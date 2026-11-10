<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Auto-detects a subfolder deployment (e.g. /portal) without hardcoding it. */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (str_contains($script, '/public/')) {
        $base = rtrim(substr($script, 0, strpos($script, '/public/')), '/');
    } else {
        $base = rtrim(dirname($script), '/');
        if ($base === '/' || $base === '.') {
            $base = '';
        }
    }
    return $base;
}

function url(string $path = ''): string
{
    $configured = $GLOBALS['app_config']['base_url'] ?? '';
    $prefix = $configured !== '' ? rtrim($configured, '/') : base_path();
    $path = '/' . ltrim($path, '/');
    return $prefix . ($path === '/' ? '/' : $path);
}

function asset(string $path): string
{
    $abs = BASE_PATH . '/public/' . ltrim($path, '/');
    $version = is_file($abs) ? (string)filemtime($abs) : CORE_VERSION;
    return url($path) . '?v=' . $version;
}

function redirect(string $path, int $status = 302): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)), true, $status);
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '' && str_starts_with($ref, ($_SERVER['HTTP_ORIGIN'] ?? 'http') . '://')) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function view(string $template, array $data = [], ?string $layout = 'layout'): void
{
    $data['flashes'] = pull_flashes();
    extract($data, EXTR_SKIP);
    $viewFile = BASE_PATH . '/views/' . $template . '.php';
    if (!is_file($viewFile)) {
        $viewFile = BASE_PATH . '/core/views/' . $template . '.php';
    }
    if (!is_file($viewFile)) {
        throw new RuntimeException("View not found: {$template}");
    }
    if ($layout === null) {
        include $viewFile;
        return;
    }
    ob_start();
    include $viewFile;
    $content = ob_get_clean();
    include BASE_PATH . '/views/' . $layout . '.php';
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string)($_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if ($token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        view('errors/419', ['title' => 'Page expired']);
        exit;
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $items = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $items;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function remember_old(array $input): void
{
    unset($input['_token'], $input['password'], $input['password_confirm']);
    $_SESSION['_old'] = $input;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function errors(): array
{
    return $_SESSION['_errors'] ?? [];
}

function remember_errors(array $errors): void
{
    $_SESSION['_errors'] = $errors;
}

function clear_errors(): void
{
    unset($_SESSION['_errors']);
}

function field_error(string $field): string
{
    $errors = errors();
    return isset($errors[$field]) ? '<span class="field-error">' . e($errors[$field]) . '</span>' : '';
}

function client_ip(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function money(float|int|string $amount, string $symbol = '₦'): string
{
    return $symbol . number_format((float)$amount, 2);
}

function short_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    return date('M j, Y', strtotime($date));
}

function short_datetime(?string $date): string
{
    if (!$date) {
        return '—';
    }
    return date('M j, Y · g:i A', strtotime($date));
}

function status_badge(string $status): string
{
    $map = [
        'confirmed' => 'ok', 'completed' => 'ok', 'paid' => 'ok', 'active' => 'ok', 'delivered' => 'ok', 'approved' => 'ok',
        'pending' => 'warn', 'scheduled' => 'warn', 'in_transit' => 'info', 'out_for_delivery' => 'info', 'processing' => 'info',
        'cancelled' => 'bad', 'no_show' => 'bad', 'failed' => 'bad', 'rejected' => 'bad', 'overdue' => 'bad',
    ];
    $tone = $map[$status] ?? 'neutral';
    return '<span class="badge badge-' . e($tone) . '">' . e(str_replace('_', ' ', ucfirst($status))) . '</span>';
}

function paginate(int $total, int $perPage, int $current): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $current = max(1, min($current, $pages));
    return ['total' => $total, 'pages' => $pages, 'current' => $current, 'offset' => ($current - 1) * $perPage, 'limit' => $perPage];
}

function reference(string $prefix = 'REF'): string
{
    return strtoupper($prefix) . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        http_response_code(405);
        exit('Method not allowed');
    }
}

function audit(string $action, string $detail = ''): void
{
    try {
        Db::insert('audit_log', [
            'user_id' => Auth::id(),
            'action' => $action,
            'detail' => mb_substr($detail, 0, 500),
            'ip' => client_ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable) {
        // Audit failures must not break the user's request.
    }
}
