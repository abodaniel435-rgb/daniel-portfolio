<?php
declare(strict_types=1);

function admin_overview(): void
{
    Auth::requireRole('admin');

    $stats = [
        'shipments' => (int)Db::value('SELECT COUNT(*) FROM shipments'),
        'open' => (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status NOT IN ('delivered','failed')"),
        'delivered' => (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status = 'delivered'"),
        'failed' => (int)Db::value("SELECT COUNT(*) FROM shipments WHERE status = 'failed'"),
        'revenue' => (float)Db::value("SELECT COALESCE(SUM(fee),0) FROM shipments WHERE status = 'delivered'"),
        'revenue_month' => (float)Db::value("SELECT COALESCE(SUM(fee),0) FROM shipments WHERE status = 'delivered' AND delivered_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
        'avg_fee' => (float)Db::value("SELECT COALESCE(AVG(fee),0) FROM shipments WHERE status = 'delivered'"),
        'couriers' => (int)Db::value("SELECT COUNT(*) FROM couriers c JOIN users u ON u.id = c.user_id WHERE u.is_active = 1"),
        'dispatchers' => (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'dispatcher' AND is_active = 1"),
        'queued_mail' => (int)Db::value("SELECT COUNT(*) FROM email_outbox WHERE status = 'queued'"),
    ];

    $leaderboard = Db::all(
        "SELECT u.name, c.zone, c.vehicle,
                (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status = 'delivered') AS delivered,
                (SELECT COALESCE(SUM(s.fee),0) FROM shipments s WHERE s.courier_id = c.id AND s.status = 'delivered') AS value
         FROM couriers c JOIN users u ON u.id = c.user_id
         WHERE u.is_active = 1
         ORDER BY delivered DESC, u.name LIMIT 6"
    );

    $series = [];
    $maxRevenue = 1.0;
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $value = (float)Db::value(
            "SELECT COALESCE(SUM(fee),0) FROM shipments WHERE status = 'delivered' AND DATE(delivered_at) = ?",
            [$date]
        );
        $maxRevenue = max($maxRevenue, $value);
        $series[] = ['label' => date('D', strtotime($date)), 'value' => $value];
    }
    foreach ($series as &$point) {
        $point['height'] = max(4, (int)round($point['value'] / $maxRevenue * 100));
    }
    unset($point);

    $roles = Db::all("SELECT role, COUNT(*) AS total FROM users GROUP BY role ORDER BY role");
    $logTail = [];
    $logFile = BASE_PATH . '/storage/automation.log';
    if (is_file($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $logTail = array_slice($lines, -10);
    }

    view('admin/overview', [
        'title' => 'Admin overview — RoutePilot',
        'stats' => $stats,
        'leaderboard' => $leaderboard,
        'series' => $series,
        'roles' => $roles,
        'logTail' => array_reverse($logTail),
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
        $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
        array_push($params, '%' . $q . '%', '%' . $q . '%');
    }
    if (in_array($role, ['dispatcher', 'courier', 'admin'], true)) {
        $where[] = 'u.role = ?';
        $params[] = $role;
    }
    $whereSql = implode(' AND ', $where);
    $total = (int)Db::value("SELECT COUNT(*) FROM users u WHERE {$whereSql}", $params);
    $page = paginate($total, 20, (int)($_GET['page'] ?? 1));
    $users = Db::all(
        "SELECT u.*, c.zone, c.vehicle, c.is_available,
                (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status NOT IN ('delivered','failed')) AS open_shipments
         FROM users u LEFT JOIN couriers c ON c.user_id = u.id
         WHERE {$whereSql} ORDER BY u.created_at DESC
         LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );

    view('admin/users', [
        'title' => 'Users — RoutePilot',
        'users' => $users,
        'page' => $page,
        'q' => $q,
        'role' => $role,
        'vehicles' => rp_vehicles(),
        'area' => 'admin',
    ]);
}

function admin_user_create(): void
{
    Auth::requireRole('admin');
    verify_csrf();

    $role = (string)($_POST['role'] ?? '');
    $isCourier = $role === 'courier';
    $v = (new Validator($_POST))
        ->required('name', 'email', 'password')
        ->email('email')
        ->max('name', 120)
        ->min('password', 10)
        ->in('role', ['dispatcher', 'courier', 'admin']);
    $errors = $v->errors();

    if ($isCourier) {
        $vehicleCheck = (new Validator($_POST))->required('vehicle', 'zone')->in('vehicle', rp_vehicles());
        $errors += $vehicleCheck->errors();
    }

    if ($errors !== []) {
        remember_errors($errors);
        remember_old($_POST);
        redirect('/admin/users');
    }

    try {
        $userId = Db::transaction(function () use ($v, $isCourier, $role): int {
            $userId = Auth::register([
                'name' => (string)$v->value('name'),
                'email' => (string)$v->value('email'),
                'phone' => trim((string)($_POST['phone'] ?? '')),
                'password' => (string)$v->value('password'),
            ], $role);
            if ($isCourier) {
                Db::insert('couriers', [
                    'user_id' => $userId,
                    'vehicle' => (string)($_POST['vehicle'] ?? 'motorcycle'),
                    'zone' => mb_substr(trim((string)($_POST['zone'] ?? 'Lagos Mainland')), 0, 80),
                    'plate_number' => mb_substr(trim((string)($_POST['plate_number'] ?? '')), 0, 24),
                    'max_daily_shipments' => 12,
                    'is_available' => 1,
                ]);
            }
            return $userId;
        });
        audit('admin.user_created', $role . ' · user #' . $userId);
        clear_errors();
        clear_old();
        flash('success', 'Account created for ' . $v->value('email') . '. Share the temporary password securely.');
    } catch (Throwable $e) {
        remember_errors(['email' => $e->getMessage()]);
        remember_old($_POST);
    }
    redirect('/admin/users');
}

function admin_user_toggle(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
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
    Db::update('users', ['is_active' => (int)$user['is_active'] === 1 ? 0 : 1], 'id = :id', ['id' => $id]);
    audit('admin.user_toggled', $user['email'] . ' → ' . ((int)$user['is_active'] === 1 ? 'inactive' : 'active'));
    flash('success', $user['name'] . ' has been ' . ((int)$user['is_active'] === 1 ? 'deactivated' : 'reactivated') . '.');
    redirect('/admin/users');
}

function admin_shipments(): void
{
    Auth::requireRole('admin');
    $status = (string)($_GET['status'] ?? '');
    $service = (string)($_GET['service'] ?? '');
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
    if ($q !== '') {
        $where[] = '(s.tracking_code LIKE ? OR s.recipient_name LIKE ? OR s.destination LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);
    $total = (int)Db::value("SELECT COUNT(*) FROM shipments s WHERE {$whereSql}", $params);
    $totals = Db::one(
        "SELECT COALESCE(SUM(s.fee),0) AS fees,
                COALESCE(SUM(CASE WHEN s.status = 'delivered' THEN s.fee ELSE 0 END),0) AS delivered_fees
         FROM shipments s WHERE {$whereSql}",
        $params
    );
    $byService = Db::all(
        "SELECT s.service, COUNT(*) AS total, COALESCE(SUM(s.fee),0) AS fees
         FROM shipments s WHERE {$whereSql} GROUP BY s.service ORDER BY fees DESC",
        $params
    );
    $page = paginate($total, 20, (int)($_GET['page'] ?? 1));
    $rows = Db::all(
        rp_shipment_select() . " WHERE {$whereSql} ORDER BY s.created_at DESC
        LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );

    view('admin/shipments', [
        'title' => 'All shipments — RoutePilot',
        'rows' => $rows,
        'page' => $page,
        'status' => $status,
        'service' => $service,
        'q' => $q,
        'totals' => $totals ?: ['fees' => 0, 'delivered_fees' => 0],
        'byService' => $byService,
        'area' => 'admin',
    ]);
}

function admin_outbox(): void
{
    Auth::requireRole('admin');
    $type = (string)($_GET['type'] ?? '');
    $where = '1=1';
    $params = [];
    if ($type !== '') {
        $where = 'message_type = ?';
        $params[] = $type;
    }
    $total = (int)Db::value("SELECT COUNT(*) FROM email_outbox WHERE {$where}", $params);
    $page = paginate($total, 25, (int)($_GET['page'] ?? 1));
    $rows = Db::all(
        "SELECT * FROM email_outbox WHERE {$where} ORDER BY created_at DESC
         LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );
    $counts = Db::one(
        "SELECT SUM(status = 'queued') AS queued, SUM(status = 'sent') AS sent, SUM(status = 'failed') AS failed,
                COUNT(*) AS total FROM email_outbox"
    );
    $types = array_column(Db::all('SELECT DISTINCT message_type FROM email_outbox ORDER BY message_type'), 'message_type');

    view('admin/outbox', [
        'title' => 'Email outbox — RoutePilot',
        'rows' => $rows,
        'page' => $page,
        'type' => $type,
        'types' => $types,
        'counts' => $counts ?: ['queued' => 0, 'sent' => 0, 'failed' => 0, 'total' => 0],
        'area' => 'admin',
    ]);
}

function admin_run_automation(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $ran = Automation::run('RoutePilot');
    audit('admin.automation_run', $ran . ' tasks');
    flash('success', "Automation finished ({$ran} tasks). See storage/automation.log for details.");
    redirect('/admin/overview');
}
