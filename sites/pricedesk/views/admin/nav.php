<?php
declare(strict_types=1);
/** Shared console navigation, so seven admin screens cannot drift apart. */
$path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH);
$path = rtrim(str_replace(base_path(), '', $path), '/') ?: '/admin';
$links = [
    '/admin' => 'Overview',
    '/admin/queue' => 'Verification queue',
    '/admin/reference' => 'Markets and commodities',
    '/admin/rates' => 'Haulage rates',
    '/admin/users' => 'Accounts',
    '/admin/audit' => 'Audit trail',
    '/admin/outbox' => 'Email outbox',
];
?>
<nav class="subnav" aria-label="Console">
  <?php foreach ($links as $href => $label): ?>
    <a href="<?= e(url($href)) ?>" <?= $path === $href ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
