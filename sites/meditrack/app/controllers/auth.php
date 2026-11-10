<?php
declare(strict_types=1);

function auth_login_form(): void
{
    if (Auth::check()) {
        redirect('/dashboard');
    }
    view('auth/login', ['title' => 'Sign in — MediTrack'], null);
}

function auth_login(): void
{
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (Auth::attempt($email, $password)) {
        clear_errors();
        $intended = $_SESSION['intended'] ?? '/dashboard';
        unset($_SESSION['intended']);
        flash('success', 'Welcome back, ' . Auth::user()['name'] . '.');
        redirect($intended);
    }
    remember_errors(['email' => 'Email or password is incorrect.']);
    remember_old(['email' => $email]);
    redirect('/login');
}

function auth_register_form(): void
{
    if (Auth::check()) {
        redirect('/dashboard');
    }
    view('auth/register', ['title' => 'Create a patient account — MediTrack'], null);
}

function auth_register(): void
{
    verify_csrf();
    $input = $_POST;
    $v = (new Validator($input))
        ->required('name', 'email', 'phone', 'password')
        ->email('email')
        ->max('name', 120)
        ->min('password', 10)
        ->matches('password', 'password_confirm');

    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($input);
        redirect('/register');
    }
    try {
        $id = Auth::register([
            'name' => $v->value('name'),
            'email' => $v->value('email'),
            'phone' => $v->value('phone'),
            'password' => $v->value('password'),
        ], 'patient');
        audit('patient.registered', 'User #' . $id);
        Auth::attempt($v->value('email'), $v->value('password'));
        clear_errors();
        clear_old();
        flash('success', 'Your patient account is ready.');
        redirect('/patient/book');
    } catch (Throwable $e) {
        remember_errors(['email' => $e->getMessage()]);
        remember_old($input);
        redirect('/register');
    }
}

function auth_logout(): void
{
    verify_csrf();
    Auth::logout();
    flash('success', 'You have been signed out.');
    redirect('/');
}

function dashboard_redirect(): void
{
    Auth::requireLogin();
    match (Auth::role()) {
        'admin' => redirect('/admin'),
        'doctor' => redirect('/doctor/schedule'),
        default => redirect('/patient/appointments'),
    };
}
