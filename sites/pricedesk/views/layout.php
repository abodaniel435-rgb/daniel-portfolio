<?php
declare(strict_types=1);
/** @var string $content */
$user = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'PriceDesk') ?></title>
  <meta name="description" content="A cooperative price board for Nigerian markets: every quote normalised to naira per kilogram, medianed, verified by a human, and netted against the cost of the trip.">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23241509'/><path d='M6 20l5-9 4 6 3-4 6 7' fill='none' stroke='%23e8a33d' stroke-width='2.6' stroke-linejoin='round' stroke-linecap='round'/></svg>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/app.css') ?>">
</head>
<body class="anim <?= e($bodyClass ?? '') ?>">
  <div class="bg-plate" aria-hidden="true"></div>

  <header class="masthead">
    <a class="brand" href="<?= e(url('/')) ?>">
      <span class="brand-mark" aria-hidden="true">P</span>
      <span class="brand-text">PriceDesk<small>the board a market agrees on</small></span>
    </a>
    <nav class="mastnav" aria-label="Main">
      <a href="<?= e(url('/board')) ?>">Price board</a>
      <a href="<?= e(url('/compare')) ?>">Is it worth the trip?</a>
      <?php if (!$user): ?>
        <a href="<?= e(url('/#rules')) ?>">How it works</a>
        <a href="<?= e(url('/login')) ?>">Sign in</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/register')) ?>">Join the board</a>
      <?php else: ?>
        <a href="<?= e(url('/report')) ?>">File a price</a>
        <a href="<?= e(url('/history')) ?>">Your reports</a>
        <a href="<?= e(url('/watch')) ?>">Watchlist</a>
        <?php if ($user['role'] === 'admin'): ?><a href="<?= e(url('/admin')) ?>">Console</a><?php endif; ?>
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
    <p>PriceDesk — a PHP 8.2 / MySQL full-stack build. Passwords are bcrypt-hashed, every POST is CSRF-checked, the haul rates live in the database so diesel can move without a redeploy, and no price reaches the board until a human verifies it.</p>
    <p>Figures on this board are member reports, not official statistics. They are a negotiating position, not a guarantee.</p>
  </footer>

  <script>
  (function () {
    var plate = document.querySelector('.bg-plate');
    if (!plate || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var queued = false;
    function paint() {
      plate.style.setProperty('--sy', (window.scrollY * 0.05).toFixed(1) + 'px');
      queued = false;
    }
    window.addEventListener('scroll', function () {
      if (!queued) { queued = true; requestAnimationFrame(paint); }
    }, { passive: true });
    paint();
  })();

  (function () {
    // Reveal-on-scroll for the long pages, so the motion is earned by reading
    // rather than fired all at once on load.
    var items = document.querySelectorAll('.reveal');
    if (!items.length || !('IntersectionObserver' in window)
        || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      items.forEach(function (el) { el.classList.add('in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { entry.target.classList.add('in'); io.unobserve(entry.target); }
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });
    items.forEach(function (el) { io.observe(el); });
  })();
  </script>
</body>
</html>
