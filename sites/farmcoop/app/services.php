<?php
declare(strict_types=1);

/** Produce categories used across listings, filters and admin tools. */
function produce_categories(): array
{
    return [
        'vegetables' => 'Vegetables',
        'grains' => 'Grains & staples',
        'tubers' => 'Tubers & roots',
        'fruits' => 'Fruits',
        'spices' => 'Spices & herbs',
        'other' => 'Other',
    ];
}

function produce_units(): array
{
    return ['kg', 'crate', 'basket'];
}

function delivery_methods(): array
{
    return ['pickup' => 'Farm pickup', 'delivery' => 'Delivery to my address'];
}

function nigerian_states(): array
{
    static $states = null;
    if ($states === null) {
        $states = array_column(Db::all('SELECT name FROM states ORDER BY sort_order, name'), 'name');
    }
    return $states;
}

/** Badge markup tuned for marketplace + order statuses. */
function fc_badge(string $status): string
{
    $map = [
        'open' => 'ok', 'paused' => 'warn', 'reserved' => 'info', 'sold' => 'neutral', 'expired' => 'bad',
        'pending' => 'warn', 'accepted' => 'info', 'ready' => 'info', 'completed' => 'ok',
        'rejected' => 'bad', 'cancelled' => 'bad',
    ];
    $tone = $map[$status] ?? 'neutral';
    return '<span class="badge badge-' . e($tone) . '">' . e(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

function fc_qty(float|int|string $value, string $unit = ''): string
{
    $formatted = rtrim(rtrim(number_format((float)$value, 2, '.', ','), '0'), '.');
    return $formatted . ($unit !== '' ? ' ' . $unit : '');
}

/** Parses a positive decimal from form input; returns null when invalid. */
function fc_number(string $raw, float $min, float $max): ?float
{
    $raw = str_replace([',', ' '], '', trim($raw));
    if ($raw === '' || !is_numeric($raw)) {
        return null;
    }
    $value = (float)$raw;
    if ($value < $min || $value > $max) {
        return null;
    }
    return round($value, 2);
}

function fc_is_url(string $url): bool
{
    if ($url === '') {
        return true;
    }
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function category_label(?string $slug): string
{
    $categories = produce_categories();
    return $categories[$slug ?? ''] ?? 'Produce';
}

/** Fallback imagery per category, used when a listing has no photo. */
function category_image(string $category): string
{
    $map = [
        'vegetables' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?auto=format&fit=crop&w=1200&q=80',
        'grains' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=1200&q=80',
        'tubers' => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=1200&q=80',
        'fruits' => 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=1200&q=80',
        'spices' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=1200&q=80',
        'other' => 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=1200&q=80',
    ];
    return $map[$category] ?? $map['other'];
}

function listing_image(array $listing): string
{
    return $listing['image_url'] ?: category_image((string)$listing['category']);
}

/** Full listing row joined with the farmer's profile + buyer-facing farm details. */
function listing_context(int $id): ?array
{
    return Db::one(
        "SELECT l.*, u.name AS farmer_name, u.email AS farmer_email, u.phone AS farmer_phone,
                f.id AS farm_id, f.farm_name, f.state AS farm_state, f.lga AS farm_lga,
                f.produce_focus, f.about AS farm_about, f.photo_url AS farm_photo, f.is_verified
         FROM listings l
         JOIN users u ON u.id = l.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = l.farmer_id
         WHERE l.id = ? LIMIT 1",
        [$id]
    );
}

function farmer_profile(int $userId): ?array
{
    return Db::one('SELECT * FROM farm_profiles WHERE user_id = ? LIMIT 1', [$userId]);
}

/** Active = an order still moving through the pipeline. */
function order_context(int $id): ?array
{
    return Db::one(
        "SELECT o.*, l.produce_name, l.category, l.pickup_window, l.image_url, l.location AS listing_location,
                l.state AS listing_state, l.lga AS listing_lga,
                b.name AS buyer_name, b.email AS buyer_email, b.phone AS buyer_phone,
                fu.name AS farmer_name, fu.email AS farmer_email, fu.phone AS farmer_phone,
                f.farm_name, f.state AS farm_state, f.lga AS farm_lga, f.is_verified
         FROM orders o
         JOIN listings l ON l.id = o.listing_id
         JOIN users b ON b.id = o.buyer_id
         JOIN users fu ON fu.id = o.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = o.farmer_id
         WHERE o.id = ? LIMIT 1",
        [$id]
    );
}

function order_events(int $orderId): array
{
    return Db::all('SELECT * FROM order_events WHERE order_id = ? ORDER BY created_at, id', [$orderId]);
}

function order_log(int $orderId, string $status, string $note = '', ?int $actorId = null): void
{
    Db::insert('order_events', [
        'order_id' => $orderId,
        'status' => $status,
        'note' => mb_substr($note, 0, 250),
        'actor_id' => $actorId,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

/** Orders that still hold stock on a listing. */
function active_order_statuses(): array
{
    return ['pending', 'accepted', 'ready'];
}

/** Open orders for a listing (used before delete / expire decisions). */
function listing_active_orders(int $listingId): int
{
    return (int)Db::value(
        "SELECT COUNT(*) FROM orders WHERE listing_id = ? AND status IN ('pending','accepted','ready')",
        [$listingId]
    );
}

function farmer_earnings(int $farmerId): array
{
    $row = Db::one(
        "SELECT
            COALESCE(SUM(CASE WHEN status IN ('accepted','ready','completed') THEN total_amount ELSE 0 END), 0) AS committed,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) AS settled,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END), 0) AS pipeline
         FROM orders WHERE farmer_id = ?",
        [$farmerId]
    );
    return [
        'committed' => (float)($row['committed'] ?? 0),
        'settled' => (float)($row['settled'] ?? 0),
        'pipeline' => (float)($row['pipeline'] ?? 0),
    ];
}

/* ------------------------------------------------------------------ *
 *  Transactional stock handling
 * ------------------------------------------------------------------ */

/**
 * Release stock back onto a listing and reopen it if it had sold out.
 * Must be called inside an open transaction.
 */
function release_listing_stock(int $listingId, float $quantity): void
{
    $listing = Db::one('SELECT id, quantity, quantity_listed, status FROM listings WHERE id = ? FOR UPDATE', [$listingId]);
    if (!$listing) {
        return;
    }
    $restored = min((float)$listing['quantity_listed'], (float)$listing['quantity'] + $quantity);
    $status = $listing['status'];
    if (in_array($status, ['sold', 'reserved'], true) && $restored > 0) {
        $status = 'open';
    }
    Db::update('listings', ['quantity' => $restored, 'status' => $status], 'id = :id', ['id' => $listingId]);
}

/* ------------------------------------------------------------------ *
 *  Email notifications
 * ------------------------------------------------------------------ */

function notify_order_placed(array $order): void
{
    $buyerBody = '<p>Hello ' . e($order['buyer_name']) . ',</p>'
        . '<p>Your order for <strong>' . e(fc_qty((float)$order['quantity'], $order['unit'])) . ' of '
        . e($order['produce_name']) . '</strong> has been sent to ' . e($order['farm_name'] ?: $order['farmer_name']) . '.</p>'
        . '<p><strong>Reference:</strong> ' . e($order['reference']) . '<br>'
        . '<strong>Total:</strong> ' . e(money((float)$order['total_amount'])) . '<br>'
        . '<strong>Handover:</strong> ' . e(ucfirst($order['delivery_method'])) . ' on ' . e(short_date($order['requested_date'])) . '</p>'
        . '<p>The farmer usually responds within 24 hours. You can follow every status change from your buyer dashboard.</p>';
    Mailer::send($order['buyer_email'], 'Order received — ' . $order['reference'],
        Mailer::template('We received your order', $buyerBody, 'Track this order', url('/buyer/orders/' . $order['id'])), '', 'order_placed_buyer');

    $farmerBody = '<p>Hello ' . e($order['farmer_name']) . ',</p>'
        . '<p><strong>' . e($order['buyer_name']) . '</strong> ordered ' . e(fc_qty((float)$order['quantity'], $order['unit']))
        . ' of <strong>' . e($order['produce_name']) . '</strong> for ' . e(money((float)$order['total_amount'])) . '.</p>'
        . '<p><strong>Reference:</strong> ' . e($order['reference']) . '<br>'
        . '<strong>Requested date:</strong> ' . e(short_date($order['requested_date'])) . '<br>'
        . '<strong>Handover:</strong> ' . e(ucfirst($order['delivery_method']))
        . ($order['delivery_address'] !== '' ? '<br><strong>Address:</strong> ' . e($order['delivery_address']) : '') . '</p>'
        . ($order['notes'] !== '' ? '<p><strong>Buyer notes:</strong> ' . e($order['notes']) . '</p>' : '')
        . '<p>Accept or reject this order from your farmer dashboard. Stock is already held for this buyer.</p>';
    Mailer::send($order['farmer_email'], 'New order — ' . $order['reference'],
        Mailer::template('New order for your harvest', $farmerBody, 'Open farmer dashboard', url('/farmer/orders')), '', 'order_placed_farmer');
}

function notify_order_status(array $order, string $status): void
{
    $copy = [
        'accepted' => ['Order accepted', 'Your order has been accepted. The farmer is preparing it for handover.'],
        'rejected' => ['Order declined', 'The farmer could not fulfil this order. The produce is back on the market and you have not been charged.'],
        'ready' => ['Order ready', 'Your produce is packed and ready. Please arrive within the pickup window.'],
        'completed' => ['Order completed', 'This order is now closed. Thank you for buying directly from the farm.'],
        'cancelled' => ['Order cancelled', 'You cancelled this order. The produce has been returned to the marketplace.'],
    ];
    if (!isset($copy[$status])) {
        return;
    }
    [$heading, $line] = $copy[$status];
    $body = '<p>Hello ' . e($order['buyer_name']) . ',</p><p>' . e($line) . '</p>'
        . '<p><strong>Reference:</strong> ' . e($order['reference']) . '<br>'
        . '<strong>Produce:</strong> ' . e(fc_qty((float)$order['quantity'], $order['unit'])) . ' · ' . e($order['produce_name']) . '<br>'
        . '<strong>Farm:</strong> ' . e($order['farm_name'] ?: $order['farmer_name']) . '<br>'
        . '<strong>Handover:</strong> ' . e(ucfirst($order['delivery_method'])) . ' · ' . e(short_date($order['requested_date'])) . '</p>';
    Mailer::send(
        $order['buyer_email'],
        $heading . ' — ' . $order['reference'],
        Mailer::template($heading, $body, 'View order', url('/buyer/orders/' . $order['id'])),
        '',
        'order_' . $status
    );
}

/** Public URL for an order tracking lookup. */
function tracking_url(array $order): string
{
    return url('/track?ref=' . urlencode((string)$order['reference']));
}
