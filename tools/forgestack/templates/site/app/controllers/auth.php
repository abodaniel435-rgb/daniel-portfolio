<?php
declare(strict_types=1);

function auth_login_form(): void
{
    if (Auth::check()) {
        redirect(Auth::is('admin') ? '/admin' : '/portal');
    }
    view('auth/login', ['title' => 'Sign in — ' . cfg('app_name'), 'area' => 'auth']);
}

function auth_login(): void
{
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (Auth::attempt($email, $password)) {
        clear_errors();
        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);
        flash('success', 'Welcome back, ' . Auth::user()['name'] . '.');
        redirect($intended ?: (Auth::is('admin') ? '/admin' : '/portal'));
    }
    remember_errors(['email' => 'Email or password is incorrect.']);
    remember_old(['email' => $email]);
    redirect('/login');
}

function auth_register_form(): void
{
    if (Auth::check()) {
        redirect('/portal');
    }
    view('auth/register', ['title' => 'Create an account — ' . cfg('app_name'), 'area' => 'auth']);
}

function auth_register(): void
{
    verify_csrf();
    $v = (new Validator($_POST))
        ->required('name', 'email', 'phone', 'password')
        ->email('email')
        ->max('name', 120)
        ->min('password', 10)
        ->matches('password', 'password_confirm');
    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($_POST);
        redirect('/register');
    }
    try {
        Auth::register([
            'name' => (string)$v->value('name'),
            'email' => (string)$v->value('email'),
            'phone' => (string)$v->value('phone'),
            'password' => (string)$v->value('password'),
        ], 'member');
        audit('account.created', (string)$v->value('email'));
        Auth::attempt((string)$v->value('email'), (string)$v->value('password'));
        clear_old();
        clear_errors();
        flash('success', 'Your account is ready. Your requests will appear here.');
        redirect('/portal');
    } catch (Throwable $e) {
        remember_errors(['email' => $e->getMessage()]);
        remember_old($_POST);
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

function portal_index(): void
{
    Auth::requireLogin();
    if (Auth::is('admin')) {
        redirect('/admin');
    }
    $user = Auth::user();
    view('portal', [
        'title' => (string)cfg('labels.portal') . ' — ' . cfg('app_name'),
        'records' => customer_records((int)$user['id'], (string)$user['email']),
        'orders' => customer_orders((int)$user['id'], (string)$user['email']),
        'area' => 'portal',
    ]);
}
