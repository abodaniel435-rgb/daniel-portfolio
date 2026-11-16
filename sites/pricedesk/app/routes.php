<?php
declare(strict_types=1);

/* Public */
$router->get('/', 'home_index');
$router->get('/board', 'home_board');
$router->get('/compare', 'home_compare');
$router->post('/compare', 'home_compare');
$router->get('/api/quote', 'record_quote_api');

/* Authentication */
$router->get('/register', 'auth_register_form');
$router->post('/register', 'auth_register');
$router->get('/login', 'auth_login_form');
$router->post('/login', 'auth_login');
$router->post('/logout', 'auth_logout');

/* Members — filing prices, their own history, the watch list */
$router->get('/report', 'record_report');
$router->post('/report', 'record_report');
$router->get('/history', 'record_history');
$router->post('/history/withdraw', 'record_withdraw');
$router->get('/watch', 'record_watch');
$router->post('/watch', 'record_watch');

/* Administrator */
$router->get('/admin', 'admin_dashboard');
$router->get('/admin/queue', 'admin_queue');
$router->post('/admin/queue/{id}/verify', 'admin_verify');
$router->post('/admin/queue/{id}/reject', 'admin_reject');
$router->post('/admin/queue/{id}/unverify', 'admin_unverify');
$router->get('/admin/reference', 'admin_reference');
$router->post('/admin/markets', 'admin_market_save');
$router->post('/admin/commodities', 'admin_commodity_save');
$router->get('/admin/rates', 'admin_rates');
$router->post('/admin/rates', 'admin_rates_save');
$router->get('/admin/users', 'admin_users');
$router->post('/admin/users/{id}/status', 'admin_user_status');
$router->get('/admin/audit', 'admin_audit');
$router->get('/admin/outbox', 'admin_outbox');
$router->post('/admin/automation', 'admin_automation');
