<?php
declare(strict_types=1);

return [
    /**
     * Harvests that have sat on the market for more than a week without a
     * single live order are taken down so the marketplace stays honest.
     */
    'expire_stale_listings' => function (): string {
        $rows = Db::all(
            "SELECT l.*, f.farm_name, u.email AS farmer_email, u.name AS farmer_name
             FROM listings l
             JOIN users u ON u.id = l.farmer_id
             LEFT JOIN farm_profiles f ON f.user_id = l.farmer_id
             WHERE l.status IN ('open','paused')
               AND l.available_from < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND NOT EXISTS (
                   SELECT 1 FROM orders o
                   WHERE o.listing_id = l.id AND o.status IN ('pending','accepted','ready')
               )"
        );
        $count = 0;
        foreach ($rows as $listing) {
            Db::update('listings', ['status' => 'expired'], 'id = :id', ['id' => $listing['id']]);
            audit('listing.expired', $listing['reference'] . ' — ' . $listing['produce_name']);
            $count++;
        }

        // Listings that reached zero stock outside the order flow are marked sold.
        $zero = Db::run("UPDATE listings SET status = 'sold' WHERE status = 'open' AND quantity <= 0")->rowCount();
        return "{$count} listing(s) expired, {$zero} zero-stock listing(s) closed";
    },

    /** Nudge farmers who have left buyers waiting for more than a day. */
    'remind_pending_orders' => function (): string {
        $rows = Db::all(
            "SELECT o.*, l.produce_name, b.name AS buyer_name, fu.name AS farmer_name, fu.email AS farmer_email
             FROM orders o
             JOIN listings l ON l.id = o.listing_id
             JOIN users b ON b.id = o.buyer_id
             JOIN users fu ON fu.id = o.farmer_id
             WHERE o.status = 'pending'
               AND o.reminder_sent_at IS NULL
               AND o.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        $sent = 0;
        foreach ($rows as $order) {
            try {
                $body = '<p>Hello ' . e($order['farmer_name']) . ',</p>'
                    . '<p><strong>' . e($order['buyer_name']) . '</strong> is still waiting for a decision on order '
                    . '<strong>' . e($order['reference']) . '</strong> placed on ' . e(short_datetime($order['created_at'])) . '.</p>'
                    . '<p>' . e(fc_qty((float)$order['quantity'], $order['unit'])) . ' of ' . e($order['produce_name'])
                    . ' · ' . e(money((float)$order['total_amount'])) . ' · requested for ' . e(short_date($order['requested_date'])) . '</p>'
                    . '<p>Accepting quickly keeps your response rate high and holds the buyer\'s quantity for you.</p>';
                Mailer::send($order['farmer_email'], 'Order still pending — ' . $order['reference'],
                    Mailer::template('A buyer is waiting on you', $body, 'Respond now', url('/farmer/orders')), '', 'order_reminder');
                Db::update('orders', ['reminder_sent_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $order['id']]);
                $sent++;
            } catch (Throwable $e) {
                Automation::log('FarmCoop', 'reminder failed for order #' . $order['id'] . ': ' . $e->getMessage());
            }
        }
        return "{$sent} pending order reminder(s) sent";
    },

    /**
     * Orders marked ready that nobody confirmed as collected are closed as
     * delivered five days after the handover date, and the buyer is told.
     */
    'close_unconfirmed_pickups' => function (): string {
        $rows = Db::all(
            "SELECT o.*, l.produce_name, b.name AS buyer_name, b.email AS buyer_email,
                    fu.name AS farmer_name, f.farm_name
             FROM orders o
             JOIN listings l ON l.id = o.listing_id
             JOIN users b ON b.id = o.buyer_id
             JOIN users fu ON fu.id = o.farmer_id
             LEFT JOIN farm_profiles f ON f.user_id = o.farmer_id
             WHERE o.status = 'ready' AND o.requested_date < DATE_SUB(CURDATE(), INTERVAL 5 DAY)"
        );
        $closed = 0;
        foreach ($rows as $order) {
            Db::transaction(function () use ($order) {
                Db::update('orders', ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $order['id']]);
                order_log((int)$order['id'], 'completed', 'Auto-closed after the handover window', null);
            });
            try {
                notify_order_status($order, 'completed');
            } catch (Throwable $e) {
                Automation::log('FarmCoop', 'completion email failed for ' . $order['reference'] . ': ' . $e->getMessage());
            }
            $closed++;
        }
        return "{$closed} ready order(s) closed as delivered";
    },

    /** Morning snapshot for the operations inbox. */
    'daily_digest' => function (): string {
        $admin = Db::one("SELECT name, email FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
        if (!$admin) {
            return 'no active administrator';
        }
        $since = date('Y-m-d H:i:s', strtotime('-1 day'));
        $newOrders = (int)Db::value('SELECT COUNT(*) FROM orders WHERE created_at >= ?', [$since]);
        $newValue = (float)Db::value('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE created_at >= ?', [$since]);
        $pending = (int)Db::value("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $gmv = (float)Db::value("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status = 'completed'");
        $openListings = (int)Db::value("SELECT COUNT(*) FROM listings WHERE status = 'open'");
        $unverified = (int)Db::value('SELECT COUNT(*) FROM farm_profiles WHERE is_verified = 0');

        $rows = '';
        $latest = Db::all(
            "SELECT o.reference, o.total_amount, o.status, l.produce_name, b.name AS buyer_name, f.farm_name
             FROM orders o
             JOIN listings l ON l.id = o.listing_id
             JOIN users b ON b.id = o.buyer_id
             LEFT JOIN farm_profiles f ON f.user_id = o.farmer_id
             ORDER BY o.created_at DESC LIMIT 6"
        );
        foreach ($latest as $order) {
            $rows .= '<tr><td style="padding:8px 10px;border-bottom:1px solid #efe6d2">' . e($order['reference']) . '</td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #efe6d2">' . e($order['produce_name']) . '</td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #efe6d2">' . e($order['buyer_name']) . '</td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #efe6d2">' . e($order['farm_name'] ?: '—') . '</td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #efe6d2">' . e(money((float)$order['total_amount'])) . '</td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #efe6d2">' . fc_badge($order['status']) . '</td></tr>';
        }

        $body = '<p>Hello ' . e($admin['name']) . ',</p>'
            . '<p>Here is the last 24 hours on FarmCoop.</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px;margin:14px 0">'
            . '<tr><td style="padding:6px 0">New orders</td><td align="right"><strong>' . $newOrders . '</strong></td></tr>'
            . '<tr><td style="padding:6px 0">Value of new orders</td><td align="right"><strong>' . money($newValue) . '</strong></td></tr>'
            . '<tr><td style="padding:6px 0">Orders awaiting farmer action</td><td align="right"><strong>' . $pending . '</strong></td></tr>'
            . '<tr><td style="padding:6px 0">GMV (completed)</td><td align="right"><strong>' . money($gmv) . '</strong></td></tr>'
            . '<tr><td style="padding:6px 0">Live listings</td><td align="right"><strong>' . $openListings . '</strong></td></tr>'
            . '<tr><td style="padding:6px 0">Farms awaiting verification</td><td align="right"><strong>' . $unverified . '</strong></td></tr>'
            . '</table>'
            . '<table style="width:100%;border-collapse:collapse;font-size:13px"><thead><tr>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #d9c9a5">Ref</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #d9c9a5">Produce</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #d9c9a5">Buyer</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #d9c9a5">Farm</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #d9c9a5">Value</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #d9c9a5">Status</th>'
            . '</tr></thead><tbody>' . ($rows ?: '<tr><td colspan="6" style="padding:14px 10px">No orders yet.</td></tr>')
            . '</tbody></table>';
        Mailer::send($admin['email'], 'FarmCoop daily digest — ' . date('D, M j'),
            Mailer::template('Daily marketplace digest', $body, 'Open admin dashboard', url('/admin')), '', 'digest');
        return 'digest queued for ' . $admin['email'];
    },
];
