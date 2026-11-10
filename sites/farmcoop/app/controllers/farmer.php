<?php
declare(strict_types=1);

/* ------------------------------------------------------------------ *
 *  Farmer workspace
 * ------------------------------------------------------------------ */

function farmer_dashboard(): void
{
    Auth::requireRole('farmer');
    $farmerId = (int)Auth::id();
    $profile = farmer_profile($farmerId);

    $stats = [
        'listings' => (int)Db::value("SELECT COUNT(*) FROM listings WHERE farmer_id = ? AND status = 'open'", [$farmerId]),
        'pending' => (int)Db::value("SELECT COUNT(*) FROM orders WHERE farmer_id = ? AND status = 'pending'", [$farmerId]),
        'ready' => (int)Db::value("SELECT COUNT(*) FROM orders WHERE farmer_id = ? AND status = 'ready'", [$farmerId]),
        'completed' => (int)Db::value("SELECT COUNT(*) FROM orders WHERE farmer_id = ? AND status = 'completed'", [$farmerId]),
    ];
    $earnings = farmer_earnings($farmerId);

    $pending = Db::all(
        "SELECT o.*, l.produce_name, b.name AS buyer_name
         FROM orders o JOIN listings l ON l.id = o.listing_id JOIN users b ON b.id = o.buyer_id
         WHERE o.farmer_id = ? AND o.status = 'pending'
         ORDER BY o.created_at ASC LIMIT 5",
        [$farmerId]
    );
    $listings = Db::all(
        "SELECT l.*, (SELECT COUNT(*) FROM orders o WHERE o.listing_id = l.id AND o.status IN ('pending','accepted','ready')) AS active_orders
         FROM listings l WHERE l.farmer_id = ? ORDER BY l.created_at DESC LIMIT 6",
        [$farmerId]
    );
    $recentEvents = Db::all(
        "SELECT o.reference, o.status, o.total_amount, o.updated_at, l.produce_name, b.name AS buyer_name
         FROM orders o JOIN listings l ON l.id = o.listing_id JOIN users b ON b.id = o.buyer_id
         WHERE o.farmer_id = ? ORDER BY o.updated_at DESC LIMIT 6",
        [$farmerId]
    );

    view('farmer/dashboard', [
        'title' => 'Farmer dashboard — FarmCoop',
        'profile' => $profile,
        'stats' => $stats,
        'earnings' => $earnings,
        'pending' => $pending,
        'listings' => $listings,
        'recentEvents' => $recentEvents,
        'area' => 'farmer',
    ]);
}

function farmer_profile_form(): void
{
    Auth::requireRole('farmer');
    view('farmer/profile', [
        'title' => 'Farm profile — FarmCoop',
        'profile' => farmer_profile((int)Auth::id()),
        'states' => nigerian_states(),
        'area' => 'farmer',
    ]);
}

