<?php
declare(strict_types=1);

final class Installer
{
    public static function installed(): bool
    {
        return is_file(BASE_PATH . '/storage/installed.lock');
    }

    public static function lock(): void
    {
        $dir = BASE_PATH . '/storage';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir . '/installed.lock', 'Installed ' . date('c') . PHP_EOL);
    }

    /**
     * Splits a .sql file into executable statements while respecting quoted
     * strings and line/block comments.
     */
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
                if ($char === '\\' ) {
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

    public static function runSqlFile(string $path): int
    {
        if (!is_file($path)) {
            throw new RuntimeException("SQL file not found: {$path}");
        }
        $statements = self::statements((string)file_get_contents($path));
        $pdo = Db::pdo();
        foreach ($statements as $statement) {
            $result = $pdo->query($statement);
            if ($result instanceof PDOStatement) {
                $result->closeCursor();
            }
        }
        return count($statements);
    }

    public static function createUser(string $name, string $email, string $password, string $role, string $phone = ''): int
    {
        if (strlen($password) < 10) {
            throw new RuntimeException('Administrator password must be at least 10 characters.');
        }
        if (Db::value('SELECT COUNT(*) FROM users WHERE email = ?', [strtolower($email)])) {
            throw new RuntimeException("User already exists: {$email}");
        }
        return Db::insert('users', [
            'name' => $name,
            'email' => strtolower($email),
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
