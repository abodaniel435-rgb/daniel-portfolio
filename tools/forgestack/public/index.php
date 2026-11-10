<?php
/**
 * ForgeStack web UI — a local console for the generator.
 * No framework, no build step; every action calls the same classes as the CLI.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

function fs_view(string $view, array $data = []): void
{
    $data['preflightMini'] = fs_preflight_cached();
    extract($data, EXTR_SKIP);
    ob_start();
    require FS_ROOT . '/views/' . $view . '.php';
    $content = ob_get_clean();
    require FS_ROOT . '/views/layout.php';
}

function fs_not_found(string $message = 'That page does not exist.'): void
{
    http_response_code(404);
    fs_view('error', ['title' => 'Not found', 'message' => $message, 'active' => '']);
}

/** Cache preflight for the dashboard so a missing MySQL does not slow every page. */
function fs_preflight_cached(int $ttl = 60): array
{
    $file = FS_ROOT . '/data/tmp/preflight.json';
    if (is_file($file) && (time() - (int)filemtime($file)) < $ttl) {
        $cached = json_decode((string)file_get_contents($file), true);
        if (is_array($cached) && isset($cached['checks'])) {
            $cached['cached'] = true;
            return $cached;
        }
    }
    $result = Preflight::run();
    $result['cached'] = false;
    @file_put_contents($file, json_encode($result, JSON_UNESCAPED_SLASHES));
    return $result;
}

function fs_project_from_request(): ?array
{
    $id = trim((string)($_GET['id'] ?? ''));
    if ($id !== '') {
        return Store::find($id);
    }
    $slug = trim((string)($_GET['slug'] ?? ''));
    return $slug !== '' ? Store::findBySlug($slug) : null;
}

