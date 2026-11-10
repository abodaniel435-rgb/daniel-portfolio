<?php
declare(strict_types=1);

/* Public */
$router->get('/', 'home_index');
$router->get('/circles', 'circles_index');
$router->get('/circles/{id}', 'circle_show');

/* Authentication */
$router->get('/login', 'auth_login_form');
$router->post('/login', 'auth_login');
$router->get('/register', 'auth_register_form');
$router->post('/register', 'auth_register');
$router->post('/logout', 'auth_logout');

/* Member portal */
$router->get('/dashboard', 'member_dashboard');
$router->post('/circles/{id}/join', 'member_join');
$router->get('/member/circles', 'member_circles_page');
$router->get('/member/circles/{id}', 'member_circle_show');
$router->get('/member/contributions', 'member_contributions_page');
$router->post('/member/contributions/pay', 'member_contribution_pay');
$router->post('/member/contributions/{id}/pay', 'member_contribution_pay');
$router->get('/member/statement', 'member_statement_page');

/* Admin portal */
$router->get('/admin', 'admin_dashboard');
$router->get('/admin/circles', 'admin_circles_page');
$router->post('/admin/circles', 'admin_circle_create');
$router->get('/admin/circles/{id}', 'admin_circle_show');
$router->post('/admin/circles/{id}/status', 'admin_circle_status');
$router->post('/admin/circles/{id}/payout', 'admin_payout_schedule');
$router->get('/admin/contributions', 'admin_contributions_page');
$router->post('/admin/contributions/{id}/verify', 'admin_contribution_verify');
$router->post('/admin/contributions/{id}/late', 'admin_contribution_late');
$router->get('/admin/payouts', 'admin_payouts_page');
$router->post('/admin/payouts/{id}/paid', 'admin_payout_paid');
$router->get('/admin/users', 'admin_users_page');
$router->post('/admin/users/{id}/toggle', 'admin_user_toggle');
$router->get('/admin/outbox', 'admin_outbox_page');
$router->post('/admin/automation', 'admin_run_automation');
