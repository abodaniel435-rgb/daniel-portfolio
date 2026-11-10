<?php
declare(strict_types=1);

function fs_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fs_h(mixed $value): string
{
    return fs_e($value);
}

function fs_slug(string $value, string $fallback = 'project'): string
{
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    $slug = trim(preg_replace('/-+/', '-', $slug), '-');
    return $slug !== '' ? substr($slug, 0, 48) : $fallback;
}

function fs_db_name(string $value): string
{
    $name = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $value));
    $name = trim(preg_replace('/_+/', '_', $name), '_');
    if ($name === '' || ctype_digit($name[0])) {
        $name = 'forge_' . $name;
    }
    return substr($name, 0, 48);
}

function fs_cli(): bool
{
    return PHP_SAPI === 'cli';
}

function fs_json(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function fs_redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function fs_body_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/** Curated palettes. The tool never generates random colours. */
function fs_palettes(): array
{
    return [
        'eucalyptus' => [
            'name' => 'Eucalyptus & bone',
            'description' => 'Clinical green with warm bone paper and a brass highlight.',
            'ink' => '#13211d',
            'brand' => '#146b5a',
            'brand-deep' => '#0b3b32',
            'accent' => '#c8964f',
            'paper' => '#f5f3ec',
            'surface' => '#fffdf9',
            'soft' => '#e6efe9',
            'line' => '#dce4dd',
            'muted' => '#60716a',
            'on-brand' => '#f7f3e8',
        ],
        'indigo' => [
            'name' => 'Midnight indigo & signal amber',
            'description' => 'Operations-grade indigo with an amber signal colour.',
            'ink' => '#0d1526',
            'brand' => '#3b4fe0',
            'brand-deep' => '#101a3d',
            'accent' => '#f0a93c',
            'paper' => '#f3f5fa',
            'surface' => '#ffffff',
            'soft' => '#e7eaf6',
            'line' => '#dfe3ee',
            'muted' => '#5d6a83',
            'on-brand' => '#ffffff',
        ],
        'terracotta' => [
            'name' => 'Terracotta & cream',
            'description' => 'Hearth-warm terracotta, cream paper and antique gold.',
            'ink' => '#241a14',
            'brand' => '#9c3f24',
            'brand-deep' => '#5f2416',
            'accent' => '#c8a45c',
            'paper' => '#f8f1e4',
            'surface' => '#fffaf2',
            'soft' => '#f1e4d1',
            'line' => '#e6d8c4',
            'muted' => '#7a6859',
            'on-brand' => '#fff8ee',
        ],
        'slate' => [
            'name' => 'Slate & copper',
            'description' => 'Neutral slate with a restrained copper accent.',
            'ink' => '#171c22',
            'brand' => '#3c5568',
            'brand-deep' => '#22313d',
            'accent' => '#b9713e',
            'paper' => '#f4f4f2',
            'surface' => '#ffffff',
            'soft' => '#e8ebec',
            'line' => '#dde1e2',
            'muted' => '#5f6a70',
            'on-brand' => '#f8f7f3',
        ],
    ];
}

/** Map a free-text colour direction onto one curated palette. */
function fs_palette_for(?string $direction): string
{
    $d = strtolower(trim((string)$direction));
    if ($d === '') {
        return 'eucalyptus';
    }
    if (preg_match('/teal|green|sage|eucalypt|emerald|mint|forest|natural|organic/', $d)) {
        return 'eucalyptus';
    }
    if (preg_match('/blue|indigo|navy|violet|purple|cobalt|electric/', $d)) {
        return 'indigo';
    }
    if (preg_match('/warm|red|orange|brown|terracotta|rust|gold|amber|cream|beige|earth/', $d)) {
        return 'terracotta';
    }
    return 'slate';
}

function fs_format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

function fs_relative_time(?string $iso): string
{
    if (!$iso) {
        return '—';
    }
    $ts = strtotime($iso);
    if (!$ts) {
        return '—';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        return (int)floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return (int)floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return (int)floor($diff / 86400) . 'd ago';
    }
    return date('M j, Y', $ts);
}

function fs_status_tone(string $status): string
{
    return match ($status) {
        'done', 'pass', 'ready' => 'ok',
        'running' => 'info',
        'warn', 'skipped' => 'warn',
        'fail', 'failed' => 'bad',
        default => 'neutral',
    };
}

/** Read a value from the generated site config by dot path. */
function fs_dot(array $data, string $path, mixed $default = null): mixed
{
    $cursor = $data;
    foreach (explode('.', $path) as $key) {
        if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
            return $default;
        }
        $cursor = $cursor[$key];
    }
    return $cursor;
}

/** Inline stroke icons (no icon font, no external requests). */
function fs_icon(string $name, int $size = 18): string
{
    $icons = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
        'file' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5z"/><path d="M14 3v5h5"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'check' => '<path d="M4 12l5 5L20 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'server' => '<rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 7h.01M7 17h.01"/>',
        'database' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
        'external' => '<path d="M14 4h6v6"/><path d="M20 4l-9 9"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/>',
        'alert' => '<path d="M12 4l9 16H3L12 4z"/><path d="M12 10v4M12 17h.01"/>',
        'play' => '<path d="M8 5l11 7-11 7V5z"/>',
        'layers' => '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/>',
        'code' => '<path d="M8 7l-5 5 5 5M16 7l5 5-5 5"/>',
        'spark' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5L18 18M18 6l-2.5 2.5M8.5 15.5L6 18"/>',
        'logout' => '<path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/><path d="M15 8l4 4-4 4M19 12H9"/>',
    ];
    $body = $icons[$name] ?? $icons['spark'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}
