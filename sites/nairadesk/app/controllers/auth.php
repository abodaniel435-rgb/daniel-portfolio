<?php
declare(strict_types=1);

function auth_register_form(): void
{
    if (Auth::check()) {
        redirect('/dashboard');
    }
    view('auth/register', ['title' => 'Create your workspace — NairaDesk']);
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
        ], 'employer');

        Db::insert('companies', [
            'owner_id' => $id,
            'name' => trim((string)($input['company'] ?? '')) ?: ((string)$v->value('name') . ' (workspace)'),
            'state' => 'Lagos',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Desk::audit('account.create', 'User #' . $id);
        // Signing the new account straight in is what makes "Create a
        // workspace" one action instead of two; the session id is replaced in
        // Auth::login() so the pre-login id never names an authenticated user.
        Auth::login($id);
        flash('success', 'Your workspace is ready. Add your first member of staff.');
        redirect('/staff');
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
        redirect('/dashboard');
    }
    view('auth/login', ['title' => 'Sign in — NairaDesk']);
}

function auth_login(): void
{
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (Auth::attempt($email, $password)) {
        clear_errors();
        $user = Auth::user();
        Desk::audit('auth.login', 'User #' . $user['id']);
        flash('success', 'Welcome back, ' . $user['name'] . '.');
        redirect(Auth::is('admin') ? '/admin' : '/dashboard');
    }

    remember_errors(['email' => 'That email and password combination is not correct.']);
    remember_old(['email' => $email]);
    redirect('/login');
}

function auth_logout(): void
{
    verify_csrf();
    Desk::audit('auth.logout', Auth::id() ? 'User #' . Auth::id() : 'anonymous');
    Auth::logout();
    flash('success', 'Signed out.');
    redirect('/');
}
