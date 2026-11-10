<?php
$area = $area ?? 'patient';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$active = static fn (string $needle): string => str_contains($path, $needle) ? 'active' : '';
$user = Auth::user();
$links = [
    'patient' => [
        ['Appointments', '/patient/appointments'],
        ['Book a visit', '/patient/book'],
        ['Consultation notes', '/patient/notes'],
    ],
    'doctor' => [
        ['My schedule', '/doctor/schedule'],
    ],
    'admin' => [
        ['Overview', '/admin'],
        ['Appointments', '/admin/appointments'],
        ['Doctors', '/admin/doctors'],
        ['Users', '/admin/users'],
        ['Email outbox', '/admin/outbox'],
    ],
];
?>
<aside class="portal-side">
  <div class="portal-user">
    <span class="avatar"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
    <div>
      <strong><?= e($user['name']) ?></strong>
      <span><?= e($user['role']) ?> account</span>
    </div>
  </div>
  <nav class="portal-nav" aria-label="<?= e(ucfirst($area)) ?> navigation">
    <?php foreach ($links[$area] as [$label, $href]): ?>
      <a class="<?= $active($href === '/admin' ? '/admin' : $href) ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <div class="sep"></div>
    <a href="<?= e(url('/')) ?>">Public site</a>
    <form method="post" action="<?= e(url('/logout')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-ghost btn-sm btn-block" type="submit">Sign out</button>
    </form>
  </nav>
</aside>
