<?php
/**
 * CLI installer. Usage:
 *   php core/cli/install.php
 * Env: ADMIN_EMAIL, ADMIN_PASSWORD, DEMO_PASSWORD (optional)
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

if (Installer::installed()) {
    fwrite(STDERR, "Already installed. Remove storage/installed.lock to reinstall.\n");
    exit(1);
}

$adminEmail = (string)env('ADMIN_EMAIL', 'abodaniel435@gmail.com');
$adminPassword = (string)env('ADMIN_PASSWORD', '');
$demoPassword = (string)env('DEMO_PASSWORD', '');

if (strlen($adminPassword) < 10) {
    fwrite(STDERR, "Set ADMIN_PASSWORD (>= 10 chars) in .env or the environment.\n");
    exit(1);
}

$schema = BASE_PATH . '/database/schema.sql';
$seed = BASE_PATH . '/database/seed.sql';

try {
    $count = Installer::runSqlFile($schema);
    echo "Schema applied ({$count} statements).\n";
    if (is_file($seed)) {
        $count = Installer::runSqlFile($seed);
        echo "Seed data applied ({$count} statements).\n";
    }

    Installer::createUser('Daniel Abo', $adminEmail, $adminPassword, 'admin', '+2347038082491');
    echo "Administrator created: {$adminEmail}\n";

    $demoUsers = BASE_PATH . '/app/demo_users.php';
    if ($demoPassword !== '' && is_file($demoUsers)) {
        $users = require $demoUsers;
        foreach ($users as $user) {
            Installer::createUser($user['name'], $user['email'], $demoPassword, $user['role'], $user['phone'] ?? '');
        }
        echo count($users) . " demo accounts created.\n";

        $demoSeed = BASE_PATH . '/app/demo_seed.php';
        if (is_file($demoSeed)) {
            $seedFn = require $demoSeed;
            if (is_callable($seedFn)) {
                $seedFn();
                echo "Demo domain data seeded.\n";
            }
        }
    }

    Installer::lock();
    echo "Installation complete.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Installation failed: ' . $e->getMessage() . "\n");
    exit(1);
}
