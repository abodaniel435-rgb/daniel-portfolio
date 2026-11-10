<?php
declare(strict_types=1);

return [
    'flag_overdue_pickups' => function (): string {
        $rows = Db::all(
            "SELECT * FROM shipments
             WHERE status = 'created' AND pickup_date < CURDATE() AND flagged_late_pickup_at IS NULL"
        );
        foreach ($rows as $shipment) {
            Db::update('shipments', ['flagged_late_pickup_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $shipment['id']]);
            Db::insert('tracking_events', [
                'shipment_id' => $shipment['id'],
                'status' => $shipment['status'],
                'location' => $shipment['origin'],
                'note' => 'Pickup date passed with no courier assigned. Flagged for dispatch follow-up.',
                'source' => 'automation',
                'created_by' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return count($rows) . ' overdue pickup(s) flagged';
    },

    'flag_delayed_transit' => function (): string {
        $rows = Db::all(
            "SELECT * FROM shipments
             WHERE status = 'in_transit' AND flagged_delayed_at IS NULL
               AND updated_at < (NOW() - INTERVAL 3 DAY)"
        );
        foreach ($rows as $shipment) {
            Db::update('shipments', ['flagged_delayed_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $shipment['id']]);
            Db::insert('tracking_events', [
                'shipment_id' => $shipment['id'],
                'status' => 'in_transit',
                'location' => $shipment['last_location'] ?: $shipment['origin'],
                'note' => 'No courier scan for more than 3 days. Marked as delayed — dispatch will follow up with the rider.',
                'source' => 'automation',
                'created_by' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if (filter_var($shipment['recipient_email'], FILTER_VALIDATE_EMAIL)) {
                $body = '<p>Hello ' . e(rp_recipient_first_name($shipment)) . ',</p>'
                    . '<p>Your parcel <strong>' . e($shipment['tracking_code']) . '</strong> is taking longer than expected. '
                    . 'Our dispatch desk has flagged it as delayed and is following up with the assigned rider.</p>'
                    . rp_shipment_summary_block($shipment);
                Mailer::send(
                    $shipment['recipient_email'],
                    'Delivery delayed — ' . $shipment['tracking_code'],
                    Mailer::template('Your delivery is delayed', $body, 'View live status', rp_track_url($shipment['tracking_code'])),
                    '',
                    'delivery_delayed'
                );
            }
        }
        return count($rows) . ' in-transit shipment(s) flagged as delayed';
    },

    'eta_reminders' => function (): string {
        $rows = Db::all(
            "SELECT * FROM shipments WHERE status = 'out_for_delivery' AND eta_reminder_sent_at IS NULL"
        );
        $sent = 0;
        foreach ($rows as $shipment) {
            if (filter_var($shipment['recipient_email'], FILTER_VALIDATE_EMAIL)) {
                $body = '<p>Hello ' . e(rp_recipient_first_name($shipment)) . ',</p>'
                    . '<p>Your parcel <strong>' . e($shipment['tracking_code']) . '</strong> is out for delivery and should reach you today'
                    . ($shipment['last_location'] !== '' ? ' (last scanned at <strong>' . e($shipment['last_location']) . '</strong>)' : '')
                    . '.</p>'
                    . rp_shipment_summary_block($shipment)
                    . '<p>Please make sure someone is available at ' . e($shipment['destination']) . ' to receive it.</p>';
                Mailer::send(
                    $shipment['recipient_email'],
                    'Arriving today — ' . $shipment['tracking_code'],
                    Mailer::template('Your parcel arrives today', $body, 'Track the rider', rp_track_url($shipment['tracking_code'])),
                    '',
                    'eta_reminder'
                );
            }
            Db::update('shipments', ['eta_reminder_sent_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $shipment['id']]);
            $sent++;
        }
        return "{$sent} ETA reminder(s) queued";
    },

    'daily_digest' => function (): string {
        $already = (int)Db::value(
            "SELECT COUNT(*) FROM email_outbox WHERE message_type = 'daily_digest' AND DATE(created_at) = CURDATE()"
        );
        if ($already > 0) {
            return 'digest already sent today';
        }
        $admin = Db::one("SELECT email FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
        $to = $admin['email'] ?? (string)env('ADMIN_EMAIL', '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return 'no active administrator to notify';
        }
        $created = (int)Db::value('SELECT COUNT(*) FROM shipments WHERE DATE(created_at) = CURDATE()');
        $delivered = (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status = 'delivered' AND DATE(delivered_at) = CURDATE()");
        $failed = (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status = 'failed' AND DATE(updated_at) = CURDATE()");
        $open = (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status NOT IN ('delivered','failed')");
        $unassigned = (int)Db::value("SELECT COUNT(*) FROM shipments WHERE courier_id IS NULL AND status NOT IN ('delivered','failed')");
        $flagged = (int)Db::value("SELECT COUNT(*) FROM shipments WHERE (flagged_delayed_at IS NOT NULL OR flagged_late_pickup_at IS NOT NULL) AND status NOT IN ('delivered','failed')");
        $revenue = (float)Db::value("SELECT COALESCE(SUM(fee),0) FROM shipments WHERE status = 'delivered' AND DATE(delivered_at) = CURDATE()");

        $couriers = Db::all(
            "SELECT u.name, (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status NOT IN ('delivered','failed')) AS open_shipments,
                    (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status = 'delivered' AND DATE(s.delivered_at) = CURDATE()) AS delivered_today
             FROM couriers c JOIN users u ON u.id = c.user_id WHERE u.is_active = 1 ORDER BY u.name"
        );
        $rows = '';
        foreach ($couriers as $courier) {
            $rows .= '<tr><td style="padding:8px 10px;border-bottom:1px solid #e6ebf2">' . e($courier['name']) . '</td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #e6ebf2">' . (int)$courier['open_shipments'] . '</td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #e6ebf2">' . (int)$courier['delivered_today'] . '</td></tr>';
        }
        $body = '<p>Daily dispatch digest for ' . e(date('l, F j, Y')) . '.</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:18px">'
            . '<tr><td style="padding:6px 0">New shipments</td><td style="padding:6px 0;font-weight:700">' . $created . '</td></tr>'
            . '<tr><td style="padding:6px 0">Delivered today</td><td style="padding:6px 0;font-weight:700">' . $delivered . '</td></tr>'
            . '<tr><td style="padding:6px 0">Failed today</td><td style="padding:6px 0;font-weight:700">' . $failed . '</td></tr>'
            . '<tr><td style="padding:6px 0">Still open</td><td style="padding:6px 0;font-weight:700">' . $open . '</td></tr>'
            . '<tr><td style="padding:6px 0">Unassigned</td><td style="padding:6px 0;font-weight:700">' . $unassigned . '</td></tr>'
            . '<tr><td style="padding:6px 0">Flagged / delayed</td><td style="padding:6px 0;font-weight:700">' . $flagged . '</td></tr>'
            . '<tr><td style="padding:6px 0">Revenue collected</td><td style="padding:6px 0;font-weight:700">' . money($revenue) . '</td></tr>'
            . '</table>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px"><thead><tr>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #cbd5e1">Courier</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #cbd5e1">Open</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #cbd5e1">Delivered today</th>'
            . '</tr></thead><tbody>' . ($rows ?: '<tr><td colspan="3" style="padding:14px 10px">No active couriers.</td></tr>')
            . '</tbody></table>';
        Mailer::send(
            $to,
            'RoutePilot daily digest — ' . date('D, M j'),
            Mailer::template('Daily dispatch digest', $body, 'Open the dispatch board', url('/dispatch')),
            '',
            'daily_digest'
        );
        return 'digest queued for ' . $to;
    },
];
