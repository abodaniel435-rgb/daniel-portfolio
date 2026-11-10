<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/');
$area = $area ?? (str_starts_with($path, '/admin') ? 'admin' : 'member');
$active = static function (string $href) use ($path): string {
    $href = rtrim($href, '/');
    return $path === $href ? 'active' : '';
};
$user = Auth::user();
$links = [
    'member' => [
        ['Dashboard', '/dashboard'],
        ['My circles', '/member/circles'],
        ['Contributions', '/member/contributions'],
        ['Statement', '/member/statement'],
    ],
    'admin' => [
        ['Overview', '/admin'],
        ['Circles', '/admin/circles'],
        ['Contributions', '/admin/contributions'],
        ['Payouts', '/admin/payouts'],
        ['Members', '/admin/users'],
        ['Email outbox', '/admin/outbox'],
    ],
];
?>
<aside class="portal-side">
  <div class="portal-user">
    <span class="avatar"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
    <div>
      <strong><?= e($user['name']) ?></strong>
      <span><?= $user['role'] === 'admin' ? 'Circle organiser' : 'Member' ?></span>
    </div>
  </div>
  <nav class="portal-nav" aria-label="<?= e(ucfirst($area)) ?> navigation">
    <?php foreach ($links[$area] as [$label, $href]): ?>
      <a class="<?= $active($href) ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <div class="sep"></div>
    <?php if ($area === 'member' && $user['role'] === 'admin'): ?>
      <a href="<?= e(url('/admin')) ?>">Organiser console</a>
    <?php endif; ?>
    <a href="<?= e(url('/circles')) ?>">Public directory</a>
    <form method="post" action="<?= e(url('/logout')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-ghost btn-sm btn-block" type="submit">Sign out</button>
    </form>
  </nav>
</aside>
