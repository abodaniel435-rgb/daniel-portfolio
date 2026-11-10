<?php
declare(strict_types=1);

// Public marketplace
$router->get('/', 'home_index');
$router->get('/marketplace', 'market_index');
$router->get('/listing/{id}', 'market_listing');
$router->get('/farm/{id}', 'market_farm');
$router->get('/track', 'track_order');
$router->post('/track', 'track_order_post');

// Authentication
$router->get('/login', 'auth_login_form');
$router->post('/login', 'auth_login');
$router->get('/register', 'auth_register_form');
$router->post('/register', 'auth_register');
$router->post('/logout', 'auth_logout');
$router->get('/dashboard', 'dashboard_redirect');

// Farmer workspace
$router->get('/farmer', 'farmer_dashboard');
$router->get('/farmer/profile', 'farmer_profile_form');
$router->post('/farmer/profile', 'farmer_profile_save');
$router->get('/farmer/listings', 'farmer_listings');
$router->get('/farmer/listings/new', 'farmer_listing_form');
$router->post('/farmer/listings', 'farmer_listing_create');
$router->get('/farmer/listings/{id}/edit', 'farmer_listing_edit_form');
$router->post('/farmer/listings/{id}', 'farmer_listing_update');
$router->post('/farmer/listings/{id}/status', 'farmer_listing_status');
$router->post('/farmer/listings/{id}/delete', 'farmer_listing_delete');
$router->get('/farmer/orders', 'farmer_orders');
$router->post('/farmer/orders/{id}/status', 'farmer_order_status');
$router->get('/farmer/earnings', 'farmer_earnings_page');

// Buyer workspace
$router->get('/buyer', 'buyer_dashboard');
$router->get('/buyer/orders', 'buyer_orders');
$router->get('/buyer/orders/{id}', 'buyer_order_show');
$router->post('/buyer/orders/{id}/cancel', 'buyer_order_cancel');
$router->get('/buyer/checkout/{id}', 'buyer_checkout_form');
$router->post('/buyer/checkout/{id}', 'buyer_order_place');

// Administration
$router->get('/admin', 'admin_dashboard');
$router->get('/admin/users', 'admin_users');
$router->post('/admin/users/{id}/toggle', 'admin_user_toggle');
$router->post('/admin/users/{id}/verify', 'admin_farmer_verify');
$router->get('/admin/listings', 'admin_listings');
$router->post('/admin/listings/{id}/status', 'admin_listing_status');
$router->get('/admin/orders', 'admin_orders');
$router->get('/admin/outbox', 'admin_outbox');
$router->post('/admin/automation', 'admin_run_automation');