function fs_same_origin(): void
{
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($origin !== '') {
        $originHost = (string)parse_url($origin, PHP_URL_HOST);
        $originPort = parse_url($origin, PHP_URL_PORT);
        $hostNoPort = explode(':', $host)[0];
        if ($originHost !== $hostNoPort) {
            http_response_code(403);
            fs_json(['ok' => false, 'error' => 'Cross-origin requests are not accepted.']);
        }
        if ($originPort !== null && !str_contains($host, ':' . $originPort)) {
            http_response_code(403);
            fs_json(['ok' => false, 'error' => 'Cross-origin requests are not accepted.']);
        }
    }
    $fetchSite = (string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
    if ($fetchSite === 'cross-site') {
        http_response_code(403);
        fs_json(['ok' => false, 'error' => 'Cross-site requests are not accepted.']);
    }
}

function fs_bool(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    return in_array(strtolower((string)$value), ['1', 'true', 'on', 'yes'], true);
}

function fs_preview_payload(array $input): array
{
    $blueprint = Blueprint::build($input, [
        'slug' => 'preview',
        'db_name' => 'preview',
        'admin_email' => 'preview@example.com',
        'admin_password' => 'preview-password-123',
        'install_db' => false,
    ]);
    $architecture = $blueprint['architecture'];
    $palette = fs_palettes()[$blueprint['palette']] ?? fs_palettes()['slate'];
    $labels = Blueprint::featureLabels();
    $features = [];
    foreach ($blueprint['intake']['features'] as $feature) {
        $features[] = ['key' => $feature, 'label' => $labels[$feature][0] ?? $feature];
    }
    $pages = [];
    foreach ($architecture['pages'] as $page) {
        if (str_starts_with($page['path'], '/admin') || in_array($page['path'], ['/login', '/register', '/portal'], true)) {
            continue;
        }
        $pages[] = $page;
    }
    return [
        'profile' => $blueprint['profile'],
        'profile_label' => $blueprint['profile_label'],
        'tagline' => $architecture['tagline'],
        'palette' => [
            'name' => $palette['name'],
            'description' => $palette['description'],
            'brand' => $palette['brand'],
            'deep' => $palette['brand-deep'],
            'accent' => $palette['accent'],
            'paper' => $palette['paper'],
            'soft' => $palette['soft'],
        ],
        'typography' => $architecture['typography'],
        'pages' => $pages,
        'deferred' => $architecture['deferred_pages'],
        'features' => $features,
        'entities' => $architecture['entities'],
        'roles' => $architecture['roles'],
        'automation' => $architecture['automation'],
        'palette_reason' => $architecture['palette_reason'],
        'page_count' => count($architecture['pages']),
        'route_count' => count($architecture['routes']),
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

try {
    if ($path === '/api/preview' && $method === 'POST') {
        fs_same_origin();
        fs_json(['ok' => true, 'preview' => fs_preview_payload(fs_body_json())]);
    }

    if ($path === '/api/preflight' && $method === 'POST') {
        fs_same_origin();
        $result = Preflight::run();
        @file_put_contents(FS_ROOT . '/data/tmp/preflight.json', json_encode($result, JSON_UNESCAPED_SLASHES));
        fs_json(['ok' => true, 'preflight' => $result]);
    }

    if ($path === '/api/generate' && $method === 'POST') {
        fs_same_origin();
        $input = fs_body_json();
        $errors = Blueprint::validate($input);
        if ($errors) {
            fs_json(['ok' => false, 'error' => 'The brief needs a little more detail.', 'errors' => $errors], 422);
        }
        $preflight = Preflight::run();
        if (!$preflight['can_generate']) {
            $failed = array_values(array_map(
                static fn ($check) => $check['label'] . ': ' . $check['detail'],
                array_filter($preflight['checks'], static fn ($check) => $check['status'] === 'fail')
            ));
            fs_json(['ok' => false, 'error' => 'Preflight is failing, so generation is blocked.', 'errors' => ['preflight' => implode(' ', $failed)], 'preflight' => $preflight], 422);
        }
        $slug = Store::nextSlug(fs_slug((string)$input['business_name'], 'site'));
        $port = (int)($input['preview_port'] ?? 0);
        $mysql = fs_bool($input['mysql_available'] ?? false);
        $installNow = $mysql && fs_bool($input['install_now'] ?? false);
        $adminEmail = trim((string)($input['admin_email'] ?? ''));
        if ($adminEmail === '') {
            $adminEmail = (string)($input['contact_email'] ?? '');
        }
        $blueprint = Blueprint::build($input, [
            'slug' => $slug,
            'db_name' => 'forge_' . str_replace('-', '_', $slug),
            'port' => $port,
            'admin_email' => $adminEmail,
            'admin_password' => (string)($input['admin_password'] ?? ''),
            'install_db' => $installNow,
            'app_url' => $port > 0 ? 'http://127.0.0.1:' . $port : '',
        ]);
        $result = SiteGenerator::generate($blueprint, [
            'live_link' => $port > 0 ? 'http://127.0.0.1:' . $port : '',
            'thumbnail' => 'assets/' . $slug . '.png',
        ]);
        if (!$result['ok']) {
            fs_json(['ok' => false, 'error' => $result['error']], 500);
        }
        fs_json([
            'ok' => true,
            'redirect' => '/project?id=' . urlencode((string)$result['project']['id']) . '&created=1',
            'project' => [
                'id' => $result['project']['id'],
                'slug' => $result['project']['slug'],
                'status' => $result['project']['status'],
                'manifest' => $result['project']['manifest']['count'],
                'steps' => $result['project']['steps'],
            ],
        ]);
    }

    if ($path === '/' && $method === 'GET') {
        $projects = Store::all();
        $stats = Store::stats();
        $portfolio = json_decode((string)@file_get_contents(FS_ROOT . '/data/portfolio.json'), true);
        $qaPassed = 0;
        foreach ($projects as $project) {
            if (!empty($project['qa']['passed'])) {
                $qaPassed++;
            }
        }
        fs_view('dashboard', [
            'title' => 'ForgeStack — local site factory',
            'active' => 'dashboard',
            'projects' => $projects,
            'stats' => $stats,
            'qaPassed' => $qaPassed,
            'portfolioCount' => is_array($portfolio) ? count($portfolio) : 0,
        ]);
        exit;
    }

    if ($path === '/new' && $method === 'GET') {
        fs_view('wizard', [
            'title' => 'New site brief — ForgeStack',
            'active' => 'new',
        ]);
        exit;
    }

    if ($path === '/preflight' && $method === 'GET') {
        $result = Preflight::run();
        @file_put_contents(FS_ROOT . '/data/tmp/preflight.json', json_encode($result, JSON_UNESCAPED_SLASHES));
        fs_view('preflight', [
            'title' => 'cPanel preflight — ForgeStack',
            'active' => 'preflight',
            'preflight' => $result,
        ]);
        exit;
    }

    if ($path === '/project' && $method === 'GET') {
        $project = fs_project_from_request();
        if (!$project) {
            fs_not_found('No project matches that id.');
            exit;
        }
        fs_view('project', [
            'title' => (string)$project['name'] . ' — ForgeStack',
            'active' => 'dashboard',
            'project' => $project,
            'created' => isset($_GET['created']),
        ]);
        exit;
    }

    if ($path === '/report' && $method === 'GET') {
        $project = fs_project_from_request();
        if (!$project) {
            fs_not_found('No project matches that id.');
            exit;
        }
        $file = FS_ROOT . '/' . $project['build_path'] . '/docs/technical-report.html';
        if (!is_file($file)) {
            fs_not_found('The technical report has not been generated for this project.');
            exit;
        }
        header('Content-Type: text/html; charset=UTF-8');
        readfile($file);
        exit;
    }

    if ($path === '/project/file' && $method === 'GET') {
        $project = fs_project_from_request();
        if (!$project) {
            fs_not_found('No project matches that id.');
            exit;
        }
        $allowed = [
            'README.md', '.env.example', 'manifest.json', 'portfolio-entry.json', 'checksums.sha256',
            'docs/DEPLOYMENT.md', 'docs/TECH_STACK.md', 'docs/technical-report.html',
            'database/schema.sql', 'database/seed.sql', 'app/config.php',
        ];
        $file = str_replace('\\', '/', (string)($_GET['file'] ?? ''));
        if (!in_array($file, $allowed, true)) {
            fs_not_found('That file is not available from the console.');
            exit;
        }
        $base = realpath(FS_ROOT . '/' . $project['build_path']);
        $full = $base ? realpath($base . '/' . $file) : false;
        if (!$base || !$full || !str_starts_with(str_replace('\\', '/', $full), str_replace('\\', '/', $base) . '/') || !is_file($full)) {
            fs_not_found('That file is not available from the console.');
            exit;
        }
        $extension = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        if ($extension === 'html') {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($full);
            exit;
        }
        header('Content-Type: text/plain; charset=UTF-8');
        echo (string)file_get_contents($full);
        exit;
    }

    fs_not_found();
} catch (Throwable $e) {
    if (str_starts_with($path, '/api/')) {
        fs_json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
    http_response_code(500);
    fs_view('error', [
        'title' => 'Something went wrong',
        'message' => $e->getMessage(),
        'active' => '',
    ]);
}
