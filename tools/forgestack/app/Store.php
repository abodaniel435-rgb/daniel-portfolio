<?php
/**
 * Store — the project registry (data/projects.json). Writes are atomic and
 * locked so the CLI and the web UI can be used at the same time.
 */
declare(strict_types=1);

final class Store
{
    private static function path(): string
    {
        return FS_ROOT . '/data/projects.json';
    }

    public static function all(): array
    {
        $raw = @file_get_contents(self::path());
        $data = json_decode($raw ?: '[]', true);
        if (!is_array($data)) {
            return [];
        }
        usort($data, static fn ($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $data;
    }

    public static function find(string $id): ?array
    {
        foreach (self::all() as $project) {
            if (($project['id'] ?? '') === $id) {
                return $project;
            }
        }
        return null;
    }

    public static function findBySlug(string $slug): ?array
    {
        foreach (self::all() as $project) {
            if (($project['slug'] ?? '') === $slug) {
                return $project;
            }
        }
        return null;
    }

    public static function save(array $project): void
    {
        $projects = self::all();
        $replaced = false;
        foreach ($projects as $i => $existing) {
            if (($existing['id'] ?? '') === ($project['id'] ?? '')) {
                $projects[$i] = $project;
                $replaced = true;
                break;
            }
        }
        if (!$replaced) {
            $projects[] = $project;
        }
        self::write($projects);
    }

    public static function update(string $id, array $patch): ?array
    {
        $project = self::find($id);
        if (!$project) {
            return null;
        }
        foreach ($patch as $key => $value) {
            $project[$key] = $value;
        }
        $project['updated_at'] = date('c');
        self::save($project);
        return $project;
    }

    public static function nextSlug(string $base): string
    {
        $slug = $base;
        $n = 2;
        while (self::findBySlug($slug) || is_dir(FS_ROOT . '/build/' . $slug)) {
            $slug = $base . '-' . $n;
            $n++;
        }
        return $slug;
    }

    public static function newId(): string
    {
        return 'fs_' . date('ymd_His') . '_' . bin2hex(random_bytes(3));
    }

    public static function stats(): array
    {
        $projects = self::all();
        $files = 0;
        $bytes = 0;
        $dbs = 0;
        foreach ($projects as $project) {
            $files += (int)($project['manifest']['count'] ?? 0);
            $bytes += (int)($project['manifest']['bytes'] ?? 0);
            if (($project['database']['status'] ?? '') === 'ready') {
                $dbs++;
            }
        }
        return [
            'projects' => count($projects),
            'files' => $files,
            'bytes' => $bytes,
            'databases' => $dbs,
        ];
    }

    public static function appendPortfolio(array $entry): void
    {
        $path = FS_ROOT . '/data/portfolio.json';
        $portfolio = json_decode((string)@file_get_contents($path) ?: '[]', true);
        if (!is_array($portfolio)) {
            $portfolio = [];
        }
        $portfolio = array_values(array_filter($portfolio, static fn ($item) => ($item['source'] ?? '') !== ($entry['source'] ?? '')));
        $portfolio[] = $entry;
        self::writeJson($path, $portfolio);
    }

    private static function write(array $projects): void
    {
        self::writeJson(self::path(), array_values($projects));
    }

    private static function writeJson(string $path, array $data): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $tmp = $path . '.tmp-' . bin2hex(random_bytes(4));
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Could not encode the project registry.');
        }
        file_put_contents($tmp, $json . PHP_EOL, LOCK_EX);
        rename($tmp, $path);
    }
}