function farmer_profile_save(): void
{
    Auth::requireRole('farmer');
    verify_csrf();
    $input = $_POST;

    $v = (new Validator($input))
        ->required('farm_name', 'state', 'lga', 'produce_focus')
        ->max('farm_name', 160)
        ->max('lga', 90)
        ->max('produce_focus', 190)
        ->max('about', 1200)
        ->in('state', nigerian_states());

    $photo = trim((string)($input['photo_url'] ?? ''));
    if (!$v->passes() || !fc_is_url($photo)) {
        $errors = $v->errors();
        if ($photo !== '' && !fc_is_url($photo)) {
            $errors['photo_url'] = 'Enter a full image URL starting with http:// or https://.';
        }
        remember_errors($errors);
        remember_old($input);
        redirect('/farmer/profile');
    }

    $data = [
        'farm_name' => (string)$v->value('farm_name'),
        'state' => (string)$v->value('state'),
        'lga' => (string)$v->value('lga'),
        'produce_focus' => (string)$v->value('produce_focus'),
        'about' => trim((string)($input['about'] ?? '')),
        'photo_url' => $photo,
    ];
    $existing = farmer_profile((int)Auth::id());
    if ($existing) {
        Db::update('farm_profiles', $data, 'user_id = :uid', ['uid' => Auth::id()]);
        audit('farmer.profile_updated', $data['farm_name']);
        flash('success', 'Farm profile updated.');
    } else {
        $data['user_id'] = Auth::id();
        $data['is_verified'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');
        Db::insert('farm_profiles', $data);
        audit('farmer.profile_created', $data['farm_name']);
        flash('success', 'Farm profile saved. Create your first harvest listing next.');
    }
    clear_errors();
    clear_old();
    redirect('/farmer/profile');
}

function farmer_listings(): void
{
    Auth::requireRole('farmer');
    $status = (string)($_GET['status'] ?? '');
    $where = ['l.farmer_id = ?'];
    $params = [Auth::id()];
    if (in_array($status, ['open', 'paused', 'reserved', 'sold', 'expired'], true)) {
        $where[] = 'l.status = ?';
        $params[] = $status;
    } else {
        $status = '';
    }
    $rows = Db::all(
        "SELECT l.*,
                (SELECT COUNT(*) FROM orders o WHERE o.listing_id = l.id AND o.status IN ('pending','accepted','ready')) AS active_orders,
                (SELECT COUNT(*) FROM orders o WHERE o.listing_id = l.id) AS total_orders
         FROM listings l WHERE " . implode(' AND ', $where) . "
         ORDER BY FIELD(l.status, 'open', 'paused', 'reserved', 'sold', 'expired'), l.created_at DESC",
        $params
    );

    view('farmer/listings', [
        'title' => 'My harvest listings — FarmCoop',
        'rows' => $rows,
        'status' => $status,
        'area' => 'farmer',
        'profile' => farmer_profile((int)Auth::id()),
    ]);
}

function farmer_listing_form(): void
{
    Auth::requireRole('farmer');
    view('farmer/listing_form', [
        'title' => 'New harvest listing — FarmCoop',
        'listing' => null,
        'categories' => produce_categories(),
        'units' => produce_units(),
        'states' => nigerian_states(),
        'profile' => farmer_profile((int)Auth::id()),
        'area' => 'farmer',
    ]);
}

/** Shared validation for create + update. */
function farmer_listing_input(): array
{
    $input = $_POST;
    $errors = [];

    $name = trim((string)($input['produce_name'] ?? ''));
    if ($name === '') {
        $errors['produce_name'] = 'Give the produce a clear name.';
    } elseif (mb_strlen($name) > 160) {
        $errors['produce_name'] = 'Keep the produce name under 160 characters.';
    }

    $category = (string)($input['category'] ?? '');
    if (!isset(produce_categories()[$category])) {
        $errors['category'] = 'Choose a produce category.';
    }

    $quantity = fc_number((string)($input['quantity'] ?? ''), 0.5, 100000);
    if ($quantity === null) {
        $errors['quantity'] = 'Enter a quantity between 0.5 and 100,000.';
    }

    $unit = (string)($input['unit'] ?? 'kg');
    if (!in_array($unit, produce_units(), true)) {
        $errors['unit'] = 'Choose a valid unit.';
    }

    $price = fc_number((string)($input['unit_price'] ?? ''), 1, 10000000);
    if ($price === null) {
        $errors['unit_price'] = 'Enter a unit price between ₦1 and ₦10,000,000.';
    }

    $availableFrom = trim((string)($input['available_from'] ?? ''));
    $date = DateTime::createFromFormat('Y-m-d', $availableFrom);
    if (!$date || $date->format('Y-m-d') !== $availableFrom) {
        $errors['available_from'] = 'Choose the date this harvest is available from.';
    } elseif ($availableFrom < date('Y-m-d')) {
        $errors['available_from'] = 'The availability date cannot be in the past.';
    }

    $window = trim((string)($input['pickup_window'] ?? ''));
    if ($window === '') {
        $errors['pickup_window'] = 'Describe the pickup window (days and hours).';
    } elseif (mb_strlen($window) > 120) {
        $errors['pickup_window'] = 'Keep the pickup window under 120 characters.';
    }

    $state = (string)($input['state'] ?? '');
    if (!in_array($state, nigerian_states(), true)) {
        $errors['state'] = 'Choose the state where the produce is held.';
    }

    $lga = trim((string)($input['lga'] ?? ''));
    if ($lga === '') {
        $errors['lga'] = 'Enter the local government area.';
    } elseif (mb_strlen($lga) > 90) {
        $errors['lga'] = 'Keep the LGA under 90 characters.';
    }

    $location = trim((string)($input['location'] ?? ''));
    if ($location === '') {
        $errors['location'] = 'Add a pickup landmark or address.';
    } elseif (mb_strlen($location) > 190) {
        $errors['location'] = 'Keep the location under 190 characters.';
    }

    $description = trim((string)($input['description'] ?? ''));
    if (mb_strlen($description) > 2000) {
        $errors['description'] = 'Keep the description under 2,000 characters.';
    }

    $image = trim((string)($input['image_url'] ?? ''));
    if (!fc_is_url($image)) {
        $errors['image_url'] = 'Enter a full image URL starting with http:// or https://.';
    } elseif (mb_strlen($image) > 255) {
        $errors['image_url'] = 'Keep the image URL under 255 characters.';
    }

    return [$errors, [
        'produce_name' => $name,
        'category' => $category,
        'quantity' => $quantity ?? 0.0,
        'unit' => $unit,
        'unit_price' => $price ?? 0.0,
        'available_from' => $availableFrom,
        'pickup_window' => $window,
        'state' => $state,
        'lga' => $lga,
        'location' => $location,
        'description' => $description,
        'image_url' => $image,
    ]];
}

function farmer_listing_create(): void
{
    Auth::requireRole('farmer');
    verify_csrf();
    [$errors, $data] = farmer_listing_input();
    if ($errors) {
        remember_errors($errors);
        remember_old($_POST);
        redirect('/farmer/listings/new');
    }
    $data['reference'] = reference('LS');
    $data['farmer_id'] = Auth::id();
    $data['quantity_listed'] = $data['quantity'];
    $data['status'] = 'open';
    $data['created_at'] = date('Y-m-d H:i:s');
    $id = Db::insert('listings', $data);
    audit('listing.created', $data['produce_name'] . ' (' . $data['reference'] . ')');
    clear_errors();
    clear_old();
    flash('success', 'Listing published. Buyers can now order ' . $data['produce_name'] . '.');
    redirect('/farmer/listings');
}

function farmer_listing_edit_form(): void
{
    Auth::requireRole('farmer');
    $listing = Db::one('SELECT * FROM listings WHERE id = ? AND farmer_id = ?', [(int)($_GET['id'] ?? 0), Auth::id()]);
    if (!$listing) {
        flash('error', 'Listing not found.');
        redirect('/farmer/listings');
    }
    view('farmer/listing_form', [
        'title' => 'Edit listing — FarmCoop',
        'listing' => $listing,
        'categories' => produce_categories(),
        'units' => produce_units(),
        'states' => nigerian_states(),
        'profile' => farmer_profile((int)Auth::id()),
        'area' => 'farmer',
    ]);
}

function farmer_listing_update(): void
{
    Auth::requireRole('farmer');
    verify_csrf();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $listing = Db::one('SELECT * FROM listings WHERE id = ? AND farmer_id = ?', [$id, Auth::id()]);
    if (!$listing) {
        flash('error', 'Listing not found.');
        redirect('/farmer/listings');
    }
    [$errors, $data] = farmer_listing_input();

    $committed = (float)Db::value(
        "SELECT COALESCE(SUM(quantity),0) FROM orders WHERE listing_id = ? AND status IN ('pending','accepted','ready')",
        [$id]
    );
    if ($errors === [] && $data['quantity'] < $committed) {
        $errors['quantity'] = 'You already have ' . fc_qty($committed, $data['unit'])
            . ' committed to open orders. Quantity cannot go below that.';
    }
    if ($errors) {
        remember_errors($errors);
        remember_old($_POST);
        redirect('/farmer/listings/' . $id . '/edit');
    }

    $soldOut = $data['quantity'] <= 0;
    $data['quantity_listed'] = max((float)$listing['quantity_listed'], $data['quantity'] + $committed);
    if ($soldOut) {
        $data['status'] = 'sold';
    } elseif (in_array($listing['status'], ['sold', 'expired'], true)) {
        $data['status'] = 'open';
    }
    Db::update('listings', $data, 'id = :id', ['id' => $id]);
    audit('listing.updated', $listing['produce_name'] . ' (#' . $id . ')');
    clear_errors();
    clear_old();
    flash('success', 'Listing updated.');
    redirect('/farmer/listings');
}

function farmer_listing_status(): void
{
    Auth::requireRole('farmer');
    verify_csrf();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $listing = Db::one('SELECT * FROM listings WHERE id = ? AND farmer_id = ?', [$id, Auth::id()]);
    if (!$listing) {
        flash('error', 'Listing not found.');
        redirect('/farmer/listings');
    }

    $activeOrders = listing_active_orders($id);
    $target = null;
    $message = '';
    switch ($action) {
        case 'pause':
            if ($listing['status'] !== 'open') {
                flash('error', 'Only live listings can be paused.');
                redirect('/farmer/listings');
            }
            $target = 'paused';
            $message = 'Listing paused. It is hidden from the marketplace until you resume it.';
            break;
        case 'resume':
            if ($listing['status'] !== 'paused' || (float)$listing['quantity'] <= 0) {
                flash('error', 'This listing cannot be resumed — check its status and remaining quantity.');
                redirect('/farmer/listings');
            }
            $target = 'open';
            $message = 'Listing is live again.';
            break;
        case 'reserve':
            if ($listing['status'] !== 'open') {
                flash('error', 'Only live listings can be reserved.');
                redirect('/farmer/listings');
            }
            $target = 'reserved';
            $message = 'Listing reserved and removed from public search.';
            break;
        case 'release':
            if (!in_array($listing['status'], ['reserved', 'expired'], true)) {
                flash('error', 'Nothing to release for this listing.');
                redirect('/farmer/listings');
            }
            if ((float)$listing['quantity'] <= 0) {
                flash('error', 'There is no stock left to relist.');
                redirect('/farmer/listings');
            }
            $target = 'open';
            $message = 'Listing is back on the marketplace.';
            break;
        case 'sold':
            if ($activeOrders > 0) {
                flash('error', 'Fulfil or close the ' . $activeOrders . ' active order(s) before marking this listing sold.');
                redirect('/farmer/listings');
            }
            $target = 'sold';
            $message = 'Listing marked sold.';
            break;
        default:
            flash('error', 'Unknown listing action.');
            redirect('/farmer/listings');
    }

    Db::update('listings', ['status' => $target], 'id = :id', ['id' => $id]);
    audit('listing.' . $action, $listing['produce_name'] . ' (#' . $id . ')');
    flash('success', $message);
    redirect('/farmer/listings');
}

function farmer_listing_delete(): void
{
    Auth::requireRole('farmer');
    verify_csrf();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $listing = Db::one('SELECT * FROM listings WHERE id = ? AND farmer_id = ?', [$id, Auth::id()]);
    if (!$listing) {
        flash('error', 'Listing not found.');
        redirect('/farmer/listings');
    }
    $orders = (int)Db::value('SELECT COUNT(*) FROM orders WHERE listing_id = ?', [$id]);
    if ($orders > 0) {
        flash('error', 'This listing has order history and cannot be deleted. Pause it instead so buyers keep their records.');
        redirect('/farmer/listings');
    }
    Db::run('DELETE FROM listings WHERE id = ? AND farmer_id = ?', [$id, Auth::id()]);
    audit('listing.deleted', $listing['produce_name'] . ' (' . $listing['reference'] . ')');
    flash('success', 'Listing deleted.');
    redirect('/farmer/listings');
}

function farmer_orders(): void
{
    Auth::requireRole('farmer');
    $status = (string)($_GET['status'] ?? '');
    $where = ['o.farmer_id = ?'];
    $params = [Auth::id()];
    if (in_array($status, ['pending', 'accepted', 'ready', 'completed', 'rejected', 'cancelled'], true)) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    } else {
        $status = '';
    }
    $orders = Db::all(
        "SELECT o.*, l.produce_name, l.pickup_window, b.name AS buyer_name, b.phone AS buyer_phone
         FROM orders o
         JOIN listings l ON l.id = o.listing_id
         JOIN users b ON b.id = o.buyer_id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY FIELD(o.status, 'pending', 'accepted', 'ready', 'completed', 'rejected', 'cancelled'), o.created_at DESC
         LIMIT 80",
        $params
    );
    $counts = Db::one(
        "SELECT SUM(status = 'pending') AS pending, SUM(status = 'accepted') AS accepted,
                SUM(status = 'ready') AS ready, SUM(status = 'completed') AS completed
         FROM orders WHERE farmer_id = ?",
        [Auth::id()]
    );
    view('farmer/orders', [
        'title' => 'Incoming orders — FarmCoop',
        'orders' => $orders,
        'status' => $status,
        'counts' => $counts ?: ['pending' => 0, 'accepted' => 0, 'ready' => 0, 'completed' => 0],
        'area' => 'farmer',
    ]);
}

