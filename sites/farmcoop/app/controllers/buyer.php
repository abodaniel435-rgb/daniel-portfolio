<?php
declare(strict_types=1);

/* ------------------------------------------------------------------ *
 *  Buyer workspace
 * ------------------------------------------------------------------ */

function buyer_dashboard(): void
{
    Auth::requireRole('buyer');
    $buyerId = (int)Auth::id();

    $stats = [
        'active' => (int)Db::value("SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status IN ('pending','accepted','ready')", [$buyerId]),
        'pending' => (int)Db::value("SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status = 'pending'", [$buyerId]),
        'completed' => (int)Db::value("SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status = 'completed'", [$buyerId]),
        'spend' => (float)Db::value("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE buyer_id = ? AND status IN ('accepted','ready','completed')", [$buyerId]),
    ];

    $orders = Db::all(
        "SELECT o.*, l.produce_name, l.pickup_window, l.image_url, l.category, f.farm_name, fu.name AS farmer_name
         FROM orders o
         JOIN listings l ON l.id = o.listing_id
         JOIN users fu ON fu.id = o.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = o.farmer_id
         WHERE o.buyer_id = ?
         ORDER BY FIELD(o.status, 'pending', 'accepted', 'ready', 'completed', 'rejected', 'cancelled'), o.created_at DESC
         LIMIT 6",
        [$buyerId]
    );

    $recommended = Db::all(
        "SELECT l.*, f.farm_name, f.is_verified FROM listings l
         LEFT JOIN farm_profiles f ON f.user_id = l.farmer_id
         WHERE l.status = 'open' AND l.quantity > 0
         ORDER BY l.available_from ASC, l.created_at DESC LIMIT 3"
    );

    view('buyer/dashboard', [
        'title' => 'Buyer dashboard — FarmCoop',
        'stats' => $stats,
        'orders' => $orders,
        'recommended' => $recommended,
        'area' => 'buyer',
    ]);
}

function buyer_orders(): void
{
    Auth::requireRole('buyer');
    $status = (string)($_GET['status'] ?? '');
    $where = ['o.buyer_id = ?'];
    $params = [Auth::id()];
    if (in_array($status, ['pending', 'accepted', 'ready', 'completed', 'rejected', 'cancelled'], true)) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    } else {
        $status = '';
    }
    $orders = Db::all(
        "SELECT o.*, l.produce_name, l.pickup_window, l.image_url, l.category, f.farm_name, fu.name AS farmer_name
         FROM orders o
         JOIN listings l ON l.id = o.listing_id
         JOIN users fu ON fu.id = o.farmer_id
         LEFT JOIN farm_profiles f ON f.user_id = o.farmer_id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY FIELD(o.status, 'pending', 'accepted', 'ready', 'completed', 'rejected', 'cancelled'), o.created_at DESC
         LIMIT 60",
        $params
    );
    $counts = Db::one(
        "SELECT SUM(status = 'pending') AS pending, SUM(status = 'accepted') AS accepted,
                SUM(status = 'ready') AS ready, SUM(status = 'completed') AS completed
         FROM orders WHERE buyer_id = ?",
        [Auth::id()]
    );
    view('buyer/orders', [
        'title' => 'My orders — FarmCoop',
        'orders' => $orders,
        'status' => $status,
        'counts' => $counts ?: ['pending' => 0, 'accepted' => 0, 'ready' => 0, 'completed' => 0],
        'area' => 'buyer',
    ]);
}

function buyer_order_show(): void
{
    Auth::requireRole('buyer');
    $order = order_context((int)($_GET['id'] ?? 0));
    if (!$order || (int)$order['buyer_id'] !== (int)Auth::id()) {
        http_response_code(404);
        view('errors/404', ['title' => 'Order not found']);
        return;
    }
    view('buyer/order', [
        'title' => 'Order ' . $order['reference'] . ' — FarmCoop',
        'order' => $order,
        'events' => order_events((int)$order['id']),
        'area' => 'buyer',
    ]);
}

function buyer_order_cancel(): void
{
    Auth::requireRole('buyer');
    verify_csrf();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $order = Db::one('SELECT * FROM orders WHERE id = ? AND buyer_id = ?', [$id, Auth::id()]);
    if (!$order) {
        flash('error', 'Order not found.');
        redirect('/buyer/orders');
    }
    if ($order['status'] !== 'pending') {
        flash('error', 'Only orders still awaiting farmer confirmation can be cancelled.');
        redirect('/buyer/orders/' . $id);
    }

    try {
        Db::transaction(function () use ($order) {
            Db::update('orders', ['status' => 'cancelled'], 'id = :id', ['id' => $order['id']]);
            release_listing_stock((int)$order['listing_id'], (float)$order['quantity']);
            order_log((int)$order['id'], 'cancelled', 'Cancelled by buyer', Auth::id());
        });
        $context = order_context((int)$order['id']);
        if ($context) {
            notify_order_status($context, 'cancelled');
            Mailer::send(
                $context['farmer_email'],
                'Buyer cancelled order — ' . $context['reference'],
                Mailer::template('Order cancelled by buyer', '<p>Hello ' . e($context['farmer_name']) . ',</p><p>'
                    . e($context['buyer_name']) . ' cancelled order <strong>' . e($context['reference']) . '</strong> for '
                    . e(fc_qty((float)$context['quantity'], $context['unit'])) . ' of ' . e($context['produce_name'])
                    . '. The stock has been returned to your listing.</p>', 'Open farmer dashboard', url('/farmer/orders')),
                '',
                'order_cancelled_farmer'
            );
        }
        audit('order.buyer_cancelled', $order['reference']);
        flash('success', 'Order cancelled and the produce is back on the marketplace.');
    } catch (Throwable $e) {
        flash('error', 'Could not cancel this order: ' . $e->getMessage());
    }
    redirect('/buyer/orders');
}

