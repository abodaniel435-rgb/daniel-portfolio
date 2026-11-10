<?php
declare(strict_types=1);

final class Automation
{
    public static function run(string $site): int
    {
        $lockFile = BASE_PATH . '/storage/cron.lock';
        $handle = fopen($lockFile, 'c');
        if (!$handle || !flock($handle, LOCK_EX | LOCK_NB)) {
            self::log($site, 'Skipped: another automation run is active.');
            return 0;
        }

        $tasksFile = BASE_PATH . '/app/automation.php';
        if (!is_file($tasksFile)) {
            self::log($site, 'No automation tasks configured.');
            flock($handle, LOCK_UN);
            fclose($handle);
            return 0;
        }

        $tasks = require $tasksFile;
        $ran = 0;
        foreach ($tasks as $name => $task) {
            try {
                $result = $task();
                self::log($site, "{$name}: " . (is_string($result) ? $result : 'ok'));
                $ran++;
            } catch (Throwable $e) {
                self::log($site, "{$name}: FAILED — " . $e->getMessage());
            }
        }
        self::log($site, "Run complete ({$ran} tasks).");
        flock($handle, LOCK_UN);
        fclose($handle);
        return $ran;
    }

    public static function log(string $site, string $message): void
    {
        $dir = BASE_PATH . '/storage';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/automation.log',
            '[' . date('Y-m-d H:i:s') . "] {$site} — {$message}\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