function farmer_order_status(): void
{
    Auth::requireRole('farmer');
    verify_csrf();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $order = Db::one('SELECT * FROM orders WHERE id = ? AND farmer_id = ?', [$id, Auth::id()]);
    if (!$order) {
        flash('error', 'Order not found.');
        redirect('/farmer/orders');
    }

    $allowed = ['accept' => ['pending', 'accepted'], 'reject' => ['pending', 'rejected'], 'ready' => ['accepted', 'ready'], 'complete' => ['ready', 'completed']];
    if (!isset($allowed[$action])) {
        flash('error', 'Unknown order action.');
        redirect('/farmer/orders');
    }
    [$from, $to] = $allowed[$action];
    if ($order['status'] !== $from) {
        flash('error', 'This order is ' . $order['status'] . ' and cannot move to ' . $to . '.');
        redirect('/farmer/orders');
    }

    $note = trim((string)($_POST['note'] ?? ''));
    try {
        Db::transaction(function () use ($order, $to, $note) {
            $now = date('Y-m-d H:i:s');
            $update = ['status' => $to];
            if ($to === 'accepted') {
                $update['accepted_at'] = $now;
            }
            if ($to === 'ready') {
                $update['ready_at'] = $now;
            }
            if ($to === 'completed') {
                $update['completed_at'] = $now;
            }
            Db::update('orders', $update, 'id = :id', ['id' => $order['id']]);
            if ($to === 'rejected') {
                release_listing_stock((int)$order['listing_id'], (float)$order['quantity']);
            }
            order_log((int)$order['id'], $to, $note, Auth::id());
        });

        $context = order_context((int)$order['id']);
        if ($context && in_array($to, ['accepted', 'rejected', 'ready', 'completed'], true)) {
            try {
                notify_order_status($context, $to);
            } catch (Throwable $mailError) {
                Automation::log('FarmCoop', 'order email failed: ' . $mailError->getMessage());
            }
        }
        audit('order.' . $to, $order['reference']);
        $labels = ['accepted' => 'Order accepted.', 'rejected' => 'Order rejected — stock returned to the listing.', 'ready' => 'Order marked ready for handover.', 'completed' => 'Order completed.'];
        flash('success', $labels[$to]);
    } catch (Throwable $e) {
        flash('error', 'Could not update the order: ' . $e->getMessage());
    }
    redirect('/farmer/orders');
}

function farmer_earnings_page(): void
{
    Auth::requireRole('farmer');
    $earnings = farmer_earnings((int)Auth::id());
    $byListing = Db::all(
        "SELECT l.produce_name, l.unit, SUM(o.quantity) AS quantity, SUM(o.total_amount) AS gross
         FROM orders o JOIN listings l ON l.id = o.listing_id
         WHERE o.farmer_id = ? AND o.status = 'completed'
         GROUP BY l.id ORDER BY gross DESC",
        [Auth::id()]
    );
    $payouts = Db::all(
        "SELECT o.reference, o.status, o.total_amount, o.completed_at, o.created_at, o.quantity, o.unit,
                l.produce_name, b.name AS buyer_name
         FROM orders o JOIN listings l ON l.id = o.listing_id JOIN users b ON b.id = o.buyer_id
         WHERE o.farmer_id = ? AND o.status IN ('accepted','ready','completed')
         ORDER BY o.created_at DESC LIMIT 40",
        [Auth::id()]
    );
    view('farmer/earnings', [
        'title' => 'Earnings — FarmCoop',
        'earnings' => $earnings,
        'byListing' => $byListing,
        'payouts' => $payouts,
        'area' => 'farmer',
    ]);
}