function buyer_checkout_form(): void
{
    Auth::requireRole('buyer');
    $listing = listing_context((int)($_GET['id'] ?? 0));
    if (!$listing) {
        flash('error', 'That listing no longer exists.');
        redirect('/marketplace');
    }
    if ($listing['status'] !== 'open' || (float)$listing['quantity'] <= 0) {
        flash('error', 'That harvest is no longer available to order.');
        redirect('/listing/' . $listing['id']);
    }
    if ((int)$listing['farmer_id'] === (int)Auth::id()) {
        flash('error', 'You cannot order your own produce.');
        redirect('/marketplace');
    }
    view('buyer/checkout', [
        'title' => 'Reserve ' . $listing['produce_name'] . ' — FarmCoop',
        'listing' => $listing,
        'area' => 'buyer',
    ]);
}

function buyer_order_place(): void
{
    Auth::requireRole('buyer');
    verify_csrf();
    $listingId = (int)($_GET['id'] ?? $_POST['listing_id'] ?? 0);
    $listing = listing_context($listingId);
    if (!$listing) {
        flash('error', 'That listing no longer exists.');
        redirect('/marketplace');
    }

    $errors = [];
    $quantity = fc_number((string)($_POST['quantity'] ?? ''), 0.5, 100000);
    if ($quantity === null) {
        $errors['quantity'] = 'Enter a quantity of at least 0.5.';
    }
    $method = (string)($_POST['delivery_method'] ?? 'pickup');
    if (!isset(delivery_methods()[$method])) {
        $errors['delivery_method'] = 'Choose pickup or delivery.';
    }
    $address = trim((string)($_POST['delivery_address'] ?? ''));
    if ($method === 'delivery') {
        if ($address === '') {
            $errors['delivery_address'] = 'Add the address for delivery.';
        } elseif (mb_strlen($address) > 255) {
            $errors['delivery_address'] = 'Keep the address under 255 characters.';
        }
    }
    $date = trim((string)($_POST['requested_date'] ?? ''));
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        $errors['requested_date'] = 'Choose the date you want to collect or receive the produce.';
    } elseif ($date < date('Y-m-d')) {
        $errors['requested_date'] = 'Choose today or a future date.';
    } elseif ($date < $listing['available_from']) {
        $errors['requested_date'] = 'This harvest is only available from ' . short_date($listing['available_from']) . '.';
    }
    $notes = trim((string)($_POST['notes'] ?? ''));
    if (mb_strlen($notes) > 500) {
        $errors['notes'] = 'Keep notes under 500 characters.';
    }
    if ($quantity !== null && $quantity > (float)$listing['quantity']) {
        $errors['quantity'] = 'Only ' . fc_qty((float)$listing['quantity'], $listing['unit']) . ' is available.';
    }
    if ((int)$listing['farmer_id'] === (int)Auth::id()) {
        $errors['quantity'] = 'You cannot order your own produce.';
    }

    if ($errors) {
        remember_errors($errors);
        remember_old($_POST);
        redirect('/buyer/checkout/' . $listingId);
    }

    try {
        $orderId = Db::transaction(function () use ($listingId, $quantity, $method, $address, $date, $notes) {
            $locked = Db::one('SELECT * FROM listings WHERE id = ? FOR UPDATE', [$listingId]);
            if (!$locked) {
                throw new RuntimeException('That listing no longer exists.');
            }
            if ($locked['status'] !== 'open') {
                throw new RuntimeException('This harvest is no longer open for orders.');
            }
            $available = (float)$locked['quantity'];
            if ($quantity > $available) {
                throw new RuntimeException('Only ' . fc_qty($available, $locked['unit']) . ' is still available.');
            }
            $remaining = round($available - $quantity, 2);
            Db::update('listings', [
                'quantity' => $remaining,
                'status' => $remaining <= 0 ? 'sold' : 'open',
            ], 'id = :id', ['id' => $listingId]);

            $reference = reference('FC');
            $total = round($quantity * (float)$locked['unit_price'], 2);
            $orderId = Db::insert('orders', [
                'reference' => $reference,
                'listing_id' => $listingId,
                'buyer_id' => Auth::id(),
                'farmer_id' => $locked['farmer_id'],
                'quantity' => $quantity,
                'unit' => $locked['unit'],
                'unit_price' => $locked['unit_price'],
                'total_amount' => $total,
                'delivery_method' => $method,
                'delivery_address' => $method === 'delivery' ? $address : '',
                'requested_date' => $date,
                'notes' => $notes,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            order_log($orderId, 'pending', 'Order placed by buyer', Auth::id());
            return $orderId;
        });

        $order = order_context($orderId);
        if ($order) {
            try {
                notify_order_placed($order);
            } catch (Throwable $mailError) {
                Automation::log('FarmCoop', 'order email failed: ' . $mailError->getMessage());
            }
            audit('order.placed', $order['reference']);
        }
        clear_errors();
        clear_old();
        flash('success', 'Order ' . ($order['reference'] ?? '') . ' placed. The farmer has been notified and your quantity is held.');
        redirect('/buyer/orders/' . $orderId);
    } catch (Throwable $e) {
        remember_errors(['quantity' => $e->getMessage()]);
        remember_old($_POST);
        redirect('/buyer/checkout/' . $listingId);
    }
}
