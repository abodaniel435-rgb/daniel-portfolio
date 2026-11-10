<?php

$router->get('/', 'home_index');
$router->get('/doctors', 'doctors_index');
$router->get('/track', 'track_show');
$router->get('/api/slots', 'api_slots');

$router->get('/login', 'auth_login_form');
$router->post('/login', 'auth_login');
$router->get('/register', 'auth_register_form');
$router->post('/register', 'auth_register');
$router->post('/logout', 'auth_logout');

$router->get('/dashboard', 'dashboard_redirect');

$router->get('/patient/appointments', 'patient_appointments');
$router->get('/patient/book', 'patient_book_form');
$router->post('/patient/book', 'patient_book');
$router->post('/patient/cancel/{id}', 'patient_cancel');
$router->get('/patient/notes', 'patient_notes');

$router->get('/doctor/schedule', 'doctor_schedule');
$router->post('/doctor/appointment/{id}/status', 'doctor_status');
$router->get('/doctor/consultation/{id}', 'doctor_consultation_form');
$router->post('/doctor/consultation/{id}', 'doctor_consultation_save');

$router->get('/admin', 'admin_dashboard');
$router->get('/admin/appointments', 'admin_appointments');
$router->get('/admin/doctors', 'admin_doctors');
$router->post('/admin/doctors', 'admin_doctor_create');
$router->post('/admin/doctors/{id}/toggle', 'admin_doctor_toggle');
$router->get('/admin/users', 'admin_users');
$router->post('/admin/users/{id}/toggle', 'admin_user_toggle');
$router->get('/admin/outbox', 'admin_outbox');
$router->post('/admin/automation', 'admin_run_automation');
