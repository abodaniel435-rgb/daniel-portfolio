<?php
/**
 * Route table. Module routes are registered only when the feature was selected
 * in the intake, so the generated app never advertises a dead link.
 */
declare(strict_types=1);

$router->get('/', 'home_index');
$router->get('/about', 'page_about');
$router->get('/services', 'page_services');
$router->get('/faq', 'page_faq');

$router->get('/book', 'book_form');
$router->post('/book', 'book_store');
$router->get('/track', 'track_show');

$router->get('/contact', 'contact_form');
$router->post('/contact', 'contact_store');

if (cfg('modules.orders', false)) {
    $router->get('/shop', 'shop_index');
    $router->post('/order', 'order_store');
}

if (cfg('modules.auth', false)) {
    $router->get('/login', 'auth_login_form');
    $router->post('/login', 'auth_login');
    $router->get('/register', 'auth_register_form');
    $router->post('/register', 'auth_register');
    $router->post('/logout', 'auth_logout');
    $router->get('/portal', 'portal_index');
}

$router->get('/admin', 'admin_dashboard');
$router->get('/admin/records', 'admin_records');
$router->post('/admin/records/{id}/status', 'admin_record_status');
$router->get('/admin/users', 'admin_users');
$router->post('/admin/users/{id}/toggle', 'admin_user_toggle');
$router->get('/admin/outbox', 'admin_outbox');
$router->post('/admin/automation', 'admin_run_automation');

if (cfg('modules.payments', false)) {
    $router->post('/admin/records/{id}/paid', 'admin_record_paid');
    $router->get('/admin/payments', 'admin_payments');
}
if (cfg('modules.orders', false)) {
    $router->get('/admin/orders', 'admin_orders');
    $router->post('/admin/orders/{id}/status', 'admin_order_status');
    $router->get('/admin/products', 'admin_products');
    $router->post('/admin/products', 'admin_product_store');
    $router->post('/admin/products/{id}/restock', 'admin_product_restock');
}
if (cfg('modules.messaging', false)) {
    $router->get('/admin/messages', 'admin_messages');
    $router->post('/admin/messages/{id}/handled', 'admin_message_handled');
}
if (cfg('modules.reports', false)) {
    $router->get('/admin/reports', 'admin_reports');
}
