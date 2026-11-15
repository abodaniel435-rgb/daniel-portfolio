<?php
/**
 * ForgeStack Core — shared bootstrap for cPanel-compatible PHP applications.
 * PHP 8.1+ / MySQL 5.7+ (MariaDB 10.3+) / Apache + .htaccess
 */
declare(strict_types=1);

define('CORE_VERSION', '1.0.0');
define('BASE_PATH', dirname(__DIR__));

/** Minimal .env loader (no Composer dependency). */
function load_env(string $file): void
{
    if (!is_file($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[0] === substr($value, -1)) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

load_env(BASE_PATH . '/.env');

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return match (strtolower((string)$value)) {
        'true', 'on', 'yes' => true,
        'false', 'off', 'no' => false,
        'null' => null,
        default => $value,
    };
}

$GLOBALS['app_config'] = [
    'name' => env('APP_NAME', 'ForgeStack App'),
    'base_url' => rtrim((string)env('APP_URL', ''), '/'),
    'timezone' => (string)env('APP_TIMEZONE', 'Africa/Lagos'),
    'db' => [
        'host' => (string)env('DB_HOST', '127.0.0.1'),
        'port' => (string)env('DB_PORT', '3306'),
        'name' => (string)env('DB_NAME', 'forge_app'),
        'user' => (string)env('DB_USER', 'root'),
        'pass' => (string)env('DB_PASS', ''),
    ],
    'mail' => [
        'enabled' => (bool)env('MAIL_ENABLED', false),
        'from' => (string)env('MAIL_FROM', 'no-reply@localhost'),
        'from_name' => (string)env('MAIL_FROM_NAME', (string)env('APP_NAME', 'ForgeStack App')),
    ],
    'install_key' => (string)env('INSTALL_KEY', ''),
    'debug' => (bool)env('APP_DEBUG', false),
];

date_default_timezone_set($GLOBALS['app_config']['timezone']);

if (PHP_SAPI !== 'cli') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('forge_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

if ($GLOBALS['app_config']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

$autoload = [
    'Db', 'Auth', 'Router', 'Mailer', 'Validator', 'Automation', 'Installer',
];
foreach ($autoload as $class) {
    require_once BASE_PATH . '/core/' . $class . '.php';
}
require_once BASE_PATH . '/core/helpers.php';

set_exception_handler(function (Throwable $e): void {
    error_log('[app] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Error: {$e->getMessage()}\n");
        exit(1);
    }
    http_response_code(500);
    if ($GLOBALS['app_config']['debug']) {
        echo '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Server error</title>'
            . '<div style="font:16px/1.6 system-ui;max-width:620px;margin:12vh auto;padding:0 24px">'
            . '<h1 style="font-size:1.6rem">Something went wrong</h1>'
            . '<p>The request could not be completed. Please try again shortly.</p>'
            . '<p><a href="' . htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8') . '">Return home</a></p></div>';
    }
    exit;
});
