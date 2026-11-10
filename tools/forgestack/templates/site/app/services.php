<?php
/**
 * Domain services. All SQL goes through Db::* prepared statements.
 */
declare(strict_types=1);

function site_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require BASE_PATH . '/app/config.php';
    }
    return $config;
}

function cfg(string $path, mixed $default = null): mixed
{
    $cursor = site_config();
    foreach (explode('.', $path) as $key) {
        if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
            return $default;
        }
        $cursor = $cursor[$key];
    }
    return $cursor;
}

function txn_table(): string
{
    $table = (string)cfg('labels.txn_table', 'records');
    return preg_match('/^[a-z0-9_]+$/', $table) ? $table : 'records';
}

function money_fmt(float|int|string $amount): string
{
    return '₦' . number_format((float)$amount, 0);
}

function record_reference(): string
{
    return strtoupper((string)cfg('labels.txn_ref', 'REF')) . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function choice_price(string $choice): float
{
    foreach ((array)cfg('form.choices_a', []) as $option) {
        if (($option['value'] ?? '') === $choice) {
            return (float)($option['price'] ?? 0);
        }
    }
    return 0.0;
}

function quantity_unit(): string
{
    return (string)cfg('labels.quantity_unit', '');
}

function status_label(string $status): string
{
    return (string)cfg('status_meta.' . $status . '.label', ucfirst(str_replace('_', ' ', $status)));
}

function status_badge_html(string $status): string
{
    $tone = (string)cfg('status_meta.' . $status . '.tone', 'neutral');
    return '<span class="badge badge-' . e($tone) . '">' . e(status_label($status)) . '</span>';
}

function next_statuses(string $status): array
{
    return (array)cfg('status_meta.' . $status . '.next', []);
}

function formatted_time(?string $time): string
{
    if (!$time) {
        return '—';
    }
    // Delivery profiles use windows such as 10:00–12:00; show those verbatim.
    if (preg_match('/[–-]/', $time)) {
        return $time;
    }
    $stamp = strtotime($time);
    return $stamp ? date('g:i A', $stamp) : $time;
}

function record_admin_url(): string
{
    return url('/admin/records');
}

function notify_admin(string $subject, string $heading, string $bodyHtml, string $ctaLabel = '', string $ctaUrl = '', string $type = 'admin_notice'): void
{
    $admin = Db::one("SELECT email FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
    if (!$admin) {
        return;
    }
    Mailer::send($admin['email'], $subject, Mailer::template($heading, $bodyHtml, $ctaLabel, $ctaUrl), '', $type);
}

/** Insert a request, its first timeline event and both notifications. */
function create_record(array $input): array
{
    $reference = record_reference();
    $choiceA = (string)$input['choice_a'];
    $amount = choice_price($choiceA);
    $paymentsOn = (bool)cfg('modules.payments', false);
    $paymentStatus = $paymentsOn && $amount > 0 ? 'unpaid' : 'not_required';
    $status = (string)cfg('initial_status', 'pending');

    $id = Db::insert(txn_table(), [
        'reference' => $reference,
        'customer_id' => Auth::id(),
        'customer_name' => $input['customer_name'],
        'customer_email' => $input['customer_email'],
        'customer_phone' => $input['customer_phone'],
        'request_date' => $input['request_date'],
        'request_time' => $input['request_time'],
        'choice_a' => $choiceA,
        'choice_b' => (string)($input['choice_b'] ?? ''),
        'quantity' => (int)($input['quantity'] ?? 1),
        'notes' => (string)($input['notes'] ?? ''),
        'amount' => $amount,
        'payment_status' => $paymentStatus,
        'status' => $status,
    ]);

    Db::insert('record_events', [
        'record_id' => $id,
        'status' => $status,
        'note' => 'Request received online.',
        'source' => 'customer',
    ]);

    $record = Db::one('SELECT * FROM ' . txn_table() . ' WHERE id = ? LIMIT 1', [$id]);
    notify_record_created($record);
    audit('record.created', $reference);
    return $record;
}

function record_when(array $record): string
{
    return short_date($record['request_date']) . ' at ' . formatted_time($record['request_time']);
}

function notify_record_created(array $record): void
{
    $name = (string)$record['customer_name'];
    $body = '<p>Hello ' . e($name) . ',</p>'
        . '<p>We have received your ' . e((string)cfg('labels.txn_one')) . ' request. The team reviews every request and will confirm by email.</p>'
        . '<p><strong>Reference:</strong> ' . e($record['reference']) . '<br>'
        . '<strong>When:</strong> ' . e(record_when($record)) . '<br>'
        . '<strong>' . e((string)cfg('labels.choice_a')) . ':</strong> ' . e($record['choice_a']) . '<br>'
        . '<strong>' . e((string)cfg('labels.choice_b')) . ':</strong> ' . e($record['choice_b'] ?: 'First available') . '</p>'
        . '<p>You can follow the status at any time using the reference above.</p>';

    Mailer::send(
        (string)$record['customer_email'],
        (string)cfg('labels.txn_one') . ' request received — ' . $record['reference'],
        Mailer::template('We received your request', $body, (string)cfg('labels.track'), url('/track?ref=' . urlencode((string)$record['reference']))),
        '',
        'request_received'
    );

    $adminBody = '<p>A new ' . e((string)cfg('labels.txn_one')) . ' request was submitted.</p>'
        . '<p><strong>' . e($record['customer_name']) . '</strong><br>'
        . e($record['customer_email']) . '<br>' . e($record['customer_phone']) . '</p>'
        . '<p><strong>Reference:</strong> ' . e($record['reference']) . '<br>'
        . '<strong>When:</strong> ' . e(record_when($record)) . '<br>'
        . '<strong>' . e((string)cfg('labels.choice_a')) . ':</strong> ' . e($record['choice_a']) . ' · ' . e($record['choice_b'] ?: 'First available') . '<br>'
        . '<strong>' . e((string)cfg('labels.quantity')) . ':</strong> ' . (int)$record['quantity'] . ' ' . e(quantity_unit()) . '<br>'
        . '<strong>Notes:</strong> ' . ($record['notes'] !== '' ? e($record['notes']) : '—') . '</p>';
    notify_admin(
        'New ' . cfg('labels.txn_one') . ' — ' . $record['reference'],
        'New ' . cfg('labels.txn_one') . ' request',
        $adminBody,
        'Open the desk',
        record_admin_url(),
        'admin_new_record'
    );
}

function find_record_by_reference(string $reference): ?array
{
    $reference = strtoupper(trim($reference));
    if ($reference === '') {
        return null;
    }
    return Db::one('SELECT * FROM ' . txn_table() . ' WHERE reference = ? LIMIT 1', [$reference]);
}

function record_timeline(int $recordId): array
{
    return Db::all('SELECT status, note, source, created_at FROM record_events WHERE record_id = ? ORDER BY id', [$recordId]);
}

function update_record_status(array $record, string $status, string $note = ''): void
{
    if (!isset(cfg('status_meta')[$status])) {
        throw new RuntimeException('Unknown status.');
    }
    if ((string)$record['status'] === $status) {
        return;
    }
    Db::update(txn_table(), ['status' => $status], 'id = :id', ['id' => (int)$record['id']]);
    Db::insert('record_events', [
        'record_id' => (int)$record['id'],
        'status' => $status,
        'note' => $note !== '' ? $note : 'Status updated by the team.',
        'source' => 'admin',
    ]);
    $record['status'] = $status;
    notify_record_status($record, $status, $note);
    audit('record.status', $record['reference'] . ' → ' . $status);
}

function notify_record_status(array $record, string $status, string $note = ''): void
{
    $heading = status_label($status);
    $body = '<p>Hello ' . e($record['customer_name']) . ',</p>'
        . '<p>Your ' . e((string)cfg('labels.txn_one')) . ' <strong>' . e($record['reference']) . '</strong> is now marked <strong>' . e($heading) . '</strong>.</p>'
        . '<p><strong>When:</strong> ' . e(record_when($record)) . '<br>'
        . '<strong>' . e((string)cfg('labels.choice_a')) . ':</strong> ' . e($record['choice_a']) . '</p>'
        . ($note !== '' ? '<p><strong>Note from the team:</strong> ' . e($note) . '</p>' : '')
        . '<p>You can view the full timeline from the tracking page.</p>';

    Mailer::send(
        (string)$record['customer_email'],
        status_label($status) . ' — ' . $record['reference'],
        Mailer::template($heading, $body, (string)cfg('labels.track'), url('/track?ref=' . urlencode((string)$record['reference']))),
        '',
        'status_' . $status
    );
}

/** Customer records visible in the portal: linked account or matching email. */
function customer_records(int $userId, string $email): array
{
    return Db::all(
        'SELECT * FROM ' . txn_table() . ' WHERE customer_id = ? OR customer_email = ? ORDER BY request_date DESC, id DESC LIMIT 40',
        [$userId, strtolower($email)]
    );
}

function customer_orders(int $userId, string $email): array
{
    if (!cfg('modules.orders', false)) {
        return [];
    }
    return Db::all(
        'SELECT * FROM orders WHERE customer_id = ? OR customer_email = ? ORDER BY id DESC LIMIT 30',
        [$userId, strtolower($email)]
    );
}

/**
 * Create an order and decrement stock inside one transaction.
 * Returns the order row; throws when stock is insufficient.
 */
function create_order(int $productId, int $quantity, array $buyer): array
{
    if ($quantity < 1 || $quantity > 20) {
        throw new RuntimeException('Choose a quantity between 1 and 20.');
    }
    return Db::transaction(function () use ($productId, $quantity, $buyer): array {
        $product = Db::one('SELECT * FROM products WHERE id = ? AND is_active = 1 FOR UPDATE', [$productId]);
        if (!$product) {
            throw new RuntimeException('That item is no longer available.');
        }
        if ((int)$product['stock'] < $quantity) {
            throw new RuntimeException('Only ' . (int)$product['stock'] . ' left in stock.');
        }
        $updated = Db::run(
            'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
            [$quantity, $productId, $quantity]
        )->rowCount();
        if ($updated !== 1) {
            throw new RuntimeException('Stock changed while you were ordering. Please try again.');
        }

        $reference = 'ORD-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $total = (float)$product['price'] * $quantity;
        $orderId = Db::insert('orders', [
            'reference' => $reference,
            'customer_id' => Auth::id(),
            'customer_name' => $buyer['name'],
            'customer_email' => strtolower($buyer['email']),
            'customer_phone' => $buyer['phone'],
            'total' => $total,
            'status' => 'pending',
        ]);
        Db::insert('order_items', [
            'order_id' => $orderId,
            'product_id' => $productId,
            'product_name' => $product['name'],
            'unit_price' => $product['price'],
            'quantity' => $quantity,
            'line_total' => $total,
        ]);

        $order = Db::one('SELECT * FROM orders WHERE id = ? LIMIT 1', [$orderId]);
        $body = '<p>Hello ' . e($buyer['name']) . ',</p>'
            . '<p>Your order <strong>' . e($reference) . '</strong> is confirmed.</p>'
            . '<p><strong>' . e($product['name']) . '</strong> × ' . $quantity . '<br>'
            . '<strong>Total:</strong> ' . money_fmt($total) . '</p>'
            . '<p>We will email you when it is ready for collection.</p>';
        Mailer::send($buyer['email'], 'Order confirmed — ' . $reference, Mailer::template('Order confirmed', $body), '', 'order_received');

        $adminBody = '<p>New order <strong>' . e($reference) . '</strong> from ' . e($buyer['name']) . ' (' . e($buyer['email']) . ').</p>'
            . '<p>' . e($product['name']) . ' × ' . $quantity . ' — ' . money_fmt($total) . '</p>';
        notify_admin('New order — ' . $reference, 'New order received', $adminBody, 'Open orders', url('/admin/orders'), 'admin_new_order');
        audit('order.created', $reference);
        return $order;
    });
}
