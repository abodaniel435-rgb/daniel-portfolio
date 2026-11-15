<?php
declare(strict_types=1);

/* Public */
$router->get('/', 'home_index');
$router->get('/how', 'home_how');

/* Authentication */
$router->get('/register', 'auth_register_form');
$router->post('/register', 'auth_register');
$router->get('/login', 'auth_login_form');
$router->post('/login', 'auth_login');
$router->post('/logout', 'auth_logout');

/* Payroll workspace — every route below is member-only */
$router->get('/dashboard', 'work_dashboard');
$router->post('/company', 'work_company_create');
$router->get('/staff', 'work_staff');
$router->post('/staff', 'work_staff_add');
$router->post('/staff/{id}/delete', 'work_staff_delete');
$router->get('/calculate', 'work_calculate');
$router->post('/api/payroll', 'work_payroll_api');
$router->get('/runs', 'work_runs');
$router->post('/runs', 'work_run_create');
$router->get('/runs/{id}', 'work_run_show');
$router->post('/runs/{id}/delete', 'work_run_delete');

/* Administrator */
$router->get('/admin', 'admin_dashboard');
$router->get('/admin/users', 'admin_users');
$router->post('/admin/users/{id}/status', 'admin_user_status');
$router->get('/admin/rates', 'admin_rates');
$router->post('/admin/rates', 'admin_rates_save');
$router->get('/admin/audit', 'admin_audit');
$router->get('/admin/runs', 'admin_runs');
