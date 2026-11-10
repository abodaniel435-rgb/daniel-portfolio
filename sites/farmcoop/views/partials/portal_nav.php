<?php
$area = $area ?? 'farmer';
$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
if ($path === '') {
    $path = '/';
}
$user = Auth::user();

$links = [
    'farmer' => [
        ['Overview', '/farmer'],
        ['Harvest listings', '/farmer/listings'],
        ['New listing', '/farmer/listings/new'],
        ['Incoming orders', '/farmer/orders'],
        ['Earnings', '/farmer/earnings'],
        ['Farm profile', '/farmer/profile'],
    ],
    'buyer' => [
        ['Overview', '/buyer'],
        ['My orders', '/buyer/orders'],
        ['Marketplace', '/marketplace'],
    ],
    'admin' => [
        ['Overview', '/admin'],
        ['Users & farms', '/admin/users'],
        ['Listings', '/admin/listings'],
        ['Orders', '/admin/orders'],
        ['Email outbox', '/admin/outbox'],
    ],
];
$items = $links[$area] ?? $links['farmer'];

$activeHref = '';
foreach ($items as [, $href]) {
    if ($path === $href || str_starts_with($path, rtrim($href, '/') . '/')) {
        if (strlen($href) > strlen($activeHref)) {
            $activeHref = $href;
        }
    }
}

$roleLabel = match ($area) {
    'farmer' => 'Farmer workspace',
    'buyer' => 'Buyer workspace',
    default => 'Operations',
};
?>
<aside class="portal-side">
  <div class="portal-user">
    <span class="avatar avatar-lg"><?= e(strtoupper(mb_substr($user['name'] ?? 'F', 0, 1))) ?></span>
    <div>
      <strong><?= e($user['name'] ?? '') ?></strong>
      <span><?= e($roleLabel) ?></span>
    </div>
  </div>
  <nav class="portal-nav" aria-label="<?= e(ucfirst($area)) ?> navigation">
    <?php foreach ($items as [$label, $href]): ?>
      <a class="<?= $href === $activeHref ? 'active' : '' ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <div class="sep"></div>
    <a href="<?= e(url('/')) ?>">← Public market</a>
    <form method="post" action="<?= e(url('/logout')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-ghost btn-sm btn-block" type="submit">Sign out</button>
    </form>
  </nav>
</aside>
