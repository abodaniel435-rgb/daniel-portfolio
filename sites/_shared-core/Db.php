<?php
declare(strict_types=1);

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $c = $GLOBALS['app_config']['db'];
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
        self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            str_replace('`', '', $table),
            implode('`,`', $cols),
            implode(',', $placeholders)
        );
        self::run($sql, $data);
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        if ($data === []) {
            return 0;
        }
        $cols = array_keys($data);
        $quote = static function (string $name): string {
            return '`' . str_replace('`', '', $name) . '`';
        };

        /* PDO refuses a statement that mixes `?` and named placeholders, and the
           old code always wrote the SET clause with names — so `Db::update($t,
           $d, 'id = ?', [$id])` died with HY093 at runtime. Write SET in
           whichever style the caller used for WHERE instead. */
        if (str_contains($where, '?')) {
            $sets = implode(', ', array_map(static fn ($c) => $quote($c) . ' = ?', $cols));
            $sql = sprintf('UPDATE %s SET %s WHERE %s', $quote($table), $sets, $where);
            return self::run($sql, array_merge(array_values($data), array_values($whereParams)))->rowCount();
        }

        $sets = implode(', ', array_map(static fn ($c) => $quote($c) . ' = :' . $c, $cols));
        $sql = sprintf('UPDATE %s SET %s WHERE %s', $quote($table), $sets, $where);
        return self::run($sql, array_merge($data, $whereParams))->rowCount();
    }

    public static function table_exists(string $table): bool
    {
        return (bool)self::value(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$table]
        );
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
