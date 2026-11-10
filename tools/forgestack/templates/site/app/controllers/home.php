<?php
declare(strict_types=1);

function home_index(): void
{
    $stats = [
        'total' => (int)Db::value('SELECT COUNT(*) FROM ' . txn_table()),
        'completed' => (int)Db::value('SELECT COUNT(*) FROM ' . txn_table() . " WHERE status IN ('completed','delivered','seated')"),
    ];
    $products = cfg('modules.orders', false)
        ? Db::all('SELECT * FROM products WHERE is_active = 1 ORDER BY price DESC LIMIT 4')
        : [];
    view('home', [
        'title' => cfg('app_name') . ' — ' . cfg('tagline'),
        'description' => (string)cfg('description'),
        'sections' => (array)cfg('copy.sections', []),
        'stats' => $stats,
        'products' => $products,
        'area' => 'public',
    ]);
}

/** Shared content pages built from the curated section library. */
function render_content_page(string $title, string $lede, array $types): void
{
    $sections = array_values(array_filter((array)cfg('copy.sections', []), static fn ($s) => in_array($s['type'] ?? '', $types, true)));
    view('page', [
        'title' => $title . ' — ' . cfg('app_name'),
        'description' => (string)cfg('description'),
        'pageTitle' => $title,
        'pageLede' => $lede,
        'sections' => $sections,
        'area' => 'public',
    ]);
}

function page_about(): void
{
    render_content_page('About ' . cfg('app_name'), (string)cfg('tagline'), ['editorial', 'stats', 'team', 'proof']);
}

function page_services(): void
{
    render_content_page('Services & pricing', 'What we do, how it works and what it costs — before you commit.', ['services', 'process', 'menu', 'coverage', 'faq']);
}

function page_faq(): void
{
    render_content_page('Frequently asked questions', 'The questions the team answers most often, written down properly.', ['faq', 'cta']);
}

function book_form(): void
{
    $record = null;
    if (isset($_GET['ref'])) {
        $record = find_record_by_reference((string)$_GET['ref']);
    }
    view('book', [
        'title' => cfg('labels.book') . ' — ' . cfg('app_name'),
        'description' => (string)cfg('labels.form_intro'),
        'area' => 'public',
    ]);
}

function book_store(): void
{
    verify_csrf();
    $choicesA = array_column((array)cfg('form.choices_a', []), 'value');
    $choicesB = (array)cfg('form.choices_b', []);
    $times = (array)cfg('form.times', []);
    $minQty = (int)cfg('form.quantity_min', 1);
    $maxQty = (int)cfg('form.quantity_max', 20);

    $v = (new Validator($_POST))
        ->required('customer_name', 'customer_email', 'customer_phone', 'request_date', 'request_time', 'choice_a')
        ->email('customer_email')
        ->max('customer_name', 120)
        ->max('customer_phone', 40)
        ->max('notes', 1000)
        ->date('request_date', true)
        ->integer('quantity', $minQty, $maxQty)
        ->in('choice_a', $choicesA)
        ->in('request_time', $times);

    $minDate = date('Y-m-d');
    if (trim((string)($_POST['request_date'] ?? '')) === $minDate && ($_POST['request_time'] ?? '') !== '') {
        // Same-day requests are allowed until the end of the day; the team confirms manually.
    }

    $choiceB = trim((string)($_POST['choice_b'] ?? ''));
    if ($choicesB && !in_array($choiceB, $choicesB, true)) {
        $choiceB = (string)$choicesB[0];
    }

    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($_POST);
        flash('error', 'Please check the highlighted fields and try again.');
        redirect('/book');
    }

    try {
        $record = create_record([
            'customer_name' => (string)$v->value('customer_name'),
            'customer_email' => (string)$v->value('customer_email'),
            'customer_phone' => (string)$v->value('customer_phone'),
            'request_date' => (string)$v->value('request_date'),
            'request_time' => (string)$v->value('request_time'),
            'choice_a' => (string)$v->value('choice_a'),
            'choice_b' => $choiceB,
            'quantity' => (int)$v->value('quantity', $minQty),
            'notes' => trim((string)($_POST['notes'] ?? '')),
        ]);
        clear_old();
        clear_errors();
        flash('success', 'Request received. Your reference is ' . $record['reference'] . '.');
        redirect('/track?ref=' . urlencode((string)$record['reference']) . '&new=1');
    } catch (Throwable $e) {
        flash('error', 'We could not save the request: ' . $e->getMessage());
        remember_old($_POST);
        redirect('/book');
    }
}

