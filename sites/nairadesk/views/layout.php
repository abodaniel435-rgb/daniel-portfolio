<?php
declare(strict_types=1);
/** @var string $content */
$user = Auth::user();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'NairaDesk') ?></title>
  <meta name="description" content="Nigerian payroll worked out on the server: progressive PAYE, CRA, rent relief, pension and NHF, with every run stored and auditable.">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%2314261d'/><path d='M8 22V10l8 7 8-7v12' fill='none' stroke='%237fd6a9' stroke-width='2.6' stroke-linejoin='round' stroke-linecap='round'/></svg>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/app.css') ?>">
</head>
<body class="anim <?= e($bodyClass ?? '') ?>">
  <div class="bg-plate" aria-hidden="true"></div>

  <header class="masthead">
    <a class="brand" href="<?= e(url('/')) ?>">
      <span class="brand-mark" aria-hidden="true">N</span>
      <span class="brand-text">NairaDesk<small>payroll, worked out</small></span>
    </a>
    <nav class="mastnav" aria-label="Main">
      <?php if (!$user): ?>
        <a href="<?= e(url('/#how')) ?>">How it works</a>
        <a href="<?= e(url('/#rates')) ?>">Statutory rates</a>
        <a href="<?= e(url('/login')) ?>">Sign in</a>
        <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Open a workspace</a>
      <?php else: ?>
        <?php if ($user['role'] === 'admin'): ?><a href="<?= e(url('/admin')) ?>">Console</a><?php endif; ?>
        <a href="<?= e(url('/dashboard')) ?>">Dashboard</a>
        <a href="<?= e(url('/staff')) ?>">Staff</a>
        <a href="<?= e(url('/calculate')) ?>">Calculate</a>
        <a href="<?= e(url('/runs')) ?>">Pay runs</a>
        <form method="post" action="<?= e(url('/logout')) ?>" class="inlineform">
          <?= csrf_field() ?>
          <button class="linklike" type="submit">Sign out · <?= e($user['name']) ?></button>
        </form>
      <?php endif; ?>
    </nav>
  </header>

  <?php foreach (($flashes ?? []) as $flash): ?>
    <div class="flash flash-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
  <?php endforeach; ?>

  <main class="page"><?= $content ?></main>

  <footer class="foot">
    <p>NairaDesk — a PHP 8 / MySQL full-stack build. Passwords are hashed with bcrypt, every POST is CSRF-checked, and the PAYE bands live in the database so they can be updated without a redeploy.</p>
    <p>Reference figures are national indications. Confirm your state's current schedule before you file.</p>
  </footer>

  <script>
  (function () {
    var plate = document.querySelector('.bg-plate');
    if (!plate || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var queued = false;
    function paint() {
      plate.style.setProperty('--sy', (window.scrollY * 0.055).toFixed(1) + 'px');
      queued = false;
    }
    window.addEventListener('scroll', function () {
      if (!queued) { queued = true; requestAnimationFrame(paint); }
    }, { passive: true });
    paint();
  })();
  </script>
</body>
</html>
