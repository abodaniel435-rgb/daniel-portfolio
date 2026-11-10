<?php
declare(strict_types=1);

function home_index(): void
{
    $stats = [
        'listings' => (int)Db::value("SELECT COUNT(*) FROM listings WHERE status = 'open' AND quantity > 0"),
        'farms' => (int)Db::value('SELECT COUNT(*) FROM farm_profiles WHERE is_verified = 1'),
        'orders' => (int)Db::value("SELECT COUNT(*) FROM orders WHERE status IN ('completed','ready','accepted')"),
        'states' => (int)Db::value("SELECT COUNT(DISTINCT state) FROM listings WHERE status = 'open'"),
    ];

    $featured = Db::all(
        "SELECT l.*, u.name AS farmer_name, f.farm_name, f.is_verified
         FROM listings l
         JOIN users u ON u.id = l.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = l.farmer_id
         WHERE l.status = 'open' AND l.quantity > 0
         ORDER BY l.available_from ASC, l.created_at DESC LIMIT 3"
    );

    $farms = Db::all(
        "SELECT f.*, u.name, COUNT(l.id) AS live_listings
         FROM farm_profiles f
         JOIN users u ON u.id = f.user_id
         LEFT JOIN listings l ON l.farmer_id = f.user_id AND l.status = 'open' AND l.quantity > 0
         WHERE f.is_verified = 1
         GROUP BY f.id
         ORDER BY f.verified_at DESC LIMIT 3"
    );

    $recent = Db::all(
        "SELECT produce_name, unit, quantity, state FROM listings WHERE status = 'open' ORDER BY created_at DESC LIMIT 5"
    );

    view('home', [
        'title' => 'FarmCoop — buy produce straight from Nigerian farms',
        'stats' => $stats,
        'featured' => $featured,
        'farms' => $farms,
        'recent' => $recent,
        'categories' => produce_categories(),
    ]);
}

function market_index(): void
{
    $category = (string)($_GET['category'] ?? '');
    $state = (string)($_GET['state'] ?? '');
    $q = trim((string)($_GET['q'] ?? ''));
    $sort = (string)($_GET['sort'] ?? 'fresh');

    $where = ["l.status = 'open'", 'l.quantity > 0'];
    $params = [];
    if (isset(produce_categories()[$category])) {
        $where[] = 'l.category = ?';
        $params[] = $category;
    } else {
        $category = '';
    }
    if ($state !== '' && in_array($state, nigerian_states(), true)) {
        $where[] = 'l.state = ?';
        $params[] = $state;
    } else {
        $state = '';
    }
    if ($q !== '') {
        $where[] = '(l.produce_name LIKE ? OR l.description LIKE ? OR f.farm_name LIKE ? OR l.location LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);
    $orderSql = match ($sort) {
        'price_low' => 'l.unit_price ASC',
        'price_high' => 'l.unit_price DESC',
        'quantity' => 'l.quantity DESC',
        default => 'l.available_from ASC, l.created_at DESC',
    };

    $total = (int)Db::value(
        "SELECT COUNT(*) FROM listings l LEFT JOIN farm_profiles f ON f.user_id = l.farmer_id WHERE {$whereSql}",
        $params
    );
    $page = paginate($total, 9, (int)($_GET['page'] ?? 1));
    $listings = Db::all(
        "SELECT l.*, u.name AS farmer_name, f.farm_name, f.is_verified
         FROM listings l
         JOIN users u ON u.id = l.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = l.farmer_id
         WHERE {$whereSql}
         ORDER BY {$orderSql}
         LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );

    view('marketplace', [
        'title' => 'Marketplace — FarmCoop',
        'listings' => $listings,
        'page' => $page,
        'category' => $category,
        'state' => $state,
        'q' => $q,
        'sort' => $sort,
        'categories' => produce_categories(),
        'states' => nigerian_states(),
        'total' => $total,
    ]);
}

function market_listing(): void
{
    $id = (int)($_GET['id'] ?? 0);
    $listing = listing_context($id);
    if (!$listing) {
        http_response_code(404);
        view('errors/404', ['title' => 'Listing not found']);
        return;
    }

    $related = Db::all(
        "SELECT l.*, f.farm_name FROM listings l
         LEFT JOIN farm_profiles f ON f.user_id = l.farmer_id
         WHERE l.category = ? AND l.id <> ? AND l.status = 'open' AND l.quantity > 0
         ORDER BY l.available_from ASC LIMIT 3",
        [$listing['category'], $id]
    );

    $activeOrders = listing_active_orders($id);
    $canOrder = $listing['status'] === 'open' && (float)$listing['quantity'] > 0;

    view('listing', [
        'title' => $listing['produce_name'] . ' — FarmCoop',
        'listing' => $listing,
        'related' => $related,
        'activeOrders' => $activeOrders,
        'canOrder' => $canOrder,
    ]);
}

function market_farm(): void
{
    $id = (int)($_GET['id'] ?? 0);
    $farm = Db::one(
        "SELECT f.*, u.name, u.email, u.phone, u.created_at AS joined_at
         FROM farm_profiles f JOIN users u ON u.id = f.user_id
         WHERE f.id = ? AND u.is_active = 1 LIMIT 1",
        [$id]
    );
    if (!$farm) {
        http_response_code(404);
        view('errors/404', ['title' => 'Farm not found']);
        return;
    }
    $listings = Db::all(
        "SELECT * FROM listings WHERE farmer_id = ? AND status = 'open' AND quantity > 0
         ORDER BY available_from ASC LIMIT 24",
        [$farm['user_id']]
    );
    $sold = (int)Db::value("SELECT COUNT(*) FROM orders WHERE farmer_id = ? AND status IN ('accepted','ready','completed')", [$farm['user_id']]);

    view('farm', [
        'title' => $farm['farm_name'] . ' — FarmCoop',
        'farm' => $farm,
        'listings' => $listings,
        'ordersFulfilled' => $sold,
    ]);
}

function track_order(): void
{
    $reference = strtoupper(trim((string)($_GET['ref'] ?? '')));
    $order = null;
    $events = [];
    if ($reference !== '') {
        $order = Db::one(
            "SELECT o.*, l.produce_name, l.pickup_window, l.location AS listing_location,
                    fu.name AS farmer_name, f.farm_name
             FROM orders o
             JOIN listings l ON l.id = o.listing_id
             JOIN users fu ON fu.id = o.farmer_id
             LEFT JOIN farm_profiles f ON f.user_id = o.farmer_id
             WHERE o.reference = ? LIMIT 1",
            [$reference]
        );
        if ($order) {
            $events = order_events((int)$order['id']);
        }
    }

    view('track', [
        'title' => 'Track an order — FarmCoop',
        'reference' => $reference,
        'order' => $order,
        'events' => $events,
    ]);
}

function track_order_post(): void
{
    verify_csrf();
    $reference = strtoupper(trim((string)($_POST['reference'] ?? '')));
    if ($reference === '') {
        flash('error', 'Enter the order reference from your confirmation email.');
        redirect('/track');
    }
    redirect('/track?ref=' . urlencode($reference));
}
