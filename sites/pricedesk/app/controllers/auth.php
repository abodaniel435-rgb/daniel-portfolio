<?php
declare(strict_types=1);

/**
 * PriceDesk authentication. Registration makes a member of the cooperative —
 * there is no self-service administrator, and the role is never taken from the
 * posted form.
 */

function auth_register_form(): void
{
    if (Auth::check()) {
        redirect('/report');
    }
    view('auth/register', ['title' => 'Join the board — PriceDesk']);
}

function auth_register(): void
{
    verify_csrf();
    $input = $_POST;
    $v = (new Validator($input))
        ->required('name', 'email', 'password')
        ->email('email')
        ->max('name', 120)
        ->min('password', 10)
        ->matches('password', 'password_confirm');

    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old(['name' => $input['name'] ?? '', 'email' => $input['email'] ?? '']);
        redirect('/register');
    }

    try {
        $id = Auth::register([
            'name' => (string)$v->value('name'),
            'email' => (string)$v->value('email'),
            'phone' => trim((string)($input['phone'] ?? '')),
            'password' => (string)$v->value('password'),
        ], 'member');

        Board::audit('account.create', 'User #' . $id);
        Auth::login($id);
        flash('success', 'You are on the board. Today’s prices are the first thing it needs.');
        redirect('/report');
    } catch (Throwable $e) {
        remember_errors(['email' => str_contains($e->getMessage(), 'already exists')
            ? 'An account with that email already exists.'
            : 'That account could not be created. Please try again.']);
        remember_old(['name' => $input['name'] ?? '', 'email' => $input['email'] ?? '']);
        redirect('/register');
    }
}

function auth_login_form(): void
{
    if (Auth::check()) {
        redirect('/report');
    }
    view('auth/login', ['title' => 'Sign in — PriceDesk']);
}

function auth_login(): void
{
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (Auth::attempt($email, $password)) {
        clear_errors();
        $user = Auth::user();
        Board::audit('auth.login', 'User #' . $user['id']);
        flash('success', 'Welcome back, ' . $user['name'] . '.');
        redirect(Auth::is('admin') ? '/admin' : '/report');
    }

    remember_errors(['email' => 'That email and password combination is not correct.']);
    remember_old(['email' => $email]);
    redirect('/login');
}

function auth_logout(): void
{
    verify_csrf();
    Board::audit('auth.logout', Auth::id() ? 'User #' . Auth::id() : 'anonymous');
    Auth::logout();
    flash('success', 'Signed out.');
    redirect('/');
}
