<?php
declare(strict_types=1);

function admin_open_statuses(): array
{
    $open = [];
    foreach ((array)cfg('status_meta', []) as $key => $meta) {
        if (!empty($meta['next'])) {
            $open[] = $key;
        }
    }
    return $open;
}

function admin_dashboard(): void
{
    Auth::requireRole('admin');
    $table = txn_table();
    $open = admin_open_statuses();
    $openIn = $open ? implode(',', array_fill(0, count($open), '?')) : "'__none__'";

    $stats = [
        'total' => (int)Db::value('SELECT COUNT(*) FROM ' . $table),
        'open' => (int)Db::value('SELECT COUNT(*) FROM ' . $table . ' WHERE status IN (' . $openIn . ')', $open),
        'today' => (int)Db::value('SELECT COUNT(*) FROM ' . $table . ' WHERE request_date = CURDATE()'),
        'customers' => (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'member'"),
    ];
    if (cfg('modules.payments', false)) {
        $stats['revenue'] = (float)Db::value("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'recorded'");
    } else {
        $final = array_keys(array_filter((array)cfg('status_meta', []), static fn ($m) => empty($m['next'])));
        $finalIn = $final ? implode(',', array_fill(0, count($final), '?')) : "'__none__'";
        $stats['revenue'] = (float)Db::value('SELECT COALESCE(SUM(amount),0) FROM ' . $table . ' WHERE status IN (' . $finalIn . ')', $final);
    }
    if (cfg('modules.orders', false)) {
        $stats['orders'] = (int)Db::value('SELECT COUNT(*) FROM orders');
        $stats['low_stock'] = (int)Db::value('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock <= 5');
    }
    if (cfg('modules.messaging', false)) {
        $stats['new_messages'] = (int)Db::value("SELECT COUNT(*) FROM messages WHERE status = 'new'");
    }
    $stats['outbox_failed'] = (int)Db::value("SELECT COUNT(*) FROM email_outbox WHERE status = 'failed'");

    $series = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $series[] = [
            'label' => date('D', strtotime($date)),
            'date' => $date,
            'count' => (int)Db::value('SELECT COUNT(*) FROM ' . $table . ' WHERE request_date = ?', [$date]),
        ];
    }

    $recent = Db::all('SELECT * FROM ' . $table . ' ORDER BY created_at DESC LIMIT 6');
    $todays = Db::all('SELECT * FROM ' . $table . ' WHERE request_date = CURDATE() ORDER BY request_time LIMIT 10');
    $logFile = BASE_PATH . '/storage/automation.log';
    $log = [];
    if (is_file($logFile)) {
        $lines = array_slice(array_filter(explode("\n", (string)file_get_contents($logFile))), -6);
        $log = array_reverse($lines);
    }

    view('admin/dashboard', [
        'title' => (string)cfg('labels.admin') . ' — ' . cfg('app_name'),
        'stats' => $stats,
        'series' => $series,
        'recent' => $recent,
        'todays' => $todays,
        'log' => $log,
        'area' => 'admin',
    ]);
}

