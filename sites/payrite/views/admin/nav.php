<?php
declare(strict_types=1);
/** Shared console navigation, so ten admin screens cannot drift apart. */
$path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH);
$path = rtrim(str_replace(base_path(), '', $path), '/') ?: '/admin';
$active = static function (string $href) use ($path): string {
    $on = $href === '/admin'
        ? $path === '/admin'
        : str_starts_with($path, rtrim($href, '/'));
    return $on ? ' class="chip is-active" aria-current="page"' : ' class="chip"';
};
$links = [
    '/admin' => 'Overview',
    '/admin/employees' => 'Roster',
    '/admin/runs' => 'Payroll runs',
    '/admin/rules' => 'Bands & reliefs',
    '/admin/staff' => 'Staff logins',
    '/admin/audit' => 'Audit trail',
    '/admin/outbox' => 'Messages',
];
?>
<nav class="tabs" aria-label="Payroll desk">
  <?php foreach ($links as $href => $label): ?>
    <a href="<?= e(url($href)) ?>"<?= $active($href) ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
