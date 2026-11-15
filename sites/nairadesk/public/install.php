<?php
declare(strict_types=1);

require dirname(__DIR__) . '/core/bootstrap.php';

if (Installer::installed()) {
    view('install', ['installed' => true], null);
    exit;
}

$expectedKey = (string)env('INSTALL_KEY', '');
$error = '';
// The administrator email is prefilled by the install view straight from
// .env. The password is never put into the page — it is typed once, hashed,
// and only the hash reaches MySQL.

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $key = (string)($_POST['install_key'] ?? '');
    $name = trim((string)($_POST['admin_name'] ?? ''));
    $email = trim((string)($_POST['admin_email'] ?? ''));
    $password = (string)($_POST['admin_password'] ?? '');

    if ($expectedKey === '' || !hash_equals($expectedKey, $key)) {
        $error = 'The installation key does not match INSTALL_KEY in your .env file.';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid administrator name and email address.';
    } elseif (strlen($password) < 10) {
        $error = 'The administrator password must be at least 10 characters.';
    } else {
        try {
            Installer::runSqlFile(BASE_PATH . '/database/schema.sql');
            Installer::runSqlFile(BASE_PATH . '/database/seed.sql');
            Installer::createUser($name, $email, $password, 'admin');
            Installer::lock();
            flash('success', 'Installed. Sign in as the administrator.');
            redirect('/login');
        } catch (Throwable $e) {
            $error = 'Installation failed: ' . $e->getMessage();
        }
    }
}

view('install', [
    'installed' => false,
    'error' => $error,
], null);
