<?php
/**
 * Preflight — proves whether this host can run a generated stack.
 * A failure blocks generation; warnings are disclosed but do not block.
 */
declare(strict_types=1);

final class Preflight
{
    public static function run(): array
    {
        $checks = [];

        $version = PHP_VERSION;
        $checks[] = self::check(
            'php',
            'PHP version',
            version_compare($version, '8.1.0', '>=') ? 'pass' : 'fail',
            $version . ' detected. Generated sites target PHP 8.1+ (tested on 8.2).',
            version_compare($version, '8.1.0', '>=')
        );

        $required = [
            'pdo_mysql' => 'database access through PDO',
            'mbstring' => 'UTF-8 text handling',
            'openssl' => 'secure tokens and TLS helpers',
            'json' => 'blueprint and portfolio records',
        ];
        foreach ($required as $extension => $why) {
            $loaded = extension_loaded($extension);
            $checks[] = self::check(
                'ext_' . $extension,
                'Extension: ' . $extension,
                $loaded ? 'pass' : 'fail',
                $loaded ? 'Loaded — ' . $why . '.' : 'Missing — required for ' . $why . '. Ask the host to enable it.',
                true
            );
        }

        foreach (['build' => 'generated sites', 'data' => 'project registry'] as $dir => $why) {
            $path = FS_ROOT . '/' . $dir;
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
            $ok = is_dir($path) && is_writable($path);
            $checks[] = self::check(
                'writable_' . $dir,
                'Writable: ' . $dir . '/',
                $ok ? 'pass' : 'fail',
                $ok ? 'Writable — used for ' . $why . '.' : 'Not writable. Fix permissions (0755/0775) before generating.',
                true
            );
        }

        $shared = $GLOBALS['fs_config']['shared_core'];
        $sharedOk = is_dir($shared) && is_file($shared . '/bootstrap.php');
        $checks[] = self::check(
            'shared_core',
            'Shared PHP core',
            $sharedOk ? 'pass' : 'fail',
            $sharedOk ? 'Found at ' . $shared . ' (copied into every generated site).' : 'Missing at ' . $shared . '. Set FORGE_SHARED_CORE.',
            true
        );

        $db = self::database();
        $checks[] = self::check(
            'mysql',
            'MySQL connection',
            $db['ok'] ? 'pass' : 'warn',
            $db['ok']
                ? 'Connected to ' . $db['version'] . ' at ' . self::dbLabel() . '. Databases can be created and schemas installed.'
                : 'Unavailable (' . $db['error'] . '). Generation still proceeds; the database step is marked “skipped — schema included”.',
            false
        );

        $checks[] = self::check(
            'node',
            'Node.js service',
            'pass',
            'Not required. Generated sites are plain PHP with no build step and no Node runtime.',
            false
        );
        $checks[] = self::check(
            'redis',
            'Redis / background worker',
            'pass',
            'Not required. Automation runs from cPanel cron and writes to MySQL and the email outbox.',
            false
        );

        $free = @disk_free_space(FS_ROOT);
        $checks[] = self::check(
            'disk',
            'Disk space',
            ($free === false || $free > 250 * 1024 * 1024) ? 'pass' : 'warn',
            $free === false ? 'Could not read free space.' : fs_format_bytes((int)$free) . ' free on the workspace volume.',
            false
        );

        $overall = 'pass';
        foreach ($checks as $check) {
            if ($check['status'] === 'fail') {
                $overall = 'fail';
                break;
            }
            if ($check['status'] === 'warn') {
                $overall = 'warn';
            }
        }

        return [
            'status' => $overall,
            'can_generate' => $overall !== 'fail',
            'generated_at' => date('c'),
            'php' => $version,
            'sapi' => PHP_SAPI,
            'database' => $db,
            'checks' => $checks,
            'target' => [
                'php' => '8.1+ (8.2 recommended)',
                'extensions' => array_keys($required),
                'web_server' => 'Apache with mod_rewrite, or any host that can route to public/index.php',
                'database' => 'MySQL 5.7+/8.x or MariaDB 10.3+',
                'node' => false,
                'redis' => false,
            ],
        ];
    }

    public static function canGenerate(): bool
    {
        $result = self::run();
        return (bool)$result['can_generate'];
    }

    public static function database(): array
    {
        $c = $GLOBALS['fs_config'];
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $c['host'], $c['port']),
                $c['user'],
                $c['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 4]
            );
            $version = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
            return ['ok' => true, 'version' => $version, 'error' => '', 'host' => $c['host'], 'port' => (int)$c['port']];
        } catch (Throwable $e) {
            return ['ok' => false, 'version' => '', 'error' => $e->getMessage(), 'host' => $c['host'], 'port' => (int)$c['port']];
        }
    }

    public static function dbLabel(): string
    {
        $c = $GLOBALS['fs_config'];
        return $c['host'] . ':' . $c['port'];
    }

    private static function check(string $id, string $label, string $status, string $detail, bool $required): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'status' => $status,
            'detail' => $detail,
            'required' => $required,
        ];
    }
}
