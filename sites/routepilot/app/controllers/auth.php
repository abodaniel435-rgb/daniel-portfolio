<?php
declare(strict_types=1);

function auth_login_form(): void
{
    if (Auth::check()) {
        redirect('/dashboard');
    }
    view('auth/login', ['title' => 'Sign in — RoutePilot dispatch desk'], null);
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
        flash('success', 'Signed in as ' . Auth::user()['name'] . '.');
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
    view('auth/register', ['title' => 'Courier sign-up — RoutePilot'], null);
}

/**
 * Courier self-registration only. Dispatchers and administrators are created
 * by an administrator from the admin console.
 */
function auth_register(): void
{
    verify_csrf();
    $input = $_POST;
    $v = (new Validator($input))
        ->required('name', 'email', 'phone', 'password', 'vehicle', 'zone')
        ->email('email')
        ->max('name', 120)
        ->max('zone', 80)
        ->min('password', 10)
        ->matches('password', 'password_confirm')
        ->in('vehicle', rp_vehicles());

    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($input);
        redirect('/register');
    }

    try {
        $userId = Db::transaction(function () use ($v): int {
            $userId = Auth::register([
                'name' => $v->value('name'),
                'email' => $v->value('email'),
                'phone' => $v->value('phone'),
                'password' => $v->value('password'),
            ], 'courier');
            Db::insert('couriers', [
                'user_id' => $userId,
                'vehicle' => (string)$v->value('vehicle'),
                'zone' => (string)$v->value('zone'),
                'plate_number' => mb_substr(trim((string)($_POST['plate_number'] ?? '')), 0, 24),
                'max_daily_shipments' => 12,
                'is_available' => 1,
            ]);
            return $userId;
        });
        audit('courier.registered', 'User #' . $userId);
        Auth::attempt((string)$v->value('email'), (string)$v->value('password'));
        clear_errors();
        clear_old();
        flash('success', 'Your rider account is live. Dispatch will assign parcels to your queue.');
        redirect('/courier/queue');
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
