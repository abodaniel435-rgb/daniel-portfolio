<?php
/**
 * ForgeStack CLI.
 *
 *   php cli/forge.php preflight
 *   php cli/forge.php new blueprint.json [--slug=my-site] [--db=forge_my_site]
 *                                     [--port=8101] [--admin-email=...]
 *                                     [--admin-password=...] [--url=...]
 *                                     [--thumbnail=assets/my-site.png]
 *                                     [--no-install] [--force]
 *   php cli/forge.php list [--json]
 *
 * Exit codes: 0 success, 1 usage/runtime error, 2 preflight failure.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("ForgeStack CLI is command-line only.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$argv = $_SERVER['argv'] ?? [];
$command = $argv[1] ?? 'help';
$args = array_slice($argv, 2);
$options = [];
$positional = [];
foreach ($args as $arg) {
    if (str_starts_with($arg, '--')) {
        $arg = substr($arg, 2);
        if (str_contains($arg, '=')) {
            [$key, $value] = explode('=', $arg, 2);
            $options[$key] = $value;
        } else {
            $options[$arg] = true;
        }
    } else {
        $positional[] = $arg;
    }
}

try {
    switch ($command) {
        case 'new':
            $file = $positional[0] ?? '';
            if ($file === '') {
                fwrite(STDERR, "Usage: php cli/forge.php new blueprint.json [options]\n");
                exit(1);
            }
            if (!is_file($file)) {
                fwrite(STDERR, "Blueprint not found: {$file}\n");
                exit(1);
            }
            $input = json_decode((string)file_get_contents($file), true);
            if (!is_array($input)) {
                fwrite(STDERR, "Blueprint must be a JSON object: {$file}\n");
                exit(1);
            }
            $errors = Blueprint::validate($input);
            if ($errors) {
                fwrite(STDERR, "Blueprint validation failed:\n");
                foreach ($errors as $field => $message) {
                    fwrite(STDERR, "  - {$field}: {$message}\n");
                }
                exit(1);
            }
            $slug = (string)($options['slug'] ?? '');
            if ($slug !== '' && (Store::findBySlug($slug) || is_dir(FS_ROOT . '/build/' . $slug)) && !isset($options['force'])) {
                fwrite(STDERR, "Slug '{$slug}' already exists. Pass --force to rebuild it.\n");
                exit(1);
            }
            if ($slug === '') {
                $slug = Store::nextSlug(fs_slug((string)$input['business_name'], 'site'));
            }
            $build = [
                'slug' => $slug,
                'db_name' => (string)($options['db'] ?? ('forge_' . $slug)),
                'port' => isset($options['port']) ? (int)$options['port'] : 0,
                'admin_email' => (string)($options['admin-email'] ?? $input['contact_email'] ?? ''),
                'admin_password' => (string)($options['admin-password'] ?? ''),
                'install_db' => !isset($options['no-install']),
                'app_url' => (string)($options['url'] ?? ''),
            ];
            $blueprint = Blueprint::build($input, $build);
            $result = SiteGenerator::generate($blueprint, [
                'overwrite' => isset($options['force']),
                'live_link' => (string)($options['url'] ?? $build['app_url']),
                'thumbnail' => (string)($options['thumbnail'] ?? ('assets/' . $slug . '.png')),
            ]);
            if (!$result['ok']) {
                fwrite(STDERR, $result['error'] . "\n");
                foreach ($result['preflight']['checks'] as $check) {
                    if ($check['status'] === 'fail') {
                        fwrite(STDERR, "  FAIL {$check['label']}: {$check['detail']}\n");
                    }
                }
                exit(2);
            }
            $project = $result['project'];
            echo "ForgeStack build complete\n";
            echo "  project     {$project['slug']} ({$project['id']})\n";
            echo "  business    {$project['name']}\n";
            echo "  folder      {$project['build_path']}\n";
            echo "  files       {$project['manifest']['count']} · " . fs_format_bytes((int)$project['manifest']['bytes']) . "\n";
            echo "  database    {$project['database']['label']}\n";
            echo "  admin       {$project['blueprint']['generation']['admin_email']}\n";
            $generated = (bool)$project['blueprint']['generation']['password_generated'];
            echo "  password    " . ($generated ? 'generated and written to .env only (not printed)' : 'supplied on the command line and written to .env only') . "\n";
            echo "  QA          " . ($project['qa']['passed'] ? 'all checks passed' : 'needs review: ' . implode(', ', array_map(static fn ($c) => $c['label'], array_filter($project['qa']['checks'], static fn ($c) => !$c['ok'])))) . "\n";
            echo "\nSteps\n";
            foreach ($project['steps'] as $i => $step) {
                printf("  %2d. %-26s %-8s %s\n", $i + 1, $step['label'], strtoupper($step['status']), $step['log']);
            }
            echo "\nRun it\n";
            if ((int)$project['port'] > 0) {
                echo "  C:/xampp/php/php.exe -S 127.0.0.1:{$project['port']} -t public public/router.php   # from build/{$project['slug']}\n";
            } else {
                echo "  php -S 127.0.0.1:8000 -t public public/router.php   # from build/{$project['slug']}\n";
            }
            echo "  Installer: http://127.0.0.1:{$project['port']}/install.php?key=INSTALL_KEY\n";
            break;

        case 'list':
            $projects = Store::all();
            if (isset($options['json'])) {
                echo json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
                break;
            }
            if (!$projects) {
                echo "No projects yet. Run: php cli/forge.php new blueprint.json\n";
                break;
            }
            printf("%-22s %-20s %-10s %-22s %-6s %s\n", 'SLUG', 'BUSINESS', 'STATUS', 'DATABASE', 'PORT', 'FILES');
            foreach ($projects as $project) {
                printf(
                    "%-22s %-20s %-10s %-22s %-6s %d\n",
                    $project['slug'] ?? '',
                    mb_substr((string)($project['name'] ?? ''), 0, 20),
                    $project['status'] ?? '',
                    $project['database']['database'] ?? '',
                    (string)($project['port'] ?? ''),
                    (int)($project['manifest']['count'] ?? 0)
                );
            }
            $stats = Store::stats();
            echo "\n" . $stats['projects'] . ' projects · ' . $stats['files'] . ' files · ' . fs_format_bytes((int)$stats['bytes']) . ' · ' . $stats['databases'] . " installed databases\n";
            break;

        case 'preflight':
            $result = Preflight::run();
            echo "ForgeStack preflight — " . strtoupper($result['status']) . "\n";
            foreach ($result['checks'] as $check) {
                printf("  [%-4s] %-26s %s\n", strtoupper($check['status']), $check['label'], $check['detail']);
            }
            echo "\nTarget: " . $result['target']['php'] . ' · ' . strtoupper(implode(', ', $result['target']['extensions'])) . "\n";
            echo $result['can_generate'] ? "Generation allowed.\n" : "Generation blocked by a failing required check.\n";
            exit($result['can_generate'] ? 0 : 2);

        case 'help':
        default:
            echo "ForgeStack " . FS_VERSION . "\n\n";
            echo "  php cli/forge.php preflight\n";
            echo "  php cli/forge.php new blueprint.json [--slug=...] [--db=...] [--port=8101]\n";
            echo "      [--admin-email=...] [--admin-password=...] [--url=http://127.0.0.1:8101]\n";
            echo "      [--thumbnail=assets/site.png] [--no-install] [--force]\n";
            echo "  php cli/forge.php list [--json]\n";
            echo "\nThe CLI accepts an admin password for local demos and writes it only to build/<slug>/.env.\n";
            exit($command === 'help' ? 0 : 1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'ForgeStack error: ' . $e->getMessage() . "\n");
    exit(1);
}
