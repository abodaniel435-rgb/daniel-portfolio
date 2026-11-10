<?php
$site = site_config();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = base_path();
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ?: '/';
}
$active = static fn (string $needle): string => rtrim($path, '/') === rtrim($needle, '/') ? 'active' : '';
$adminUser = Auth::user();
?>
<aside class="portal-side">
  <div class="portal-user">
    <span class="avatar" aria-hidden="true"><?= e(mb_substr((string)($adminUser['name'] ?? 'A'), 0, 1)) ?></span>
    <div>
      <strong><?= e($adminUser['name'] ?? 'Administrator') ?></strong>
      <span><?= e($site['labels']['admin']) ?></span>
    </div>
  </div>
  <nav class="portal-nav" aria-label="Admin navigation">
    <a class="<?= $active('/admin') ?>" href="<?= e(url('/admin')) ?>">Overview</a>
    <a class="<?= $active('/admin/records') ?>" href="<?= e(url('/admin/records')) ?>">All <?= e($site['labels']['txn_plural']) ?></a>
    <?php if (cfg('modules.orders', false)): ?>
      <a class="<?= $active('/admin/orders') ?>" href="<?= e(url('/admin/orders')) ?>">Orders</a>
      <a class="<?= $active('/admin/products') ?>" href="<?= e(url('/admin/products')) ?>">Catalogue &amp; stock</a>
    <?php endif; ?>
    <?php if (cfg('modules.messaging', false)): ?>
      <a class="<?= $active('/admin/messages') ?>" href="<?= e(url('/admin/messages')) ?>">Enquiries</a>
    <?php endif; ?>
    <?php if (cfg('modules.payments', false)): ?>
      <a class="<?= $active('/admin/payments') ?>" href="<?= e(url('/admin/payments')) ?>">Payments</a>
    <?php endif; ?>
    <a class="<?= $active('/admin/users') ?>" href="<?= e(url('/admin/users')) ?>">People</a>
    <a class="<?= $active('/admin/outbox') ?>" href="<?= e(url('/admin/outbox')) ?>">Email outbox</a>
    <?php if (cfg('modules.reports', false)): ?>
      <a class="<?= $active('/admin/reports') ?>" href="<?= e(url('/admin/reports')) ?>">Reports</a>
    <?php endif; ?>
    <span class="sep"></span>
    <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View public site ↗</a>
    <a href="<?= e(url('/track')) ?>">Public tracking ↗</a>
  </nav>
  <form class="nav-automation" method="post" action="<?= e(url('/admin/automation')) ?>">
    <?= csrf_field() ?>
    <button class="btn btn-quiet btn-block btn-sm" type="submit">Run automation now</button>
  </form>
</aside>
