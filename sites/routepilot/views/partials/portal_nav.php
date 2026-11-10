<?php
$area = $area ?? 'dispatch';
$user = Auth::user();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = base_path();
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ?: '/';
}
$isActive = static function (string $href) use ($path): string {
    if ($path === $href) {
        return 'active';
    }
    if ($href === '/dispatch/shipments' && preg_match('#^/dispatch/shipments/\d+$#', $path)) {
        return 'active';
    }
    if ($href === '/admin/users' && preg_match('#^/admin/users#', $path)) {
        return 'active';
    }
    return '';
};
$links = [
    'dispatch' => [
        ['Dispatch board', '/dispatch'],
        ['Shipments', '/dispatch/shipments'],
        ['New shipment', '/dispatch/shipments/new'],
        ['Couriers', '/dispatch/couriers'],
    ],
    'courier' => [
        ['My route', '/courier/queue'],
    ],
    'admin' => [
        ['Overview', '/admin'],
        ['Users', '/admin/users'],
        ['Shipments', '/admin/shipments'],
        ['Email outbox', '/admin/outbox'],
    ],
];
?>
<aside class="portal-side">
  <div class="portal-user">
    <span class="avatar"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
    <div>
      <strong><?= e($user['name']) ?></strong>
      <span class="role"><?= e($user['role']) ?> account</span>
    </div>
  </div>
  <nav class="portal-nav" aria-label="<?= e(ucfirst($area)) ?> navigation">
    <?php foreach ($links[$area] as [$label, $href]): ?>
      <a class="<?= $isActive($href) ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php if ($area !== 'admin' && Auth::is('admin')): ?>
      <div class="sep"></div>
      <a href="<?= e(url('/admin')) ?>">Admin console</a>
    <?php endif; ?>
    <?php if ($area === 'admin'): ?>
      <div class="sep"></div>
      <a href="<?= e(url('/dispatch')) ?>">Dispatch board</a>
    <?php endif; ?>
    <div class="sep"></div>
    <a href="<?= e(url('/track')) ?>">Public tracking</a>
    <a href="<?= e(url('/')) ?>">Public site</a>
    <form method="post" action="<?= e(url('/logout')) ?>" style="margin-top:6px">
      <?= csrf_field() ?>
      <button class="btn btn-ghost btn-sm btn-block" type="submit">Sign out</button>
    </form>
    <div class="nav-note">Every status change is written to the audit log with your name, IP and timestamp.</div>
  </nav>
</aside>