function admin_records(): void
{
    Auth::requireRole('admin');
    $table = txn_table();
    $status = (string)($_GET['status'] ?? '');
    $q = trim((string)($_GET['q'] ?? ''));
    $where = ['1=1'];
    $params = [];
    if ($status !== '' && isset(cfg('status_meta')[$status])) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $where[] = '(reference LIKE ? OR customer_name LIKE ? OR customer_email LIKE ? OR choice_a LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);
    $total = (int)Db::value('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $whereSql, $params);
    $page = paginate($total, 20, (int)($_GET['page'] ?? 1));
    $rows = Db::all(
        'SELECT * FROM ' . $table . ' WHERE ' . $whereSql . ' ORDER BY request_date DESC, request_time DESC, id DESC LIMIT ' . $page['limit'] . ' OFFSET ' . $page['offset'],
        $params
    );
    view('admin/records', [
        'title' => 'All ' . cfg('labels.txn_plural') . ' — ' . cfg('app_name'),
        'rows' => $rows,
        'page' => $page,
        'status' => $status,
        'q' => $q,
        'area' => 'admin',
    ]);
}

function admin_record_status(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    $note = trim((string)($_POST['note'] ?? ''));
    $record = Db::one('SELECT * FROM ' . txn_table() . ' WHERE id = ? LIMIT 1', [$id]);
    if (!$record) {
        flash('error', 'That record no longer exists.');
        redirect('/admin/records');
    }
    if (!in_array($status, next_statuses((string)$record['status']), true)) {
        flash('error', 'That status change is not allowed from “' . status_label((string)$record['status']) . '”.');
        redirect('/admin/records');
    }
    update_record_status($record, $status, mb_substr($note, 0, 500));
    flash('success', $record['reference'] . ' is now ' . status_label($status) . '.');
    redirect('/admin/records');
}

function admin_record_paid(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    if (!cfg('modules.payments', false)) {
        http_response_code(404);
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $record = Db::one('SELECT * FROM ' . txn_table() . ' WHERE id = ? LIMIT 1', [$id]);
    if (!$record) {
        flash('error', 'That record no longer exists.');
        redirect('/admin/payments');
    }
    if ((string)$record['payment_status'] === 'paid') {
        flash('info', 'That payment is already recorded.');
        redirect('/admin/payments');
    }
    $amount = (float)$record['amount'];
    if ($amount <= 0) {
        flash('error', 'This record has no amount to capture.');
        redirect('/admin/payments');
    }
    $reference = 'PAY-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    Db::transaction(function () use ($record, $reference, $amount): void {
        Db::update(txn_table(), ['payment_status' => 'paid'], 'id = :id', ['id' => (int)$record['id']]);
        Db::insert('payments', [
            'reference' => $reference,
            'record_id' => (int)$record['id'],
            'order_id' => null,
            'amount' => $amount,
            'method' => 'manual',
            'status' => 'recorded',
            'note' => 'Recorded by ' . (Auth::user()['name'] ?? 'administrator'),
        ]);
        Db::insert('record_events', [
            'record_id' => (int)$record['id'],
            'status' => (string)$record['status'],
            'note' => 'Payment of ' . money_fmt($amount) . ' recorded (' . $reference . ').',
            'source' => 'admin',
        ]);
    });
    $body = '<p>Hello ' . e($record['customer_name']) . ',</p>'
        . '<p>We have recorded your payment of <strong>' . money_fmt($amount) . '</strong> for ' . e($record['reference']) . '.</p>'
        . '<p><strong>Receipt:</strong> ' . e($reference) . '</p>';
    Mailer::send((string)$record['customer_email'], 'Payment received — ' . $record['reference'], Mailer::template('Payment received', $body), '', 'payment_received');
    audit('payment.recorded', $reference . ' for ' . $record['reference']);
    flash('success', 'Payment ' . $reference . ' recorded.');
    redirect('/admin/payments');
}

function admin_users(): void
{
    Auth::requireRole('admin');
    $ordersCount = cfg('modules.orders', false)
        ? '(SELECT COUNT(*) FROM orders o WHERE o.customer_id = u.id)'
        : '0';
    $users = Db::all(
        'SELECT u.*,
            (SELECT COUNT(*) FROM ' . txn_table() . ' r WHERE r.customer_id = u.id) AS records,
            ' . $ordersCount . ' AS orders_count
         FROM users u ORDER BY u.created_at DESC'
    );
    view('admin/users', [
        'title' => 'People — ' . cfg('app_name'),
        'users' => $users,
        'area' => 'admin',
    ]);
}

function admin_user_toggle(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)Auth::id()) {
        flash('error', 'You cannot deactivate your own account.');
        redirect('/admin/users');
    }
    $user = Db::one('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    if (!$user) {
        flash('error', 'Account not found.');
        redirect('/admin/users');
    }
    if ($user['role'] === 'admin') {
        flash('error', 'Administrator accounts cannot be suspended from this screen.');
        redirect('/admin/users');
    }
    Db::update('users', ['is_active' => (int)$user['is_active'] === 1 ? 0 : 1], 'id = :id', ['id' => $id]);
    audit('user.toggled', $user['email'] . ' → ' . ((int)$user['is_active'] === 1 ? 'inactive' : 'active'));
    flash('success', 'Account ' . $user['email'] . ' updated.');
    redirect('/admin/users');
}

