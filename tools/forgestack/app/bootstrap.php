<?php
/**
 * ForgeStack — shared bootstrap for the agent itself.
 * Plain PHP 8.1+. No Composer, no external services.
 */
declare(strict_types=1);

define('FS_ROOT', dirname(__DIR__));
define('FS_VERSION', '1.0.0');
// Prefer the bundled core copy (ships with the package), fall back to the workspace copy.
$fs_bundled_core = FS_ROOT . '/core';
$fs_workspace_core = 'C:/Users/HI/Documents/Qoder/2026-11-07/e34d91d8/new-sites/_shared/core';
define('FS_SHARED_CORE', is_dir($fs_bundled_core) ? $fs_bundled_core : $fs_workspace_core);

date_default_timezone_set('Africa/Lagos');

/** Read an env value from the process env or Apache's SetEnv (which only fills $_SERVER on some SAPIs). */
function fs_env(string $key): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_SERVER[$key] ?? null;
    }
    return is_string($value) ? $value : null;
}

// Local host defaults; override with env vars when running the CLI or on the host.
$GLOBALS['fs_config'] = [
    'host' => fs_env('FORGE_DB_HOST') ?: '127.0.0.1',
    'port' => fs_env('FORGE_DB_PORT') ?: '3307',
    'user' => fs_env('FORGE_DB_USER') ?: 'root',
    'pass' => fs_env('FORGE_DB_PASS') ?? '',
    'profile' => fs_env('FORGE_PROFILE') ?: '',
    'base_url' => fs_env('FORGE_BASE_URL') ?: '',
    'shared_core' => fs_env('FORGE_SHARED_CORE') ?: FS_SHARED_CORE,
];

foreach (['Profiles', 'Blueprint', 'Store', 'Preflight', 'Database', 'Docs', 'Generator'] as $class) {
    require_once FS_ROOT . '/app/' . $class . '.php';
}

require_once FS_ROOT . '/app/Support.php';

/** Ensure the runtime directories exist and are writable. */
function fs_prepare_dirs(): void
{
    foreach ([FS_ROOT . '/data', FS_ROOT . '/build', FS_ROOT . '/data/tmp'] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
    $registry = FS_ROOT . '/data/projects.json';
    if (!is_file($registry)) {
        file_put_contents($registry, "[]\n", LOCK_EX);
    }
    $portfolio = FS_ROOT . '/data/portfolio.json';
    if (!is_file($portfolio)) {
        file_put_contents($portfolio, "[]\n", LOCK_EX);
    }
}

fs_prepare_dirs();
