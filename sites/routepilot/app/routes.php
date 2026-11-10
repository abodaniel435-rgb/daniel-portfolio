<?php

$router->get('/', 'home_index');
$router->get('/track', 'track_show');

$router->get('/login', 'auth_login_form');
$router->post('/login', 'auth_login');
$router->get('/register', 'auth_register_form');
$router->post('/register', 'auth_register');
$router->post('/logout', 'auth_logout');
$router->get('/dashboard', 'dashboard_redirect');

// Dispatch desk (dispatcher + admin).
$router->get('/dispatch', 'dispatch_dashboard');
$router->get('/dispatch/shipments', 'dispatch_shipments');
$router->get('/dispatch/shipments/new', 'dispatch_shipment_form');
$router->post('/dispatch/shipments', 'dispatch_shipment_create');
$router->get('/dispatch/shipments/{id}', 'dispatch_shipment_show');
$router->post('/dispatch/shipments/{shipment_id}/assign', 'dispatch_assign');
$router->post('/dispatch/shipments/{shipment_id}/unassign', 'dispatch_unassign');
$router->post('/dispatch/shipments/{shipment_id}/status', 'dispatch_shipment_status');
$router->get('/dispatch/couriers', 'dispatch_couriers');
$router->post('/dispatch/couriers/{id}/toggle', 'dispatch_courier_toggle');

// Courier app.
$router->get('/courier/queue', 'courier_queue');
$router->get('/courier/shipment/{id}', 'courier_shipment_show');
$router->post('/courier/shipment/{shipment_id}/status', 'courier_status');
$router->post('/courier/availability', 'courier_availability');

// Administration.
$router->get('/admin', 'admin_overview');
$router->get('/admin/users', 'admin_users');
$router->post('/admin/users', 'admin_user_create');
$router->post('/admin/users/{id}/toggle', 'admin_user_toggle');
$router->get('/admin/shipments', 'admin_shipments');
$router->get('/admin/outbox', 'admin_outbox');
$router->post('/admin/automation', 'admin_run_automation');