function admin_outbox(): void
{
    Auth::requireRole('admin');
    $rows = Db::all('SELECT * FROM email_outbox ORDER BY id DESC LIMIT 60');
    view('admin/outbox', [
        'title' => 'Email outbox — ' . cfg('app_name'),
        'rows' => $rows,
        'area' => 'admin',
    ]);
}

function admin_messages(): void
{
    Auth::requireRole('admin');
    if (!cfg('modules.messaging', false)) {
        http_response_code(404);
        exit;
    }
    $rows = Db::all('SELECT * FROM messages ORDER BY id DESC LIMIT 80');
    view('admin/messages', [
        'title' => 'Enquiries — ' . cfg('app_name'),
        'rows' => $rows,
        'area' => 'admin',
    ]);
}

function admin_message_handled(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    if (!cfg('modules.messaging', false)) {
        http_response_code(404);
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    Db::update('messages', ['status' => 'handled', 'handled_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
    audit('enquiry.handled', 'Message #' . $id);
    flash('success', 'Enquiry marked as handled.');
    redirect('/admin/messages');
}

function admin_products(): void
{
    Auth::requireRole('admin');
    if (!cfg('modules.orders', false)) {
        http_response_code(404);
        exit;
    }
    $products = Db::all('SELECT * FROM products ORDER BY name');
    view('admin/products', [
        'title' => 'Catalogue & stock — ' . cfg('app_name'),
        'products' => $products,
        'area' => 'admin',
    ]);
}

function admin_product_store(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    if (!cfg('modules.orders', false)) {
        http_response_code(404);
        exit;
    }
    $v = (new Validator($_POST))
        ->required('name', 'sku', 'price', 'stock')
        ->max('name', 120)
        ->max('sku', 40)
        ->integer('price', 0, 10000000)
        ->integer('stock', 0, 100000);
    if (!$v->passes()) {
        remember_errors($v->errors());
        flash('error', 'Check the product fields.');
        redirect('/admin/products');
    }
    try {
        Db::insert('products', [
            'sku' => strtoupper((string)$v->value('sku')),
            'name' => (string)$v->value('name'),
            'description' => trim((string)($_POST['description'] ?? '')),
            'price' => (float)$v->value('price'),
            'stock' => (int)$v->value('stock'),
            'is_active' => 1,
        ]);
        audit('product.created', (string)$v->value('sku'));
        flash('success', 'Product added to the catalogue.');
    } catch (Throwable $e) {
        flash('error', 'Could not add the product: ' . $e->getMessage());
    }
    redirect('/admin/products');
}

function admin_product_restock(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    if ($qty < 1 || $qty > 10000) {
        flash('error', 'Enter a restock quantity between 1 and 10,000.');
        redirect('/admin/products');
    }
    Db::run('UPDATE products SET stock = stock + ? WHERE id = ?', [$qty, $id]);
    audit('product.restocked', 'Product #' . $id . ' +' . $qty);
    flash('success', 'Stock updated.');
    redirect('/admin/products');
}

function admin_orders(): void
{
    Auth::requireRole('admin');
    if (!cfg('modules.orders', false)) {
        http_response_code(404);
        exit;
    }
    $orders = Db::all(
        'SELECT o.*, (SELECT GROUP_CONCAT(CONCAT(oi.product_name, " × ", oi.quantity) SEPARATOR ", ") FROM order_items oi WHERE oi.order_id = o.id) AS items
         FROM orders o ORDER BY o.id DESC LIMIT 80'
    );
    view('admin/orders', [
        'title' => 'Orders — ' . cfg('app_name'),
        'orders' => $orders,
        'area' => 'admin',
    ]);
}

function admin_order_status(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    if (!cfg('modules.orders', false)) {
        http_response_code(404);
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    $allowed = [
        'pending' => ['ready', 'cancelled'],
        'ready' => ['collected', 'cancelled'],
        'collected' => [],
        'cancelled' => [],
    ];
    $order = Db::one('SELECT * FROM orders WHERE id = ? LIMIT 1', [$id]);
    if (!$order) {
        flash('error', 'Order not found.');
        redirect('/admin/orders');
    }
    $next = $allowed[$order['status']] ?? [];
    if (!in_array($status, $next, true)) {
        flash('error', 'That order status change is not allowed.');
        redirect('/admin/orders');
    }
    Db::update('orders', ['status' => $status], 'id = :id', ['id' => $id]);
    $body = '<p>Hello ' . e($order['customer_name']) . ',</p>'
        . '<p>Your order <strong>' . e($order['reference']) . '</strong> is now <strong>' . e(ucfirst($status)) . '</strong>.</p>'
        . '<p><strong>Total:</strong> ' . money_fmt((float)$order['total']) . '</p>';
    Mailer::send((string)$order['customer_email'], 'Order ' . $status . ' — ' . $order['reference'], Mailer::template('Order update', $body), '', 'order_' . $status);
    audit('order.status', $order['reference'] . ' → ' . $status);
    flash('success', 'Order ' . $order['reference'] . ' marked ' . $status . '.');
    redirect('/admin/orders');
}

function admin_reports(): void
{
    Auth::requireRole('admin');
    if (!cfg('modules.reports', false)) {
        http_response_code(404);
        exit;
    }
    $table = txn_table();
    $statusCounts = Db::all('SELECT status, COUNT(*) AS total FROM ' . $table . ' GROUP BY status ORDER BY total DESC');
    $series = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $series[] = [
            'label' => date('D', strtotime($date)),
            'count' => (int)Db::value('SELECT COUNT(*) FROM ' . $table . ' WHERE request_date = ?', [$date]),
        ];
    }
    $demand = Db::all('SELECT choice_a, COUNT(*) AS total, COALESCE(SUM(amount),0) AS value FROM ' . $table . ' GROUP BY choice_a ORDER BY total DESC LIMIT 6');
    $revenue = cfg('modules.payments', false)
        ? (float)Db::value("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'recorded'")
        : (float)Db::value('SELECT COALESCE(SUM(amount),0) FROM ' . $table . " WHERE payment_status = 'paid'");
    $completed = 0;
    foreach ($statusCounts as $row) {
        $completed += empty(cfg('status_meta.' . $row['status'] . '.next')) ? (int)$row['total'] : 0;
    }
    $extras = [];
    if (cfg('modules.orders', false)) {
        $extras['orders'] = (int)Db::value('SELECT COUNT(*) FROM orders');
        $extras['order_value'] = (float)Db::value('SELECT COALESCE(SUM(total),0) FROM orders');
        $extras['stock_value'] = (float)Db::value('SELECT COALESCE(SUM(price * stock),0) FROM products WHERE is_active = 1');
    }
    if (cfg('modules.messaging', false)) {
        $extras['messages'] = (int)Db::value('SELECT COUNT(*) FROM messages');
        $extras['messages_new'] = (int)Db::value("SELECT COUNT(*) FROM messages WHERE status = 'new'");
    }
    view('admin/reports', [
        'title' => 'Reports — ' . cfg('app_name'),
        'statusCounts' => $statusCounts,
        'series' => $series,
        'demand' => $demand,
        'revenue' => $revenue,
        'completed' => $completed,
        'extras' => $extras,
        'area' => 'admin',
    ]);
}

function admin_payments(): void
{
    Auth::requireRole('admin');
    if (!cfg('modules.payments', false)) {
        http_response_code(404);
        exit;
    }
    $payments = Db::all('SELECT * FROM payments ORDER BY id DESC LIMIT 80');
    $unpaid = Db::all('SELECT * FROM ' . txn_table() . " WHERE payment_status = 'unpaid' ORDER BY request_date ASC LIMIT 30");
    $total = (float)Db::value("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'recorded'");
    view('admin/payments', [
        'title' => 'Payments — ' . cfg('app_name'),
        'payments' => $payments,
        'unpaid' => $unpaid,
        'total' => $total,
        'area' => 'admin',
    ]);
}

function admin_run_automation(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $ran = Automation::run((string)cfg('app_name'));
    audit('automation.run', $ran . ' task(s)');
    flash('success', 'Automation ran: ' . $ran . ' task(s). See storage/automation.log for the line-by-line result.');
    redirect('/admin');
}
