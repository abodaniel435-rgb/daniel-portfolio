<?php
declare(strict_types=1);

function home_index(): void
{
    $stats = [
        'delivered' => (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status = 'delivered'"),
        'active' => (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status IN ('created','assigned','picked_up','in_transit','out_for_delivery')"),
        'couriers' => (int)Db::value('SELECT COUNT(*) FROM couriers c JOIN users u ON u.id = c.user_id WHERE c.is_available = 1 AND u.is_active = 1'),
        'zones' => (int)Db::value('SELECT COUNT(DISTINCT origin) FROM shipments'),
    ];

    // Anonymised live board: the public site never exposes customer identities.
    $board = Db::all(
        "SELECT origin, destination, status, service, updated_at
         FROM shipments
         WHERE status NOT IN ('created')
         ORDER BY updated_at DESC LIMIT 4"
    );
    $demoCode = (string)Db::value("SELECT tracking_code FROM shipments WHERE status = 'delivered' ORDER BY id LIMIT 1");

    $services = rp_services();
    view('home', [
        'title' => 'RoutePilot — dispatch and public tracking for Lagos couriers',
        'stats' => $stats,
        'board' => $board,
        'demoCode' => $demoCode,
        'services' => $services,
    ]);
}

function track_show(): void
{
    $code = strtoupper(trim((string)($_GET['code'] ?? '')));
    $shipment = null;
    $error = '';

    if ($code !== '') {
        if (!preg_match('/^RP-[0-9]{6}-[A-Z0-9]{4,8}$/', $code)) {
            $error = 'Tracking codes look like RP-YYMMDD-XXXX. Please check the format and try again.';
        } else {
            $shipment = rp_shipment_by_code($code);
            if (!$shipment) {
                $error = 'We could not find a shipment with that tracking code. Confirm the code in your email or contact dispatch.';
            }
        }
    }

    view('track', [
        'title' => $shipment ? $shipment['tracking_code'] . ' — RoutePilot tracking' : 'Track a parcel — RoutePilot',
        'code' => $code,
        'shipment' => $shipment,
        'error' => $error,
        'events' => $shipment ? rp_events((int)$shipment['id']) : [],
    ]);
}

function dashboard_redirect(): void
{
    Auth::requireLogin();
    match (Auth::role()) {
        'admin' => redirect('/admin'),
        'courier' => redirect('/courier/queue'),
        default => redirect('/dispatch'),
    };
}

/** Small helper shared by the courier and dispatch detail screens. */
function rp_assert_shipment_access(array $shipment): void
{
    if (Auth::is('admin', 'dispatcher')) {
        return;
    }
    $courier = rp_courier_for_user((int)Auth::id());
    if (!$courier || (int)$shipment['courier_id'] !== (int)$courier['id']) {
        http_response_code(403);
        view('errors/403', ['title' => 'Access denied']);
        exit;
    }
}
