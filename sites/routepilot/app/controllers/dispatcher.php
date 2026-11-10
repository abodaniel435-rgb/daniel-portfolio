<?php
declare(strict_types=1);

/** Dispatchers and administrators share the operational desk. */
function dispatch_dashboard(): void
{
    Auth::requireRole('dispatcher', 'admin');

    $stats = [
        'active' => (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status IN ('assigned','picked_up','in_transit','out_for_delivery')"),
        'unassigned' => (int)Db::value("SELECT COUNT(*) FROM shipments WHERE courier_id IS NULL AND status NOT IN ('delivered','failed')"),
        'delivered_today' => (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status = 'delivered' AND DATE(delivered_at) = CURDATE()"),
        'flagged' => (int)Db::value('SELECT COUNT(*) FROM shipments WHERE (flagged_delayed_at IS NOT NULL OR flagged_late_pickup_at IS NOT NULL) AND status NOT IN (\'delivered\',\'failed\')'),
        'revenue_month' => (float)Db::value("SELECT COALESCE(SUM(fee),0) FROM shipments WHERE status = 'delivered' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
    ];

    $board = Db::all(
        rp_shipment_select() . "
        WHERE s.status NOT IN ('delivered','failed')
        ORDER BY FIELD(s.status,'out_for_delivery','in_transit','picked_up','assigned','created'), s.pickup_date ASC, s.id ASC
        LIMIT 8"
    );

    $workload = Db::all(
        "SELECT c.id, u.name, c.zone, c.vehicle, c.max_daily_shipments, c.is_available,
                (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status NOT IN ('delivered','failed')) AS open_shipments,
                (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status = 'delivered' AND DATE(s.delivered_at) = CURDATE()) AS delivered_today
         FROM couriers c JOIN users u ON u.id = c.user_id
         WHERE u.is_active = 1
         ORDER BY open_shipments DESC, u.name"
    );

    $alerts = Db::all(
        rp_shipment_select() . "
        WHERE (s.flagged_delayed_at IS NOT NULL OR s.flagged_late_pickup_at IS NOT NULL)
          AND s.status NOT IN ('delivered','failed')
        ORDER BY s.updated_at DESC LIMIT 5"
    );

    $series = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $series[] = [
            'label' => date('D', strtotime($date)),
            'count' => (int)Db::value('SELECT COUNT(*) FROM shipments WHERE DATE(created_at) = ?', [$date]),
        ];
    }

    view('dispatch/dashboard', [
        'title' => 'Dispatch board — RoutePilot',
        'stats' => $stats,
        'board' => $board,
        'workload' => $workload,
        'alerts' => $alerts,
        'series' => $series,
        'area' => 'dispatch',
    ]);
}

function dispatch_shipments(): void
{
    Auth::requireRole('dispatcher', 'admin');

    $status = (string)($_GET['status'] ?? '');
    $service = (string)($_GET['service'] ?? '');
    $courierFilter = (string)($_GET['courier'] ?? '');
    $q = trim((string)($_GET['q'] ?? ''));

    $where = ['1=1'];
    $params = [];
    if (in_array($status, rp_statuses(), true)) {
        $where[] = 's.status = ?';
        $params[] = $status;
    }
    if (array_key_exists($service, rp_services())) {
        $where[] = 's.service = ?';
        $params[] = $service;
    }
    if ($courierFilter === 'unassigned') {
        $where[] = 's.courier_id IS NULL';
    } elseif ($courierFilter !== '' && ctype_digit($courierFilter)) {
        $where[] = 's.courier_id = ?';
        $params[] = (int)$courierFilter;
    }
    if ($q !== '') {
        $where[] = '(s.tracking_code LIKE ? OR s.recipient_name LIKE ? OR s.sender_name LIKE ? OR s.destination LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);

    $total = (int)Db::value("SELECT COUNT(*) FROM shipments s WHERE {$whereSql}", $params);
    $page = paginate($total, 15, (int)($_GET['page'] ?? 1));
    $rows = Db::all(
        rp_shipment_select() . " WHERE {$whereSql}
        ORDER BY FIELD(s.status,'out_for_delivery','in_transit','picked_up','assigned','created','failed','delivered'),
                 s.pickup_date ASC, s.id DESC
        LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );

    view('dispatch/shipments', [
        'title' => 'Shipments — RoutePilot',
        'rows' => $rows,
        'page' => $page,
        'status' => $status,
        'service' => $service,
        'courierFilter' => $courierFilter,
        'q' => $q,
        'couriers' => rp_couriers(),
        'area' => 'dispatch',
    ]);
}

function dispatch_shipment_form(): void
{
    Auth::requireRole('dispatcher', 'admin');
    view('dispatch/create', [
        'title' => 'New shipment — RoutePilot',
        'couriers' => rp_couriers(true),
        'services' => rp_services(),
        'area' => 'dispatch',
    ]);
}

function dispatch_shipment_create(): void
{
    Auth::requireRole('dispatcher', 'admin');
    verify_csrf();

    $v = (new Validator($_POST))
        ->required('sender_name', 'sender_phone', 'recipient_name', 'recipient_phone', 'recipient_email',
            'origin', 'destination', 'package_description', 'service', 'pickup_date')
        ->email('recipient_email')
        ->max('sender_name', 120)
        ->max('recipient_name', 120)
        ->max('origin', 140)
        ->max('destination', 140)
        ->max('package_description', 255)
        ->in('service', array_keys(rp_services()))
        ->pattern('sender_phone', '/^[0-9+\-\s()]{7,40}$/', 'Enter a valid sender phone number.')
        ->pattern('recipient_phone', '/^[0-9+\-\s()]{7,40}$/', 'Enter a valid recipient phone number.');

    $errors = $v->errors();

    $weight = filter_var($_POST['weight_kg'] ?? '', FILTER_VALIDATE_FLOAT);
    if ($weight === false || $weight <= 0 || $weight > 200) {
        $errors['weight_kg'] = 'Enter a weight between 0.1kg and 200kg.';
        $weight = 0.0;
    }

    $fee = filter_var($_POST['fee'] ?? '', FILTER_VALIDATE_FLOAT);
    if ($fee === false || $fee < 0 || $fee > 5000000) {
        $errors['fee'] = 'Enter a delivery fee between ₦0 and ₦5,000,000.';
        $fee = 0.0;
    }

    $pickup = (string)($_POST['pickup_date'] ?? '');
    $pickupTs = $pickup !== '' ? strtotime($pickup) : false;
    if (!$pickupTs || $pickup < date('Y-m-d', strtotime('-30 days')) || $pickup > date('Y-m-d', strtotime('+90 days'))) {
        $errors['pickup_date'] = 'Pickup date must be within 30 days past or 90 days ahead.';
    }

    if ($errors !== []) {
        remember_errors($errors);
        remember_old($_POST);
        redirect('/dispatch/shipments/new');
    }

    $courierId = null;
    $courierRow = null;
    $postedCourier = (string)($_POST['courier_id'] ?? '');
    if ($postedCourier !== '' && ctype_digit($postedCourier)) {
        $courierRow = Db::one(
            'SELECT c.*, u.name, u.is_active FROM couriers c JOIN users u ON u.id = c.user_id WHERE c.id = ?',
            [(int)$postedCourier]
        );
        if (!$courierRow || !$courierRow['is_active'] || !$courierRow['is_available']) {
            remember_errors(['courier_id' => 'Choose an active, available courier.']);
            remember_old($_POST);
            redirect('/dispatch/shipments/new');
        }
        $openForCourier = (int)Db::value(
            "SELECT COUNT(*) FROM shipments WHERE courier_id = ? AND status NOT IN ('delivered','failed')",
            [$courierRow['id']]
        );
        if ($openForCourier >= (int)$courierRow['max_daily_shipments']) {
            remember_errors(['courier_id' => $courierRow['name'] . ' is at capacity. Leave the parcel in the queue and assign it later.']);
            remember_old($_POST);
            redirect('/dispatch/shipments/new');
        }
        $courierId = (int)$courierRow['id'];
    }

    $id = Db::transaction(function () use ($v, $weight, $fee, $courierId, $courierRow): int {
        $code = rp_tracking_code();
        $id = Db::insert('shipments', [
            'tracking_code' => $code,
            'sender_name' => (string)$v->value('sender_name'),
            'sender_phone' => (string)$v->value('sender_phone'),
            'recipient_name' => (string)$v->value('recipient_name'),
            'recipient_phone' => (string)$v->value('recipient_phone'),
            'recipient_email' => (string)$v->value('recipient_email'),
            'origin' => (string)$v->value('origin'),
            'destination' => (string)$v->value('destination'),
            'package_description' => (string)$v->value('package_description'),
            'weight_kg' => round((float)$weight, 2),
            'service' => (string)$v->value('service'),
            'fee' => round((float)$fee, 2),
            'pickup_date' => (string)$v->value('pickup_date'),
            'status' => $courierId !== null ? 'assigned' : 'created',
            'courier_id' => $courierId,
            'created_by' => Auth::id(),
            'assigned_at' => $courierId !== null ? date('Y-m-d H:i:s') : null,
        ]);
        rp_record_event($id, 'created', (string)$v->value('origin'),
            'Shipment booked by dispatch. Tracking code ' . $code . '.', 'dispatch');
        if ($courierId !== null && $courierRow) {
            rp_record_event($id, 'assigned', (string)$courierRow['zone'],
                'Assigned to ' . $courierRow['name'] . ' (' . $courierRow['vehicle'] . ').', 'dispatch');
        }
        return $id;
    });

    $shipment = rp_shipment($id);
    audit('dispatch.shipment_created', $shipment['tracking_code'] . ' → ' . $shipment['destination']);
    try {
        notify_shipment_created($shipment);
        if ($shipment['status'] === 'assigned') {
            notify_status_change($shipment, 'assigned');
        }
    } catch (Throwable $e) {
        Automation::log('RoutePilot', 'created email failed: ' . $e->getMessage());
    }

    clear_errors();
    clear_old();
    flash('success', 'Shipment ' . $shipment['tracking_code'] . ' created and recipient notified.');
    redirect('/dispatch/shipments/' . $id);
}

function dispatch_shipment_show(): void
{
    Auth::requireRole('dispatcher', 'admin');
    $shipment = rp_shipment((int)($_GET['id'] ?? 0));
    if (!$shipment) {
        flash('error', 'Shipment not found.');
        redirect('/dispatch/shipments');
    }
    view('dispatch/shipment', [
        'title' => $shipment['tracking_code'] . ' — RoutePilot dispatch',
        'shipment' => $shipment,
        'events' => rp_events((int)$shipment['id']),
        'couriers' => rp_couriers(),
        'transitions' => Auth::is('admin', 'dispatcher') ? rp_allowed_transitions($shipment['status']) : [],
        'area' => 'dispatch',
    ]);
}

function dispatch_assign(): void
{
    Auth::requireRole('dispatcher', 'admin');
    verify_csrf();
    $shipment = rp_shipment((int)($_POST['shipment_id'] ?? 0));
    if (!$shipment) {
        flash('error', 'Shipment not found.');
        redirect('/dispatch/shipments');
    }
    if (!rp_is_open($shipment['status'])) {
        flash('error', 'This shipment is already closed and cannot be reassigned.');
        redirect('/dispatch/shipments/' . $shipment['id']);
    }
    $courier = Db::one(
        'SELECT c.*, u.name, u.is_active FROM couriers c JOIN users u ON u.id = c.user_id WHERE c.id = ?',
        [(int)($_POST['courier_id'] ?? 0)]
    );
    if (!$courier || !$courier['is_active'] || !$courier['is_available']) {
        flash('error', 'Choose an active, available courier.');
        redirect('/dispatch/shipments/' . $shipment['id']);
    }
    $openForCourier = (int)Db::value(
        "SELECT COUNT(*) FROM shipments WHERE courier_id = ? AND id <> ? AND status NOT IN ('delivered','failed')",
        [$courier['id'], $shipment['id']]
    );
    if ($openForCourier >= (int)$courier['max_daily_shipments']) {
        flash('error', $courier['name'] . ' is already at capacity (' . (int)$courier['max_daily_shipments'] . ' open parcels).');
        redirect('/dispatch/shipments/' . $shipment['id']);
    }

    Db::transaction(function () use ($shipment, $courier): void {
        Db::update('shipments', [
            'courier_id' => $courier['id'],
            'status' => $shipment['status'] === 'created' ? 'assigned' : $shipment['status'],
            'assigned_at' => date('Y-m-d H:i:s'),
            'last_location' => $courier['zone'],
        ], 'id = :id', ['id' => $shipment['id']]);
        rp_record_event((int)$shipment['id'], 'assigned', (string)$courier['zone'],
            'Assigned to ' . $courier['name'] . ' (' . ucfirst($courier['vehicle']) . ', ' . $courier['zone'] . ').', 'dispatch');
    });

    $updated = rp_shipment((int)$shipment['id']);
    audit('dispatch.courier_assigned', $shipment['tracking_code'] . ' → ' . $courier['name']);
    try {
        notify_status_change($updated, 'assigned');
    } catch (Throwable $e) {
        Automation::log('RoutePilot', 'assign email failed: ' . $e->getMessage());
    }
    flash('success', $courier['name'] . ' is now handling ' . $shipment['tracking_code'] . '.');
    redirect('/dispatch/shipments/' . $shipment['id']);
}

function dispatch_unassign(): void
{
    Auth::requireRole('dispatcher', 'admin');
    verify_csrf();
    $shipment = rp_shipment((int)($_POST['shipment_id'] ?? 0));
    if (!$shipment) {
        flash('error', 'Shipment not found.');
        redirect('/dispatch/shipments');
    }
    if (!rp_open_for_reassign($shipment['status'])) {
        flash('error', 'Only parcels that have not been picked up can be unassigned.');
        redirect('/dispatch/shipments/' . $shipment['id']);
    }
    $previous = rp_courier_display_name($shipment);
    Db::transaction(function () use ($shipment): void {
        Db::update('shipments', [
            'courier_id' => null,
            'status' => 'created',
            'assigned_at' => null,
        ], 'id = :id', ['id' => $shipment['id']]);
        rp_record_event((int)$shipment['id'], 'created', $shipment['origin'],
            'Courier assignment removed. Parcel returned to the dispatch queue.', 'dispatch');
    });
    audit('dispatch.courier_unassigned', $shipment['tracking_code'] . ' ← ' . $previous);
    flash('success', 'Shipment returned to the unassigned queue.');
    redirect('/dispatch/shipments/' . $shipment['id']);
}

/** Unassigning is only safe before the parcel is physically collected. */
function rp_open_for_reassign(string $status): bool
{
    return in_array($status, ['created', 'assigned'], true);
}

function dispatch_shipment_status(): void
{
    Auth::requireRole('dispatcher', 'admin');
    verify_csrf();
    $shipment = rp_shipment((int)($_POST['shipment_id'] ?? 0));
    if (!$shipment) {
        flash('error', 'Shipment not found.');
        redirect('/dispatch/shipments');
    }
    $status = (string)($_POST['status'] ?? '');
    if (!in_array($status, rp_allowed_transitions($shipment['status']), true)) {
        flash('error', 'That status change is not allowed from ' . rp_status_label($shipment['status']) . '.');
        redirect('/dispatch/shipments/' . $shipment['id']);
    }
    $location = trim((string)($_POST['location'] ?? ''));
    $note = trim((string)($_POST['note'] ?? ''));
    if ($location === '') {
        flash('error', 'Add the location where this update happened.');
        redirect('/dispatch/shipments/' . $shipment['id']);
    }
    if ($status === 'failed' && $note === '') {
        flash('error', 'Describe why the delivery failed.');
        redirect('/dispatch/shipments/' . $shipment['id']);
    }

    rp_apply_status($shipment, $status, $location, $note, 'dispatch');
    flash('success', 'Shipment moved to ' . rp_status_label($status) . ' and the recipient was notified.');
    redirect('/dispatch/shipments/' . $shipment['id']);
}

function dispatch_couriers(): void
{
    Auth::requireRole('dispatcher', 'admin');
    $couriers = Db::all(
        "SELECT c.*, u.name, u.email, u.phone, u.is_active,
                (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status NOT IN ('delivered','failed')) AS open_shipments,
                (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status = 'delivered') AS lifetime_delivered,
                (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status = 'delivered' AND DATE(s.delivered_at) = CURDATE()) AS delivered_today
         FROM couriers c JOIN users u ON u.id = c.user_id ORDER BY u.name"
    );
    view('dispatch/couriers', [
        'title' => 'Courier fleet — RoutePilot',
        'couriers' => $couriers,
        'area' => 'dispatch',
    ]);
}

function dispatch_courier_toggle(): void
{
    Auth::requireRole('dispatcher', 'admin');
    verify_csrf();
    $courier = Db::one('SELECT c.*, u.name FROM couriers c JOIN users u ON u.id = c.user_id WHERE c.id = ?', [(int)($_POST['id'] ?? 0)]);
    if (!$courier) {
        flash('error', 'Courier not found.');
        redirect('/dispatch/couriers');
    }
    $next = $courier['is_available'] ? 0 : 1;
    Db::update('couriers', ['is_available' => $next], 'id = :id', ['id' => $courier['id']]);
    audit('dispatch.courier_availability', $courier['name'] . ' → ' . ($next ? 'available' : 'off duty'));
    flash('success', $courier['name'] . ' is now ' . ($next ? 'available for assignments.' : 'off duty.'));
    redirect('/dispatch/couriers');
}
