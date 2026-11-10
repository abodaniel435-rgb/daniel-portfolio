<?php
declare(strict_types=1);

/* ------------------------------------------------------------------ *
 *  Administration
 * ------------------------------------------------------------------ */

function admin_dashboard(): void
{
    Auth::requireRole('admin');
    $stats = [
        'gmv' => (float)Db::value("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status = 'completed'"),
        'pipeline' => (float)Db::value("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status IN ('pending','accepted','ready')"),
        'farmers' => (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'farmer' AND is_active = 1"),
        'buyers' => (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'buyer' AND is_active = 1"),
        'pending' => (int)Db::value("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
        'listings' => (int)Db::value("SELECT COUNT(*) FROM listings WHERE status = 'open'"),
        'unverified' => (int)Db::value('SELECT COUNT(*) FROM farm_profiles WHERE is_verified = 0'),
        'orders' => (int)Db::value('SELECT COUNT(*) FROM orders'),
    ];

    $series = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $series[] = [
            'date' => $date,
            'label' => date('D', strtotime($date)),
            'count' => (int)Db::value('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?', [$date]),
            'value' => (float)Db::value("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = ? AND status IN ('accepted','ready','completed')", [$date]),
        ];
    }

    $recent = Db::all(
        "SELECT o.reference, o.status, o.total_amount, o.created_at, l.produce_name,
                b.name AS buyer_name, f.farm_name, fu.name AS farmer_name
         FROM orders o
         JOIN listings l ON l.id = o.listing_id
         JOIN users b ON b.id = o.buyer_id
         JOIN users fu ON fu.id = o.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = o.farmer_id
         ORDER BY o.created_at DESC LIMIT 8"
    );

    $topFarms = Db::all(
        "SELECT f.farm_name, f.state, f.is_verified, u.name,
                COALESCE(SUM(CASE WHEN o.status IN ('accepted','ready','completed') THEN o.total_amount END), 0) AS value
         FROM farm_profiles f
         JOIN users u ON u.id = f.user_id
         LEFT JOIN orders o ON o.farmer_id = f.user_id
         GROUP BY f.id ORDER BY value DESC LIMIT 5"
    );

    $categories = Db::all(
        "SELECT category, COUNT(*) AS listings, COALESCE(SUM(quantity),0) AS quantity
         FROM listings WHERE status = 'open' GROUP BY category ORDER BY listings DESC"
    );

    view('admin/dashboard', [
        'title' => 'Marketplace overview — FarmCoop',
        'stats' => $stats,
        'series' => $series,
        'recent' => $recent,
        'topFarms' => $topFarms,
        'categories' => $categories,
        'area' => 'admin',
    ]);
}

function admin_users(): void
{
    Auth::requireRole('admin');
    $q = trim((string)($_GET['q'] ?? ''));
    $role = (string)($_GET['role'] ?? '');
    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }
    if (in_array($role, ['farmer', 'buyer', 'admin'], true)) {
        $where[] = 'u.role = ?';
        $params[] = $role;
    } else {
        $role = '';
    }
    $whereSql = implode(' AND ', $where);
    $total = (int)Db::value("SELECT COUNT(*) FROM users u WHERE {$whereSql}", $params);
    $page = paginate($total, 20, (int)($_GET['page'] ?? 1));
    $users = Db::all(
        "SELECT u.*, f.farm_name, f.state AS farm_state, f.is_verified,
                (SELECT COUNT(*) FROM listings l WHERE l.farmer_id = u.id) AS listings,
                (SELECT COUNT(*) FROM orders o WHERE o.buyer_id = u.id) AS orders_placed
         FROM users u
         LEFT JOIN farm_profiles f ON f.user_id = u.id
         WHERE {$whereSql}
         ORDER BY FIELD(u.role, 'admin', 'farmer', 'buyer'), u.created_at DESC
         LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );
    view('admin/users', [
        'title' => 'Users — FarmCoop',
        'users' => $users,
        'page' => $page,
        'q' => $q,
        'role' => $role,
        'area' => 'admin',
    ]);
}

function admin_user_toggle(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id === Auth::id()) {
        flash('error', 'You cannot deactivate your own account.');
        redirect('/admin/users');
    }
    $user = Db::one('SELECT * FROM users WHERE id = ?', [$id]);
    if (!$user) {
        flash('error', 'User not found.');
        redirect('/admin/users');
    }
    if ($user['role'] === 'admin' && (int)$user['is_active'] === 1) {
        $activeAdmins = (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
        if ($activeAdmins <= 1) {
            flash('error', 'Keep at least one active administrator.');
            redirect('/admin/users');
        }
    }
    Db::update('users', ['is_active' => (int)$user['is_active'] ? 0 : 1], 'id = :id', ['id' => $id]);
    audit('admin.user_toggled', $user['email'] . ' → ' . ((int)$user['is_active'] ? 'inactive' : 'active'));
    flash('success', $user['name'] . ' has been ' . ((int)$user['is_active'] ? 'deactivated' : 'reactivated') . '.');
    redirect('/admin/users');
}

function admin_farmer_verify(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $user = Db::one("SELECT * FROM users WHERE id = ? AND role = 'farmer'", [$id]);
    $profile = $user ? farmer_profile($id) : null;
    if (!$user || !$profile) {
        flash('error', 'Farmer profile not found.');
        redirect('/admin/users');
    }
    $next = (int)$profile['is_verified'] === 1 ? 0 : 1;
    Db::update('farm_profiles', [
        'is_verified' => $next,
        'verified_at' => $next ? date('Y-m-d H:i:s') : null,
    ], 'user_id = :uid', ['uid' => $id]);
    audit('admin.farmer_verified', $profile['farm_name'] . ' → ' . ($next ? 'verified' : 'unverified'));
    if ($next) {
        try {
            Mailer::send(
                $user['email'],
                'Your farm is verified — FarmCoop',
                Mailer::template('Farm verified', '<p>Hello ' . e($user['name']) . ',</p><p><strong>'
                    . e($profile['farm_name']) . '</strong> is now a verified FarmCoop storefront. Buyers will see the verified badge on your listings and farm page.</p>', 'Open farmer dashboard', url('/farmer')),
                '',
                'farmer_verified'
            );
        } catch (Throwable $mailError) {
            Automation::log('FarmCoop', 'verify email failed: ' . $mailError->getMessage());
        }
    }
    flash('success', $profile['farm_name'] . ($next ? ' is now verified.' : ' is no longer verified.'));
    redirect('/admin/users');
}

function admin_listings(): void
{
    Auth::requireRole('admin');
    $status = (string)($_GET['status'] ?? '');
    $category = (string)($_GET['category'] ?? '');
    $q = trim((string)($_GET['q'] ?? ''));
    $where = ['1=1'];
    $params = [];
    if (in_array($status, ['open', 'paused', 'reserved', 'sold', 'expired'], true)) {
        $where[] = 'l.status = ?';
        $params[] = $status;
    } else {
        $status = '';
    }
    if (isset(produce_categories()[$category])) {
        $where[] = 'l.category = ?';
        $params[] = $category;
    } else {
        $category = '';
    }
    if ($q !== '') {
        $where[] = '(l.produce_name LIKE ? OR l.reference LIKE ? OR f.farm_name LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);
    $total = (int)Db::value(
        "SELECT COUNT(*) FROM listings l LEFT JOIN farm_profiles f ON f.user_id = l.farmer_id WHERE {$whereSql}",
        $params
    );
    $page = paginate($total, 20, (int)($_GET['page'] ?? 1));
    $rows = Db::all(
        "SELECT l.*, f.farm_name, f.is_verified, u.name AS farmer_name,
                (SELECT COUNT(*) FROM orders o WHERE o.listing_id = l.id) AS order_count
         FROM listings l
         JOIN users u ON u.id = l.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = l.farmer_id
         WHERE {$whereSql}
         ORDER BY FIELD(l.status, 'open', 'paused', 'reserved', 'sold', 'expired'), l.created_at DESC
         LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );
    view('admin/listings', [
        'title' => 'Listings — FarmCoop',
        'rows' => $rows,
        'page' => $page,
        'status' => $status,
        'category' => $category,
        'q' => $q,
        'categories' => produce_categories(),
        'area' => 'admin',
    ]);
}

function admin_listing_status(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    if (!in_array($status, ['open', 'paused', 'reserved', 'sold', 'expired'], true)) {
        flash('error', 'Invalid listing status.');
        redirect('/admin/listings');
    }
    $listing = Db::one('SELECT * FROM listings WHERE id = ?', [$id]);
    if (!$listing) {
        flash('error', 'Listing not found.');
        redirect('/admin/listings');
    }
    if ($status === 'open' && (float)$listing['quantity'] <= 0) {
        flash('error', 'A listing with no remaining quantity cannot be reopened.');
        redirect('/admin/listings');
    }
    Db::update('listings', ['status' => $status], 'id = :id', ['id' => $id]);
    audit('admin.listing_status', $listing['reference'] . ' → ' . $status);
    flash('success', $listing['produce_name'] . ' is now ' . $status . '.');
    redirect('/admin/listings');
}

function admin_orders(): void
{
    Auth::requireRole('admin');
    $status = (string)($_GET['status'] ?? '');
    $q = trim((string)($_GET['q'] ?? ''));
    $where = ['1=1'];
    $params = [];
    if (in_array($status, ['pending', 'accepted', 'ready', 'completed', 'rejected', 'cancelled'], true)) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    } else {
        $status = '';
    }
    if ($q !== '') {
        $where[] = '(o.reference LIKE ? OR b.name LIKE ? OR f.farm_name LIKE ? OR l.produce_name LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);
    $total = (int)Db::value(
        "SELECT COUNT(*) FROM orders o
         JOIN listings l ON l.id = o.listing_id
         JOIN users b ON b.id = o.buyer_id
         JOIN users fu ON fu.id = o.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = o.farmer_id
         WHERE {$whereSql}",
        $params
    );
    $page = paginate($total, 25, (int)($_GET['page'] ?? 1));
    $rows = Db::all(
        "SELECT o.*, l.produce_name, b.name AS buyer_name, fu.name AS farmer_name, f.farm_name
         FROM orders o
         JOIN listings l ON l.id = o.listing_id
         JOIN users b ON b.id = o.buyer_id
         JOIN users fu ON fu.id = o.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = o.farmer_id
         WHERE {$whereSql}
         ORDER BY o.created_at DESC
         LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );
    $totals = Db::one(
        "SELECT COUNT(*) AS orders, COALESCE(SUM(total_amount),0) AS value,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount END),0) AS settled
         FROM orders"
    );
    view('admin/orders', [
        'title' => 'All orders — FarmCoop',
        'rows' => $rows,
        'page' => $page,
        'status' => $status,
        'q' => $q,
        'totals' => $totals ?: ['orders' => 0, 'value' => 0, 'settled' => 0],
        'area' => 'admin',
    ]);
}

function admin_outbox(): void
{
    Auth::requireRole('admin');
    $rows = Db::all('SELECT * FROM email_outbox ORDER BY created_at DESC, id DESC LIMIT 100');
    $counts = Db::one(
        "SELECT COALESCE(SUM(status = 'queued'),0) AS queued, COALESCE(SUM(status = 'sent'),0) AS sent,
                COALESCE(SUM(status = 'failed'),0) AS failed FROM email_outbox"
    );
    view('admin/outbox', [
        'title' => 'Email outbox — FarmCoop',
        'rows' => $rows,
        'counts' => $counts ?: ['queued' => 0, 'sent' => 0, 'failed' => 0],
        'area' => 'admin',
    ]);
}

function admin_run_automation(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $ran = Automation::run('FarmCoop');
    flash('success', "Automation finished ({$ran} tasks). Log: storage/automation.log");
    redirect('/admin/outbox');
}
