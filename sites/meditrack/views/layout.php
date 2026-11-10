<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = static fn (string $needle): string => str_contains($path, $needle) ? 'active' : '';
$user = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'MediTrack') ?></title>
  <meta name="description" content="MediTrack helps patients book clinic appointments, doctors manage schedules and administrators run the clinic from one secure workspace.">
  <meta name="theme-color" content="#0d3b33">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body>
  <header class="site-header">
    <div class="nav">
      <a class="brand" href="<?= e(url('/')) ?>" aria-label="MediTrack home">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M3 12h4l2-5 3 10 2-5h7"/></svg>
        </span>
        Medi<em>Track</em>
      </a>
      <button class="menu-btn" type="button" aria-expanded="false" aria-controls="navlinks" aria-label="Toggle navigation">☰</button>
      <nav class="nav-links" id="navlinks" aria-label="Main navigation">
        <a class="<?= $isActive('/doctors') ?>" href="<?= e(url('/doctors')) ?>">Doctors</a>
        <a class="<?= $isActive('/track') ?>" href="<?= e(url('/track')) ?>">Track appointment</a>
        <?php if ($user): ?>
          <a class="<?= $isActive('/patient') ?>" href="<?= e(url('/dashboard')) ?>">Dashboard</a>
        <?php endif; ?>
      </nav>
      <div class="nav-actions">
        <?php if ($user): ?>
          <a class="avatar" href="<?= e(url('/dashboard')) ?>" title="<?= e($user['name']) ?>"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></a>
          <form method="post" action="<?= e(url('/logout')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-ghost btn-sm" type="submit">Sign out</button>
          </form>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/login')) ?>">Sign in</a>
          <a class="btn btn-primary btn-sm" href="<?= e(url('/register')) ?>">Create account</a>
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
        <a class="brand" href="<?= e(url('/')) ?>"><span class="brand-mark" aria-hidden="true">+</span>Medi<em>Track</em></a>
        <p>Calm, coordinated clinic care. Book appointments, keep schedules in sync and give every patient a clear next step.</p>
      </div>
      <div>
        <h4>Patients</h4>
        <a href="<?= e(url('/doctors')) ?>">Find a doctor</a>
        <a href="<?= e(url('/track')) ?>">Track an appointment</a>
        <a href="<?= e(url('/register')) ?>">Create an account</a>
      </div>
      <div>
        <h4>Clinic</h4>
        <a href="<?= e(url('/login')) ?>">Staff sign in</a>
        <a href="mailto:hello@meditrack.test">hello@meditrack.test</a>
        <a href="tel:+2347000000000">+234 700 000 0000</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> MediTrack. Built for cPanel hosting.</span>
      <span>Lagos, Nigeria · Mon–Sat, 8:00–18:00</span>
    </div>
  </footer>
</body>
</html>
