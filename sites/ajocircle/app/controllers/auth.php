<?php
declare(strict_types=1);

function auth_login_form(): void
{
    if (Auth::check()) {
        redirect('/dashboard');
    }
    view('auth/login', ['title' => 'Sign in — AjoCircle'], null);
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
        $user = Auth::user();
        audit('auth.login', 'User #' . $user['id']);
        flash('success', 'Welcome back, ' . $user['name'] . '.');
        redirect($intended);
    }
    remember_errors(['email' => 'That email and password combination is not correct.']);
    remember_old(['email' => $email]);
    redirect('/login');
}

function auth_register_form(): void
{
    if (Auth::check()) {
        redirect('/dashboard');
    }
    view('auth/register', ['title' => 'Create your account — AjoCircle'], null);
}

function auth_register(): void
{
    verify_csrf();
    $input = $_POST;
    $v = (new Validator($input))
        ->required('name', 'email', 'phone', 'password')
        ->email('email')
        ->max('name', 120)
        ->max('phone', 40)
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
        ], 'member');
        $user = Db::one('SELECT * FROM users WHERE id = ?', [$id]);
        Auth::attempt((string)$v->value('email'), (string)$v->value('password'));
        clear_errors();
        clear_old();
        audit('member.registered', 'User #' . $id);
        if ($user) {
            try {
                notify_welcome($user);
            } catch (Throwable) {
                // Email queueing must never block sign-up.
            }
        }
        flash('success', 'Your AjoCircle account is ready. Browse the directory to join a circle.');
        redirect('/circles');
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
    redirect(Auth::role() === 'admin' ? '/admin' : '/dashboard');
}
