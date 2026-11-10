<?php
/**
 * Database — optionally creates the generated site's database and applies its
 * schema + seed. If MySQL is unreachable the result is “skipped — schema
 * included”, never a false success.
 */
declare(strict_types=1);

final class Database
{
    public static function install(string $siteDir, array $project): array
    {
        $generation = $project['blueprint']['generation'] ?? [];
        $intake = $project['blueprint']['intake'] ?? [];
        $dbName = (string)($generation['db_name'] ?? '');

        if (!$dbName) {
            return self::result('failed', $dbName, ['No database name was derived.'], 'A database name is required.');
        }
        if (empty($intake['mysql_available'])) {
            return self::result('skipped', $dbName, [
                'MySQL was marked unavailable at intake.',
                'Schema written to database/schema.sql and seed to database/seed.sql.',
            ], 'skipped — schema included');
        }
        if (empty($generation['install_db'])) {
            return self::result('skipped', $dbName, [
                'Automatic installation was disabled for this build.',
                'Configure .env then run public/install.php or core/cli/install.php.',
            ], 'skipped — schema included');
        }

        $env = self::readEnv($siteDir . '/.env');
        $host = (string)($env['DB_HOST'] ?? $GLOBALS['fs_config']['host']);
        $port = (string)($env['DB_PORT'] ?? $GLOBALS['fs_config']['port']);
        $user = (string)($env['DB_USER'] ?? $GLOBALS['fs_config']['user']);
        $pass = (string)($env['DB_PASS'] ?? $GLOBALS['fs_config']['pass']);
        $steps = [];

        try {
            $server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port), $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (Throwable $e) {
            return self::result('skipped', $dbName, [
                'Could not connect to MySQL at ' . $host . ':' . $port . ': ' . $e->getMessage(),
                'Schema written to database/schema.sql; the deployment guide covers the cPanel install path.',
            ], 'skipped — schema included');
        }

        try {
            $server->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                str_replace('`', '', $dbName)
            ));
            $steps[] = 'Created database ' . $dbName . ' (utf8mb4).';

            $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName), $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $schemaFile = $siteDir . '/database/schema.sql';
            $seedFile = $siteDir . '/database/seed.sql';
            if (!is_file($schemaFile)) {
                return self::result('failed', $dbName, $steps, 'database/schema.sql is missing from the build.');
            }

            $count = self::runSqlFile($pdo, $schemaFile);
            $steps[] = 'Applied schema: ' . $count . ' statements.';

            if (is_file($seedFile)) {
                $seedCount = self::runSqlFile($pdo, $seedFile);
                $steps[] = 'Applied reference seed: ' . $seedCount . ' statements.';
            }

            $adminEmail = strtolower((string)($env['ADMIN_EMAIL'] ?? ''));
            $adminPass = (string)($env['ADMIN_PASSWORD'] ?? '');
            if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                return self::result('failed', $dbName, $steps, 'ADMIN_EMAIL in .env is missing or invalid.');
            }
            if (strlen($adminPass) < 10) {
                return self::result('failed', $dbName, $steps, 'ADMIN_PASSWORD in .env must be at least 10 characters.');
            }

            $exists = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE email = ' . $pdo->quote($adminEmail))->fetchColumn();
            if ($exists === 0) {
                $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())');
                $stmt->execute([
                    (string)($env['ADMIN_NAME'] ?? 'Site Administrator'),
                    $adminEmail,
                    (string)($intake['contact_phone'] ?? ''),
                    password_hash($adminPass, PASSWORD_DEFAULT),
                    'admin',
                ]);
                $steps[] = 'Created administrator ' . $adminEmail . ' with password_hash().';
            } else {
                $steps[] = 'Administrator ' . $adminEmail . ' already exists; left unchanged.';
            }

            $storage = $siteDir . '/storage';
            if (!is_dir($storage)) {
                mkdir($storage, 0775, true);
            }
            file_put_contents($storage . '/installed.lock', 'Installed ' . date('c') . PHP_EOL, LOCK_EX);
            $steps[] = 'Wrote storage/installed.lock (blocks the one-time installer).';

            $tables = $pdo->query('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name')->fetchAll(PDO::FETCH_COLUMN);
            $steps[] = count($tables) . ' tables present: ' . implode(', ', $tables) . '.';

            return self::result('ready', $dbName, $steps, 'Installed into ' . $host . ':' . $port . '/' . $dbName . '.', [
                'host' => $host,
                'port' => (int)$port,
                'name' => $dbName,
                'tables' => $tables,
            ]);
        } catch (Throwable $e) {
            return self::result('failed', $dbName, array_merge($steps, ['Error: ' . $e->getMessage()]), 'Database installation failed: ' . $e->getMessage());
        }
    }

    /** Drop a generated database (used by the CLI only). */
    public static function drop(string $dbName): bool
    {
        $c = $GLOBALS['fs_config'];
        try {
            $pdo = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $c['host'], $c['port']), $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('DROP DATABASE IF EXISTS `' . str_replace('`', '', $dbName) . '`');
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public static function readEnv(string $file): array
    {
        $env = [];
        if (!is_file($file)) {
            return $env;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[0] === substr($value, -1)) {
                $value = substr($value, 1, -1);
            }
            $env[trim($key)] = $value;
        }
        return $env;
    }

    private static function runSqlFile(PDO $pdo, string $file): int
    {
        $sql = (string)file_get_contents($file);
        $statements = self::statements($sql);
        foreach ($statements as $statement) {
            $result = $pdo->query($statement);
            if ($result instanceof PDOStatement) {
                $result->closeCursor();
            }
        }
        return count($statements);
    }

    /** Quote-aware statement splitter (mirrors the shared Installer). */
    public static function statements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $quote = '';
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';
            if (!$inString && $char === '-' && $next === '-') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }
            if (!$inString && $char === '/' && $next === '*') {
                $i += 2;
                while ($i < $len - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }
            if ($inString) {
                if ($char === '\\') {
                    $buffer .= $char . $next;
                    $i++;
                    continue;
                }
                if ($char === $quote) {
                    if ($next === $quote) {
                        $buffer .= $char . $next;
                        $i++;
                        continue;
                    }
                    $inString = false;
                }
                $buffer .= $char;
                continue;
            }
            if ($char === "'" || $char === '"') {
                $inString = true;
                $quote = $char;
                $buffer .= $char;
                continue;
            }
            if ($char === ';') {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }
        $trimmed = trim($buffer);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }
        return $statements;
    }

    private static function result(string $status, string $dbName, array $steps, string $message, array $connection = []): array
    {
        return [
            'status' => $status,
            'database' => $dbName,
            'label' => $message,
            'message' => $message,
            'steps' => $steps,
            'connection' => $connection,
            'tables' => $connection['tables'] ?? [],
            'checked_at' => date('c'),
        ];
    }
}
