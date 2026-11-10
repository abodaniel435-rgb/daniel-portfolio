<?php
declare(strict_types=1);

require dirname(__DIR__) . '/core/bootstrap.php';

if (Installer::installed()) {
    view('install', ['installed' => true], null);
    exit;
}

$expectedKey = (string)env('INSTALL_KEY', '');
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $key = (string)($_POST['install_key'] ?? '');
    $name = trim((string)($_POST['admin_name'] ?? ''));
    $email = trim((string)($_POST['admin_email'] ?? ''));
    $password = (string)($_POST['admin_password'] ?? '');
    $demoPassword = (string)($_POST['demo_password'] ?? '');

    if ($expectedKey === '' || !hash_equals($expectedKey, $key)) {
        $error = 'The installation key does not match INSTALL_KEY in your .env file.';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid administrator name and email address.';
    } elseif (strlen($password) < 10) {
        $error = 'The administrator password must be at least 10 characters.';
    } elseif ($demoPassword !== '' && strlen($demoPassword) < 10) {
        $error = 'The demo password must be at least 10 characters (or leave it blank).';
    } else {
        try {
            Installer::runSqlFile(BASE_PATH . '/database/schema.sql');
            if (is_file(BASE_PATH . '/database/seed.sql')) {
                Installer::runSqlFile(BASE_PATH . '/database/seed.sql');
            }
            Installer::createUser($name, $email, $password, 'admin');

            if ($demoPassword !== '') {
                $demoUsers = require BASE_PATH . '/app/demo_users.php';
                foreach ($demoUsers as $user) {
                    Installer::createUser($user['name'], $user['email'], $demoPassword, $user['role'], $user['phone'] ?? '');
                }
                $demoSeed = BASE_PATH . '/app/demo_seed.php';
                if (is_file($demoSeed)) {
                    $seedFn = require $demoSeed;
                    if (is_callable($seedFn)) {
                        $seedFn();
                    }
                }
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
