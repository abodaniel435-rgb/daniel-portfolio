<?php
declare(strict_types=1);

/** ---------------------------------------------------------------- catalogue */

function rp_services(): array
{
    return [
        'same_day' => [
            'label' => 'Same day',
            'eta' => 'Delivered before 9pm today',
            'blurb' => 'Lagos-wide pickup and delivery within the same working day.',
            'multiplier' => 1.0,
        ],
        'express' => [
            'label' => 'Express',
            'eta' => 'Next working day, priority rider',
            'blurb' => 'Priority rider allocation with a next-day delivery promise.',
            'multiplier' => 1.0,
        ],
        'standard' => [
            'label' => 'Standard',
            'eta' => '2–4 working days',
            'blurb' => 'Cost-efficient consolidated routing for non-urgent parcels.',
            'multiplier' => 1.0,
        ],
    ];
}

function rp_service_label(string $service): string
{
    return rp_services()[$service]['label'] ?? ucfirst(str_replace('_', ' ', $service));
}

function rp_vehicles(): array
{
    return ['motorcycle', 'bicycle', 'van', 'car'];
}

/** ------------------------------------------------------------------- status */

function rp_statuses(): array
{
    return ['created', 'assigned', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed'];
}

function rp_status_label(string $status): string
{
    return [
        'created' => 'Created',
        'assigned' => 'Courier assigned',
        'picked_up' => 'Picked up',
        'in_transit' => 'In transit',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'failed' => 'Delivery failed',
    ][$status] ?? ucfirst(str_replace('_', ' ', $status));
}

/** Statuses a courier may move a shipment into, keyed by the current status. */
function rp_allowed_transitions(string $status): array
{
    return [
        'created' => [],
        'assigned' => ['picked_up', 'failed'],
        'picked_up' => ['in_transit', 'failed'],
        'in_transit' => ['out_for_delivery', 'failed'],
        'out_for_delivery' => ['delivered', 'failed'],
        'delivered' => [],
        'failed' => [],
    ][$status] ?? [];
}

function rp_is_open(string $status): bool
{
    return !in_array($status, ['delivered', 'failed'], true);
}

/**
 * Visually distinct status pill. Core status_badge() is generic; RoutePilot
 * renders its own tone class so pills can glow on the dark dashboard.
 */
function rp_pill(string $status, string $extra = ''): string
{
    $tone = [
        'created' => 'draft',
        'assigned' => 'queue',
        'picked_up' => 'moving',
        'in_transit' => 'moving',
        'out_for_delivery' => 'near',
        'delivered' => 'done',
        'failed' => 'fail',
    ][$status] ?? 'draft';
    return '<span class="pill pill-' . e($tone) . ' ' . e($extra) . '">' . e(rp_status_label($status)) . '</span>';
}

/** ----------------------------------------------------------- tracking codes */

function rp_tracking_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($attempt = 0; $attempt < 12; $attempt++) {
        $suffix = '';
        for ($i = 0; $i < 4; $i++) {
            $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $code = 'RP-' . date('ymd') . '-' . $suffix;
        if (!Db::value('SELECT COUNT(*) FROM shipments WHERE tracking_code = ?', [$code])) {
            return $code;
        }
    }
    // Deterministic fallback keeps the code shape even under extreme collisions.
    return 'RP-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/** -------------------------------------------------------------- data access */

function rp_shipment_select(): string
{
    return 'SELECT s.*, c.zone AS courier_zone, c.vehicle AS courier_vehicle, c.plate_number AS courier_plate,
                   u.name AS courier_name, u.phone AS courier_phone,
                   creator.name AS creator_name
            FROM shipments s
            LEFT JOIN couriers c ON c.id = s.courier_id
            LEFT JOIN users u ON u.id = c.user_id
            LEFT JOIN users creator ON creator.id = s.created_by';
}

function rp_shipment(int $id): ?array
{
    return Db::one(rp_shipment_select() . ' WHERE s.id = ? LIMIT 1', [$id]);
}

function rp_shipment_by_code(string $code): ?array
{
    return Db::one(rp_shipment_select() . ' WHERE s.tracking_code = ? LIMIT 1', [strtoupper(trim($code))]);
}

function rp_events(int $shipmentId): array
{
    return Db::all(
        'SELECT te.*, u.name AS author_name FROM tracking_events te
         LEFT JOIN users u ON u.id = te.created_by
         WHERE te.shipment_id = ? ORDER BY te.created_at ASC, te.id ASC',
        [$shipmentId]
    );
}

function rp_couriers(bool $availableOnly = false): array
{
    $sql = "SELECT c.*, u.name, u.email, u.phone, u.is_active,
                   (SELECT COUNT(*) FROM shipments s
                     WHERE s.courier_id = c.id AND s.status NOT IN ('delivered','failed')) AS open_shipments
            FROM couriers c JOIN users u ON u.id = c.user_id";
    if ($availableOnly) {
        $sql .= ' WHERE c.is_available = 1 AND u.is_active = 1';
    }
    $sql .= ' ORDER BY u.name';
    return Db::all($sql);
}

function rp_courier_for_user(int $userId): ?array
{
    return Db::one('SELECT * FROM couriers WHERE user_id = ? LIMIT 1', [$userId]);
}

function rp_courier_display_name(?array $shipment): string
{
    return (string)($shipment['courier_name'] ?? '') !== '' ? (string)$shipment['courier_name'] : 'Unassigned';
}

/** ----------------------------------------------------------------- ETA copy */

function rp_eta_copy(array $shipment): string
{
    if ($shipment['status'] === 'delivered') {
        return 'Delivered ' . short_datetime($shipment['delivered_at'] ?? null) . '.';
    }
    if ($shipment['status'] === 'failed') {
        return 'Delivery could not be completed. Our dispatch desk will contact you to re-attempt.';
    }
    $service = rp_services()[$shipment['service']] ?? rp_services()['standard'];
    return match ($shipment['status']) {
        'created' => 'Awaiting courier assignment. Expected ' . strtolower($service['eta']) . '.',
        'assigned' => 'Courier assigned. Pickup scheduled for ' . short_date($shipment['pickup_date']) . '.',
        'picked_up' => 'Parcel collected and being sorted for the ' . rp_service_label($shipment['service']) . ' route.',
        'in_transit' => 'On the road to ' . $shipment['destination'] . '. ' . $service['eta'] . '.',
        'out_for_delivery' => 'Rider is completing the final leg to ' . $shipment['destination'] . ' today.',
        default => $service['eta'],
    };
}

/** Timeline used on the public tracking page and shipment detail screens. */
function rp_timeline(array $shipment): array
{
    $steps = [
        'created' => 'Shipment booked',
        'assigned' => 'Courier assigned',
        'picked_up' => 'Picked up',
        'in_transit' => 'In transit',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
    ];
    $order = array_keys($steps);
    $current = array_search($shipment['status'], $order, true);
    $failed = $shipment['status'] === 'failed';
    $timeline = [];
    foreach ($steps as $status => $label) {
        $index = array_search($status, $order, true);
        $timeline[] = [
            'status' => $status,
            'label' => $label,
            'done' => $current !== false && $index <= $current,
            'failed' => $failed && $status === 'out_for_delivery',
        ];
    }
    return $timeline;
}

/** ------------------------------------------------------------- notifications */

function rp_track_url(string $code): string
{
    return url('/track?code=' . rawurlencode($code));
}

function rp_recipient_first_name(array $shipment): string
{
    $parts = preg_split('/\s+/', trim((string)$shipment['recipient_name'])) ?: [];
    return $parts[0] ?? 'there';
}

function rp_shipment_summary_block(array $shipment): string
{
    return '<table style="width:100%;border-collapse:collapse;font-size:14px;margin:16px 0">'
        . '<tr><td style="padding:6px 0;color:#64748b">Tracking code</td><td style="padding:6px 0;font-weight:700">' . e($shipment['tracking_code']) . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#64748b">Route</td><td style="padding:6px 0">' . e($shipment['origin']) . ' → ' . e($shipment['destination']) . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#64748b">Service</td><td style="padding:6px 0">' . e(rp_service_label($shipment['service'])) . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#64748b">Pickup date</td><td style="padding:6px 0">' . e(short_date($shipment['pickup_date'])) . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#64748b">Parcel</td><td style="padding:6px 0">' . e($shipment['package_description']) . '</td></tr>'
        . '</table>';
}

function notify_shipment_created(array $shipment): void
{
    if (!filter_var($shipment['recipient_email'], FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $body = '<p>Hello ' . e(rp_recipient_first_name($shipment)) . ',</p>'
        . '<p>A parcel is on its way to you from <strong>' . e($shipment['sender_name']) . '</strong>. '
        . 'Use the tracking code below to follow it in real time.</p>'
        . rp_shipment_summary_block($shipment)
        . '<p>' . e(rp_eta_copy($shipment)) . '</p>';
    Mailer::send(
        $shipment['recipient_email'],
        'Your parcel is booked — ' . $shipment['tracking_code'],
        Mailer::template('Your delivery is booked', $body, 'Track this parcel', rp_track_url($shipment['tracking_code'])),
        '',
        'shipment_created'
    );
}

function notify_status_change(array $shipment, string $status, string $note = '', string $location = ''): void
{
    if (!filter_var($shipment['recipient_email'], FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $headline = [
        'assigned' => ['A courier is assigned', 'A RoutePilot courier has been assigned to your parcel.'],
        'picked_up' => ['Your parcel was picked up', 'Your parcel has been collected and is on its way.'],
        'in_transit' => ['Your parcel is in transit', 'Your parcel is moving along the delivery route.'],
        'out_for_delivery' => ['Out for delivery today', 'Your rider is completing the final leg of the delivery.'],
        'delivered' => ['Delivered successfully', 'Your parcel has been delivered. Thank you for using RoutePilot.'],
        'failed' => ['Delivery attempt unsuccessful', 'We could not complete this delivery. Dispatch will contact you shortly.'],
    ];
    if (!isset($headline[$status])) {
        return;
    }
    [$heading, $line] = $headline[$status];
    $body = '<p>Hello ' . e(rp_recipient_first_name($shipment)) . ',</p><p>' . e($line) . '</p>'
        . rp_shipment_summary_block($shipment)
        . ($location !== '' ? '<p><strong>Last scanned:</strong> ' . e($location) . '</p>' : '')
        . ($note !== '' ? '<p><strong>Courier note:</strong> ' . e($note) . '</p>' : '')
        . '<p>' . e(rp_eta_copy(array_merge($shipment, ['status' => $status]))) . '</p>';
    Mailer::send(
        $shipment['recipient_email'],
        $heading . ' — ' . $shipment['tracking_code'],
        Mailer::template($heading, $body, 'Open tracking page', rp_track_url($shipment['tracking_code'])),
        '',
        'status_' . $status
    );
}

function notify_delivery_confirmation(array $shipment): void
{
    if (!filter_var($shipment['recipient_email'], FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $body = '<p>Hello ' . e(rp_recipient_first_name($shipment)) . ',</p>'
        . '<p>Your parcel <strong>' . e($shipment['tracking_code']) . '</strong> was delivered'
        . ($shipment['last_location'] !== '' ? ' at <strong>' . e($shipment['last_location']) . '</strong>' : '')
        . '.</p>'
        . rp_shipment_summary_block($shipment)
        . '<p>If you did not receive this parcel, reply to this email and our dispatch desk will investigate.</p>';
    Mailer::send(
        $shipment['recipient_email'],
        'Delivery confirmed — ' . $shipment['tracking_code'],
        Mailer::template('Delivery confirmed', $body, 'View delivery record', rp_track_url($shipment['tracking_code'])),
        '',
        'delivery_confirmed'
    );
}

/** ------------------------------------------------------------ write helpers */

function rp_record_event(int $shipmentId, string $status, string $location, string $note, string $source = 'courier'): int
{
    return Db::insert('tracking_events', [
        'shipment_id' => $shipmentId,
        'status' => $status,
        'location' => mb_substr($location, 0, 140),
        'note' => mb_substr($note, 0, 500),
        'source' => $source,
        'created_by' => Auth::id(),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

/**
 * Single write path for every status change so couriers, dispatchers and
 * automation behave identically.
 */
function rp_apply_status(array $shipment, string $status, string $location, string $note, string $source): void
{
    Db::transaction(function () use ($shipment, $status, $location, $note, $source): void {
        $data = [
            'status' => $status,
            'last_location' => mb_substr($location, 0, 140),
        ];
        if ($status === 'delivered') {
            $data['delivered_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'failed') {
            $data['failed_reason'] = mb_substr($note, 0, 255);
        }
        Db::update('shipments', $data, 'id = :id', ['id' => $shipment['id']]);
        rp_record_event((int)$shipment['id'], $status, $location, $note, $source);
    });

    $updated = rp_shipment((int)$shipment['id']);
    audit('shipment.status_' . $status, $shipment['tracking_code']);
    try {
        if ($status === 'delivered') {
            notify_delivery_confirmation($updated);
        } else {
            notify_status_change($updated, $status, $note, $location);
        }
    } catch (Throwable $e) {
        Automation::log('RoutePilot', 'status email failed: ' . $e->getMessage());
    }
}

/** Human summary line for a tracking event timeline. */
function rp_time_ago(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return '—';
    }
    $diff = max(0, time() - $ts);
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        return (int)floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return (int)floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return (int)floor($diff / 86400) . 'd ago';
    }
    return date('M j', $ts);
}

function rp_event_source_label(string $source): string
{
    return [
        'dispatch' => 'Dispatch desk',
        'courier' => 'Courier update',
        'automation' => 'RoutePilot automation',
        'system' => 'System',
    ][$source] ?? 'Update';
}
