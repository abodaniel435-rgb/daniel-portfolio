<?php
declare(strict_types=1);

function auth_login_form(): void
{
    if (Auth::check()) {
        redirect('/dashboard');
    }
    view('auth/login', ['title' => 'Sign in — FarmCoop'], null);
}

function auth_login(): void
{
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (Auth::attempt($email, $password)) {
        clear_errors();
        clear_old();
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
    view('auth/register', [
        'title' => 'Create an account — FarmCoop',
        'categories' => produce_categories(),
    ], null);
}

function auth_register(): void
{
    verify_csrf();
    $input = $_POST;
    $role = (string)($input['role'] ?? 'buyer');
    $v = (new Validator($input))
        ->required('name', 'email', 'phone', 'password')
        ->email('email')
        ->max('name', 120)
        ->max('phone', 40)
        ->min('password', 10)
        ->matches('password', 'password_confirm')
        ->in('role', ['farmer', 'buyer']);

    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($input);
        redirect('/register');
    }
    if ($role === 'farmer') {
        $v->required('farm_name');
        if (!$v->passes()) {
            remember_errors($v->errors());
            remember_old($input);
            redirect('/register');
        }
    }

    try {
        $userId = Auth::register([
            'name' => $v->value('name'),
            'email' => $v->value('email'),
            'phone' => $v->value('phone'),
            'password' => $v->value('password'),
        ], $role);

        if ($role === 'farmer') {
            Db::insert('farm_profiles', [
                'user_id' => $userId,
                'farm_name' => trim((string)$input['farm_name']),
                'state' => in_array($_POST['state'] ?? '', nigerian_states(), true) ? (string)$_POST['state'] : '',
                'lga' => trim((string)($_POST['lga'] ?? '')),
                'produce_focus' => trim((string)($_POST['produce_focus'] ?? '')),
                'about' => '',
                'is_verified' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        audit($role . '.registered', 'User #' . $userId);
        Auth::attempt((string)$v->value('email'), (string)$v->value('password'));
        clear_errors();
        clear_old();

        if ($role === 'farmer') {
            flash('success', 'Your farm account is ready. Add your farm details so buyers know where their produce comes from.');
            redirect('/farmer/profile');
        }
        flash('success', 'Your buyer account is ready. Start with the live marketplace.');
        redirect('/marketplace');
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
        'farmer' => redirect('/farmer'),
        default => redirect('/buyer'),
    };
}
