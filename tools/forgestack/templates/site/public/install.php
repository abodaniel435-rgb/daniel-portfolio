<?php
/**
 * One-time installer. Protected by INSTALL_KEY.
 * The administrator is seeded from ADMIN_EMAIL / ADMIN_PASSWORD in .env —
 * credentials are never hardcoded in PHP.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/core/bootstrap.php';

if (Installer::installed()) {
    view('install', ['installed' => true, 'error' => ''], null);
    exit;
}

$expectedKey = (string)env('INSTALL_KEY', '');
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $key = (string)($_POST['install_key'] ?? '');
    $adminEmail = strtolower((string)env('ADMIN_EMAIL', ''));
    $adminPassword = (string)env('ADMIN_PASSWORD', '');
    $seedDemo = isset($_POST['seed_demo']);

    if ($expectedKey === '' || !hash_equals($expectedKey, $key)) {
        $error = 'The installation key does not match INSTALL_KEY in your .env file.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Set a valid ADMIN_EMAIL in .env before installing.';
    } elseif (strlen($adminPassword) < 10) {
        $error = 'ADMIN_PASSWORD in .env must be at least 10 characters.';
    } else {
        try {
            Installer::runSqlFile(BASE_PATH . '/database/schema.sql');
            if ($seedDemo && is_file(BASE_PATH . '/database/seed.sql')) {
                Installer::runSqlFile(BASE_PATH . '/database/seed.sql');
            }
            if (!Db::value('SELECT COUNT(*) FROM users WHERE email = ?', [$adminEmail])) {
                Installer::createUser((string)env('ADMIN_NAME', 'Site Administrator'), $adminEmail, $adminPassword, 'admin');
            }
            Installer::lock();
            flash('success', 'Installation complete. Sign in with your administrator account.');
            redirect('/login');
        } catch (Throwable $e) {
            $error = 'Installation failed: ' . $e->getMessage();
        }
    }
}

view('install', ['installed' => false, 'error' => $error], null);
