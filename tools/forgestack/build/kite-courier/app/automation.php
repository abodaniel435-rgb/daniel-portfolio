<?php
/**
 * Automation — run from cPanel cron (cron/run.php) or the admin console.
 * Each task returns a short line that is written to storage/automation.log.
 */
declare(strict_types=1);

$tasks = [];

$tasks['upcoming_reminders'] = function (): string {
    $table = txn_table();
    $statuses = (array)cfg('reminder_statuses', []);
    if (!$statuses) {
        return 'no reminder statuses configured';
    }
    $in = implode(',', array_fill(0, count($statuses), '?'));
    $params = $statuses;
    $params[] = date('Y-m-d', strtotime('+1 day'));
    $rows = Db::all(
        'SELECT * FROM ' . $table . ' WHERE status IN (' . $in . ') AND request_date = ? AND reminder_sent_at IS NULL',
        $params
    );
    foreach ($rows as $row) {
        $body = '<p>Hello ' . e($row['customer_name']) . ',</p>'
            . '<p>This is a reminder for your ' . e((string)cfg('labels.txn_one')) . ' tomorrow at <strong>'
            . e(formatted_time($row['request_time'])) . '</strong>.</p>'
            . '<p><strong>Reference:</strong> ' . e($row['reference']) . '<br>'
            . '<strong>' . e((string)cfg('labels.choice_a')) . ':</strong> ' . e($row['choice_a']) . '<br>'
            . '<strong>' . e((string)cfg('labels.choice_b')) . ':</strong> ' . e($row['choice_b']) . '</p>'
            . '<p>If you need to move it, reply to this email or call ' . e((string)cfg('contact.phone')) . '.</p>';
        Mailer::send((string)$row['customer_email'], 'Reminder — ' . $row['reference'], Mailer::template('Reminder for tomorrow', $body), '', 'reminder');
        Db::update($table, ['reminder_sent_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int)$row['id']]);
    }
    return count($rows) . ' reminder(s) queued';
};

$tasks['close_past_requests'] = function (): string {
    $table = txn_table();
    $open = [];
    foreach ((array)cfg('status_meta', []) as $key => $meta) {
        if (!empty($meta['next'])) {
            $open[] = $key;
        }
    }
    if (!$open) {
        return 'nothing to close';
    }
    $in = implode(',', array_fill(0, count($open), '?'));
    $rows = Db::all('SELECT * FROM ' . $table . ' WHERE status IN (' . $in . ') AND request_date < CURDATE()', $open);
    $closed = (string)cfg('close_past_status', 'completed');
    foreach ($rows as $row) {
        Db::update($table, ['status' => $closed], 'id = :id', ['id' => (int)$row['id']]);
        Db::insert('record_events', [
            'record_id' => (int)$row['id'],
            'status' => $closed,
            'note' => 'Closed automatically after the requested date passed.',
            'source' => 'system',
        ]);
        $row['status'] = $closed;
        $body = '<p>Hello ' . e($row['customer_name']) . ',</p>'
            . '<p>Your ' . e((string)cfg('labels.txn_one')) . ' <strong>' . e($row['reference']) . '</strong> was closed because the requested date has passed and it was not completed.</p>'
            . '<p>If this is a mistake, reply and the team will reopen it.</p>';
        Mailer::send((string)$row['customer_email'], 'Closed — ' . $row['reference'], Mailer::template('Request closed', $body), '', 'auto_closed');
    }
    return count($rows) . ' past request(s) closed as ' . $closed;
};

$tasks['daily_digest'] = function (): string {
    $admin = Db::one("SELECT email, name FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
    if (!$admin) {
        return 'no active administrator';
    }
    $table = txn_table();
    $open = [];
    foreach ((array)cfg('status_meta', []) as $key => $meta) {
        if (!empty($meta['next'])) {
            $open[] = $key;
        }
    }
    $openIn = $open ? implode(',', array_fill(0, count($open), '?')) : "'__none__'";
    $today = (int)Db::value('SELECT COUNT(*) FROM ' . $table . ' WHERE request_date = CURDATE()');
    $pending = (int)Db::value('SELECT COUNT(*) FROM ' . $table . ' WHERE status IN (' . $openIn . ')', $open);
    $failed = (int)Db::value("SELECT COUNT(*) FROM email_outbox WHERE status = 'failed'");
    $lines = '<li>' . $today . ' request(s) scheduled today</li><li>' . $pending . ' open request(s)</li><li>' . $failed . ' failed email(s)</li>';
    if (cfg('modules.orders', false)) {
        $lines .= '<li>' . (int)Db::value("SELECT COUNT(*) FROM orders WHERE status = 'pending'") . ' pending order(s)</li>';
        $lines .= '<li>' . (int)Db::value('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock <= 5') . ' low-stock item(s)</li>';
    }
    if (cfg('modules.messaging', false)) {
        $lines .= '<li>' . (int)Db::value("SELECT COUNT(*) FROM messages WHERE status = 'new'") . ' new enquiry(ies)</li>';
    }
    $body = '<p>Here is the daily summary for ' . e((string)cfg('app_name')) . '.</p><ul>' . $lines . '</ul>';
    Mailer::send((string)$admin['email'], 'Daily digest — ' . date('D, M j'), Mailer::template('Daily operations digest', $body, 'Open the desk', url('/admin')), '', 'digest');
    return 'digest queued for ' . $admin['email'];
};

if (cfg('modules.orders', false)) {
    $tasks['low_stock_alert'] = function (): string {
        $low = Db::all('SELECT name, sku, stock FROM products WHERE is_active = 1 AND stock <= 5 ORDER BY stock');
        if (!$low) {
            return 'stock levels healthy';
        }
        $already = (int)Db::value("SELECT COUNT(*) FROM email_outbox WHERE message_type = 'low_stock' AND created_at >= CURDATE()");
        if ($already > 0) {
            return 'low-stock alert already sent today';
        }
        $items = '';
        foreach ($low as $item) {
            $items .= '<li>' . e($item['name']) . ' (' . e($item['sku']) . ') — ' . (int)$item['stock'] . ' left</li>';
        }
        $admin = Db::one("SELECT email FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
        if (!$admin) {
            return 'no active administrator';
        }
        Mailer::send((string)$admin['email'], 'Low stock — ' . count($low) . ' item(s)',
            Mailer::template('Low stock alert', '<p>These items need restocking:</p><ul>' . $items . '</ul>', 'Manage stock', url('/admin/products')),
            '', 'low_stock');
        return count($low) . ' low-stock item(s) flagged';
    };
}

if (cfg('modules.payments', false)) {
    $tasks['payment_followup'] = function (): string {
        $table = txn_table();
        $rows = Db::all(
            "SELECT * FROM " . $table . " WHERE payment_status = 'unpaid' AND request_date < CURDATE() AND payment_reminder_sent_at IS NULL LIMIT 20"
        );
        foreach ($rows as $row) {
            $body = '<p>Hello ' . e($row['customer_name']) . ',</p>'
                . '<p>Our records show <strong>' . money_fmt((float)$row['amount']) . '</strong> outstanding for ' . e($row['reference']) . ' on ' . e(short_date($row['request_date'])) . '.</p>'
                . '<p>If you have already paid, reply with the receipt and we will update the ledger.</p>';
            Mailer::send((string)$row['customer_email'], 'Payment reminder — ' . $row['reference'], Mailer::template('A gentle payment reminder', $body), '', 'payment_reminder');
            Db::update($table, ['payment_reminder_sent_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int)$row['id']]);
        }
        return count($rows) . ' payment reminder(s) queued';
    };
}

return $tasks;
