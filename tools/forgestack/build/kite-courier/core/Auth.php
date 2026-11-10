<?php
declare(strict_types=1);

final class Auth
{
    private static ?array $cached = null;

    public static function attempt(string $email, string $password, string $expectedRole = ''): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return false;
        }
        if (self::throttled($email)) {
            flash('error', 'Too many sign-in attempts. Please wait 15 minutes and try again.');
            return false;
        }

        $user = Db::one('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        $valid = $user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash']);
        if (!$valid) {
            self::recordFailure($email);
            return false;
        }
        if ($expectedRole !== '' && $user['role'] !== $expectedRole && $user['role'] !== 'admin') {
            self::recordFailure($email);
            return false;
        }

        self::clearFailures($email);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Db::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $user['id']]);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['last_activity'] = time();
        Db::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        self::$cached = $user;
        return true;
    }

    public static function user(): ?array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $user = Db::one('SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1', [(int)$_SESSION['user_id']]);
        if (!$user) {
            self::logout();
            return null;
        }
        self::$cached = $user;
        return $user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): string
    {
        return self::user()['role'] ?? 'guest';
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '/';
            flash('error', 'Please sign in to continue.');
            redirect('/login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!self::is(...$roles)) {
            http_response_code(403);
            view('errors/403', ['title' => 'Access denied']);
            exit;
        }
    }

    public static function logout(): void
    {
        self::$cached = null;
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function register(array $data, string $role = 'member'): int
    {
        $email = strtolower(trim((string)($data['email'] ?? '')));
        if (Db::value('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) {
            throw new RuntimeException('An account with that email already exists.');
        }
        return Db::insert('users', [
            'name' => trim((string)$data['name']),
            'email' => $email,
            'phone' => trim((string)($data['phone'] ?? '')),
            'password_hash' => password_hash((string)$data['password'], PASSWORD_DEFAULT),
            'role' => in_array($role, ['admin', 'patient', 'doctor', 'farmer', 'buyer', 'dispatcher', 'courier', 'member', 'applicant', 'employer'], true) ? $role : 'member',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function throttled(string $email): bool
    {
        $since = date('Y-m-d H:i:s', time() - 900);
        $count = (int)Db::value(
            'SELECT COUNT(*) FROM login_attempts WHERE (email = ? OR ip = ?) AND attempted_at >= ?',
            [$email, client_ip(), $since]
        );
        return $count >= 8;
    }

    private static function recordFailure(string $email): void
    {
        try {
            Db::insert('login_attempts', [
                'email' => $email,
                'ip' => client_ip(),
                'attempted_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            // Throttling must never block a legitimate request path.
        }
    }

    private static function clearFailures(string $email): void
    {
        Db::run('DELETE FROM login_attempts WHERE email = ? OR ip = ?', [$email, client_ip()]);
    }
}
