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
  <title><?= e($title ?? 'PayRite') ?></title>
  <meta name="description" content="Nigerian payroll done in one pass: progressive PAYE under the 2026 Act, consolidated and rent relief, pension and NHF, payslips a signed-in employee can read, and a rate table an administrator can change without redeploying.">
  <meta name="theme-color" content="#131a20">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23131a20'/><path d='M8 22V10h8M8 16h7' fill='none' stroke='%23d99a2b' stroke-width='2.6' stroke-linecap='round'/><circle cx='21' cy='20' r='3.4' fill='none' stroke='%23d99a2b' stroke-width='2.2'/></svg>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/app.css') ?>">
</head>
<body class="anim <?= e($bodyClass ?? '') ?>">
  <div class="bg-plate" aria-hidden="true"></div>

  <header class="masthead">
    <div class="masthead-in">
      <a class="brand" href="<?= e(url('/')) ?>">
        <span class="brand-mark" aria-hidden="true">P</span>
        <span class="brand-text">PayRite<small>PAYE, pension and net pay in one pass</small></span>
      </a>
      <nav class="mastnav" aria-label="Main">
        <a href="<?= e(url('/rates')) ?>">Bands &amp; reliefs</a>
        <?php if (!$user): ?>
          <a href="<?= e(url('/api/paye')) ?>">API</a>
          <a href="<?= e(url('/login')) ?>">Sign in</a>
          <a class="btn btn-gold btn-sm" href="<?= e(url('/register')) ?>">Staff account</a>
        <?php else: ?>
          <?php if ($user['role'] === 'admin'): ?>
            <!-- An administrator has no payslips of their own: the staff links
               would bounce off staff_guard, so the desk gets its own set. -->
            <a href="<?= e(url('/admin')) ?>">Desk</a>
            <a href="<?= e(url('/admin/employees')) ?>">Roster</a>
            <a href="<?= e(url('/admin/runs')) ?>">Runs</a>
            <a href="<?= e(url('/admin/rules')) ?>">Rates</a>
          <?php else: ?>
            <a href="<?= e(url('/payslips')) ?>">My payslips</a>
            <a href="<?= e(url('/profile')) ?>">My package</a>
          <?php endif; ?>
          <form method="post" action="<?= e(url('/logout')) ?>" class="inlineform">
            <?= csrf_field() ?>
            <button class="linklike" type="submit">Sign out · <?= e($user['name']) ?></button>
          </form>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <?php foreach (($flashes ?? []) as $flash): ?>
    <div class="flash flash-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
  <?php endforeach; ?>

  <main class="page"><?= $content ?></main>

  <footer class="foot">
    <p>PayRite — a PHP 8.2 / MySQL payroll build. Passwords are bcrypt-hashed, every POST is CSRF-checked, the PAYE
      bands and relief constants live in the database so a rate change is a row update rather than a redeploy, and a
      payslip stores the band table it was computed with so an old slip still shows the sums that produced it.</p>
    <p class="tiny">Figures follow the progressive PAYE structure published for the 2026 Act. They are a working model
      for a small employer, not tax advice — file against the rates your state and pension fund administrator confirm.</p>
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
