<?php
declare(strict_types=1);

/* Public — the calculator and the published bands need no account. */
$router->get('/', 'home_index');
$router->post('/', 'home_index');
$router->get('/rates', 'home_rates');
$router->get('/api/paye', 'home_api_paye');

/* Authentication — registering creates a staff account, never an administrator. */
$router->get('/register', 'auth_register_form');
$router->post('/register', 'auth_register');
$router->get('/login', 'auth_login_form');
$router->post('/login', 'auth_login');
$router->post('/logout', 'auth_logout');

/* Staff — their own record and their own payslips only. */
$router->get('/payslips', 'staff_payslips');
$router->get('/payslips/{id}', 'staff_payslip');
$router->get('/profile', 'staff_profile');
$router->post('/profile', 'staff_profile');

/* Payroll desk */
$router->get('/admin', 'admin_dashboard');

$router->get('/admin/employees', 'admin_employees');
$router->get('/admin/employees/new', 'admin_employee_new');
$router->post('/admin/employees/new', 'admin_employee_new');
$router->get('/admin/employees/{id}/edit', 'admin_employee_edit');
$router->post('/admin/employees/{id}/edit', 'admin_employee_edit');
$router->post('/admin/employees/{id}/status', 'admin_employee_status');

$router->get('/admin/runs', 'admin_runs');
$router->post('/admin/runs', 'admin_run_create');
$router->get('/admin/runs/{id}', 'admin_run_view');
$router->post('/admin/runs/{id}/lock', 'admin_run_lock');
$router->post('/admin/runs/{id}/void', 'admin_run_void');
$router->post('/admin/runs/{id}/notify', 'admin_run_notify');

$router->get('/admin/rules', 'admin_rules');
$router->post('/admin/rules', 'admin_rules_save');

$router->get('/admin/staff', 'admin_staff');
$router->post('/admin/staff/{id}/status', 'admin_staff_status');
$router->post('/admin/staff/{id}/link', 'admin_staff_link');

$router->get('/admin/audit', 'admin_audit');
$router->get('/admin/outbox', 'admin_outbox');
