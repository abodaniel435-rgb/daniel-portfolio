<?php
declare(strict_types=1);

function courier_queue(): void
{
    Auth::requireRole('courier');
    $courier = rp_courier_for_user((int)Auth::id());
    if (!$courier) {
        flash('error', 'Your rider profile is not linked yet. Please contact dispatch.');
        redirect('/');
    }

    $queue = Db::all(
        rp_shipment_select() . "
        WHERE s.courier_id = ?
          AND s.status NOT IN ('delivered','failed')
        ORDER BY FIELD(s.status,'out_for_delivery','in_transit','picked_up','assigned','created'),
                 s.pickup_date ASC, s.id ASC",
        [$courier['id']]
    );
    $completed = Db::all(
        rp_shipment_select() . "
        WHERE s.courier_id = ? AND s.status IN ('delivered','failed')
        ORDER BY s.updated_at DESC LIMIT 8",
        [$courier['id']]
    );
    $stats = [
        'open' => count($queue),
        'delivered_today' => (int)Db::value(
            "SELECT COUNT(*) FROM shipments WHERE courier_id = ? AND status = 'delivered' AND DATE(delivered_at) = CURDATE()",
            [$courier['id']]
        ),
        'delivered_total' => (int)Db::value(
            "SELECT COUNT(*) FROM shipments WHERE courier_id = ? AND status = 'delivered'",
            [$courier['id']]
        ),
        'fees_today' => (float)Db::value(
            "SELECT COALESCE(SUM(fee),0) FROM shipments WHERE courier_id = ? AND status = 'delivered' AND DATE(delivered_at) = CURDATE()",
            [$courier['id']]
        ),
    ];

    view('courier/queue', [
        'title' => 'My route — RoutePilot courier',
        'courier' => $courier,
        'queue' => $queue,
        'completed' => $completed,
        'stats' => $stats,
        'area' => 'courier',
    ]);
}

function courier_shipment_show(): void
{
    Auth::requireRole('courier');
    $shipment = rp_shipment((int)($_GET['id'] ?? 0));
    if (!$shipment) {
        flash('error', 'Shipment not found.');
        redirect('/courier/queue');
    }
    rp_assert_shipment_access($shipment);
    view('courier/shipment', [
        'title' => $shipment['tracking_code'] . ' — RoutePilot courier',
        'shipment' => $shipment,
        'events' => rp_events((int)$shipment['id']),
        'transitions' => rp_allowed_transitions($shipment['status']),
        'area' => 'courier',
    ]);
}

function courier_status(): void
{
    Auth::requireRole('courier');
    verify_csrf();

    $courier = rp_courier_for_user((int)Auth::id());
    $shipment = rp_shipment((int)($_POST['shipment_id'] ?? 0));
    if (!$courier || !$shipment || (int)$shipment['courier_id'] !== (int)$courier['id']) {
        http_response_code(403);
        view('errors/403', ['title' => 'Access denied']);
        exit;
    }
    if (!rp_is_open($shipment['status'])) {
        flash('error', 'This parcel is already closed.');
        redirect('/courier/queue');
    }
    $status = (string)($_POST['status'] ?? '');
    if (!in_array($status, rp_allowed_transitions($shipment['status']), true)) {
        flash('error', 'You cannot move this parcel from ' . rp_status_label($shipment['status']) . ' to that status.');
        redirect('/courier/shipment/' . $shipment['id']);
    }
    $location = trim((string)($_POST['location'] ?? ''));
    $note = trim((string)($_POST['note'] ?? ''));
    if (mb_strlen($location) < 3) {
        flash('error', 'Add the location where this update happened (e.g. Yaba, Lagos).');
        redirect('/courier/shipment/' . $shipment['id']);
    }
    if (mb_strlen($note) > 500) {
        flash('error', 'Keep the note under 500 characters.');
        redirect('/courier/shipment/' . $shipment['id']);
    }
    if ($status === 'failed' && mb_strlen($note) < 6) {
        flash('error', 'Explain why the delivery failed so dispatch can follow up.');
        redirect('/courier/shipment/' . $shipment['id']);
    }

    rp_apply_status($shipment, $status, $location, $note, 'courier');
    flash('success', $shipment['tracking_code'] . ' updated to ' . rp_status_label($status) . '. Recipient notified.');
    redirect('/courier/queue');
}

function courier_availability(): void
{
    Auth::requireRole('courier');
    verify_csrf();
    $courier = rp_courier_for_user((int)Auth::id());
    if (!$courier) {
        flash('error', 'Rider profile missing.');
        redirect('/courier/queue');
    }
    $next = $courier['is_available'] ? 0 : 1;
    Db::update('couriers', ['is_available' => $next], 'id = :id', ['id' => $courier['id']]);
    audit('courier.availability', $next ? 'available' : 'off duty');
    flash('success', $next ? 'You are marked available for new parcels.' : 'You are marked off duty.');
    redirect('/courier/queue');
}