function track_show(): void
{
    $reference = trim((string)($_GET['ref'] ?? ''));
    $record = $reference !== '' ? find_record_by_reference($reference) : null;
    $timeline = $record ? record_timeline((int)$record['id']) : [];
    view('track', [
        'title' => (string)cfg('labels.track_title') . ' — ' . cfg('app_name'),
        'description' => (string)cfg('labels.track_intro'),
        'reference' => $reference,
        'record' => $record,
        'timeline' => $timeline,
        'isNew' => isset($_GET['new']),
        'area' => 'public',
    ]);
}

function contact_form(): void
{
    if (!cfg('modules.messaging', false)) {
        view('contact_limited', [
            'title' => 'Contact — ' . cfg('app_name'),
            'description' => 'Contact details for ' . cfg('app_name'),
            'area' => 'public',
        ]);
        return;
    }
    view('contact', [
        'title' => 'Contact — ' . cfg('app_name'),
        'description' => (string)cfg('copy.contact_intro'),
        'area' => 'public',
    ]);
}

function contact_store(): void
{
    if (!cfg('modules.messaging', false)) {
        http_response_code(404);
        view('errors/404', ['title' => 'Not found']);
        return;
    }
    verify_csrf();
    $v = (new Validator($_POST))
        ->required('name', 'email', 'subject', 'body')
        ->email('email')
        ->max('name', 120)
        ->max('subject', 160)
        ->min('body', 10)
        ->max('body', 3000);
    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($_POST);
        redirect('/contact');
    }
    $id = Db::insert('messages', [
        'name' => (string)$v->value('name'),
        'email' => (string)$v->value('email'),
        'phone' => trim((string)($_POST['phone'] ?? '')),
        'subject' => (string)$v->value('subject'),
        'body' => (string)$v->value('body'),
        'status' => 'new',
    ]);
    $body = '<p><strong>' . e((string)$v->value('name')) . '</strong> (' . e((string)$v->value('email')) . ') sent an enquiry.</p>'
        . '<p><strong>Subject:</strong> ' . e((string)$v->value('subject')) . '</p>'
        . '<p>' . nl2br(e((string)$v->value('body'))) . '</p>';
    notify_admin('New enquiry — ' . $v->value('subject'), 'New website enquiry', $body, 'Open the inbox', url('/admin/messages'), 'admin_enquiry');

    if (cfg('modules.messaging', false)) {
        Mailer::send((string)$v->value('email'), 'We received your message — ' . cfg('app_name'),
            Mailer::template('Thanks — we have your message', '<p>Hello ' . e((string)$v->value('name')) . ',</p><p>Your message reached the team. We reply within one working day.</p>', '', ''),
            '', 'contact_receipt');
    }
    audit('enquiry.created', 'Message #' . $id);
    clear_old();
    clear_errors();
    flash('success', 'Thanks — your message is with the team. We reply within one working day.');
    redirect('/contact');
}

function shop_index(): void
{
    $products = Db::all('SELECT * FROM products WHERE is_active = 1 ORDER BY price ASC');
    view('shop', [
        'title' => (string)cfg('labels.shop_title', 'Catalogue') . ' — ' . cfg('app_name'),
        'description' => 'Order from ' . cfg('app_name') . ' for collection. Stock is checked inside a database transaction.',
        'products' => $products,
        'area' => 'public',
    ]);
}

function order_store(): void
{
    verify_csrf();
    $v = (new Validator($_POST))
        ->required('product_id', 'quantity', 'name', 'email', 'phone')
        ->email('email')
        ->integer('product_id', 1)
        ->integer('quantity', 1, 20)
        ->max('name', 120)
        ->max('phone', 40);
    if (!$v->passes()) {
        remember_errors($v->errors());
        flash('error', 'Please check your order details.');
        redirect('/shop');
    }
    try {
        $order = create_order((int)$v->value('product_id'), (int)$v->value('quantity'), [
            'name' => (string)$v->value('name'),
            'email' => (string)$v->value('email'),
            'phone' => (string)$v->value('phone'),
        ]);
        flash('success', 'Order confirmed. Your reference is ' . $order['reference'] . '.');
        redirect('/shop?ref=' . urlencode((string)$order['reference']));
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('/shop');
    }
}
