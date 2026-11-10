<?php
// Portal screens pass an $area, so they automatically render in the dark shell.
$theme = $theme ?? (isset($area) ? 'dark' : 'light');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = base_path();
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ?: '/';
}
$isActive = static function (string $needle) use ($path): string {
    $needle = rtrim($needle, '/') ?: '/';
    if ($needle === '/') {
        return $path === '/' ? 'active' : '';
    }
    return str_starts_with($path, $needle) ? 'active' : '';
};
$user = Auth::user();
$dashboard = '/dashboard';
if ($user) {
    $dashboard = match ($user['role']) {
        'admin' => '/admin',
        'courier' => '/courier/queue',
        default => '/dispatch',
    };
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'RoutePilot') ?></title>
  <meta name="description" content="<?= e($description ?? 'RoutePilot is the dispatch desk for Lagos last-mile couriers: book shipments, assign riders, track every parcel publicly and keep an audit trail of every scan.') ?>">
  <meta name="theme-color" content="#0b1220">
  <meta property="og:title" content="<?= e($title ?? 'RoutePilot') ?>">
  <meta property="og:description" content="Dispatch, courier updates and public parcel tracking in one audit-ready desk.">
  <meta property="og:type" content="website">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body class="<?= $theme === 'dark' ? 'theme-dark' : '' ?>">
  <header class="site-header">
    <div class="nav">
      <a class="brand" href="<?= e(url('/')) ?>" aria-label="RoutePilot home">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.3 2.7a1 1 0 0 1 .22 1.06l-7.2 17.2a1 1 0 0 1-1.86.05l-2.4-6.1-6.1-2.4a1 1 0 0 1 .05-1.86l17.2-7.2a1 1 0 0 1 1.09.25ZM10.9 12.4l1.55 3.94 5.16-12.31L10.9 12.4Zm-.44-1.24 8.37-5.16-12.31 5.16 3.94 1.55Z"/></svg>
        </span>
        Route<em>Pilot</em>
      </a>
      <button class="menu-btn" type="button" aria-expanded="false" aria-controls="navlinks" aria-label="Toggle navigation">☰</button>
      <nav class="nav-links" id="navlinks" aria-label="Main navigation">
        <a class="<?= $isActive('/') ?>" href="<?= e(url('/')) ?>">Home</a>
        <a class="<?= $isActive('/track') ?>" href="<?= e(url('/track')) ?>">Track a parcel</a>
        <?php if ($user): ?>
          <?php if (Auth::is('dispatcher', 'admin')): ?>
            <a class="<?= $isActive('/dispatch') ?>" href="<?= e(url('/dispatch')) ?>">Dispatch board</a>
          <?php endif; ?>
          <?php if (Auth::is('courier')): ?>
            <a class="<?= $isActive('/courier') ?>" href="<?= e(url('/courier/queue')) ?>">My route</a>
          <?php endif; ?>
          <?php if (Auth::is('admin')): ?>
            <a class="<?= $isActive('/admin') ?>" href="<?= e(url('/admin')) ?>">Admin</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="<?= e(url('/#how-it-works')) ?>">How it works</a>
          <a href="<?= e(url('/#services')) ?>">Services</a>
        <?php endif; ?>
      </nav>
      <div class="nav-actions">
        <?php if ($user): ?>
          <a class="avatar" href="<?= e(url($dashboard)) ?>" title="<?= e($user['name'] . ' · ' . $user['role']) ?>"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></a>
          <form method="post" action="<?= e(url('/logout')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-ghost btn-sm" type="submit">Sign out</button>
          </form>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/login')) ?>">Sign in</a>
          <a class="btn btn-primary btn-sm" href="<?= e(url('/register')) ?>">Ride with us</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <?php if (!empty($flashes)): ?>
    <div class="flashes" role="status" aria-live="polite">
      <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <main><?= $content ?></main>

  <footer class="site-footer">
    <div class="footer-inner">
      <div>
        <a class="brand" href="<?= e(url('/')) ?>">
          <span class="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.3 2.7a1 1 0 0 1 .22 1.06l-7.2 17.2a1 1 0 0 1-1.86.05l-2.4-6.1-6.1-2.4a1 1 0 0 1 .05-1.86l17.2-7.2a1 1 0 0 1 1.09.25Z"/></svg>
          </span>
          Route<em>Pilot</em>
        </a>
        <p>One dispatch desk for Lagos last-mile couriers. Every assignment, scan and delivery is recorded — and every customer gets a tracking link that actually works.</p>
      </div>
      <div>
        <h4>Customers</h4>
        <a href="<?= e(url('/track')) ?>">Track a parcel</a>
        <a href="<?= e(url('/#services')) ?>">Delivery services</a>
        <a href="<?= e(url('/#how-it-works')) ?>">How it works</a>
      </div>
      <div>
        <h4>Operations</h4>
        <a href="<?= e(url('/login')) ?>">Dispatch sign in</a>
        <a href="<?= e(url('/register')) ?>">Courier sign-up</a>
        <a href="mailto:dispatch@routepilot.test">dispatch@routepilot.test</a>
      </div>
      <div>
        <h4>Coverage</h4>
        <a href="<?= e(url('/track')) ?>">Lagos Island</a>
        <a href="<?= e(url('/track')) ?>">Lagos Mainland</a>
        <a href="tel:+2348021110042">+234 802 111 0042</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> RoutePilot Logistics. Built for cPanel hosting.</span>
      <span>Lagos, Nigeria · Mon–Sat, 7:00–21:00</span>
    </div>
  </footer>
</body>
</html>
