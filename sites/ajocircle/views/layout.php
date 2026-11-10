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
  <title><?= e($title ?? 'AjoCircle') ?></title>
  <meta name="description" content="AjoCircle gives every rotating savings group a transparent ledger: contribution schedules, due dates, verified payments and an auditable payout order.">
  <meta name="theme-color" content="#151b3d">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body>
  <header class="site-header">
    <div class="nav">
      <a class="brand" href="<?= e(url('/')) ?>" aria-label="AjoCircle home">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a8 8 0 1 1-2.34-5.66"/><path d="M20 4v4h-4"/><circle cx="12" cy="12" r="2.6" fill="currentColor" stroke="none"/></svg>
        </span>
        Ajo<em>Circle</em>
      </a>
      <button class="menu-btn" type="button" aria-expanded="false" aria-controls="navlinks" aria-label="Toggle navigation">☰</button>
      <nav class="nav-links" id="navlinks" aria-label="Main navigation">
        <a class="<?= $isActive('/circles') ?>" href="<?= e(url('/circles')) ?>">Circles</a>
        <a href="<?= e(url('/#how')) ?>">How it works</a>
        <a href="<?= e(url('/#trust')) ?>">Transparency</a>
        <?php if ($user): ?>
          <a class="<?= $isActive('/admin') ?>" href="<?= e(url($user['role'] === 'admin' ? '/admin' : '/dashboard')) ?>">
            <?= $user['role'] === 'admin' ? 'Organiser console' : 'My dashboard' ?>
          </a>
        <?php endif; ?>
      </nav>
      <div class="nav-actions">
        <?php if ($user): ?>
          <a class="avatar" href="<?= e(url($user['role'] === 'admin' ? '/admin' : '/dashboard')) ?>" title="<?= e($user['name']) ?>"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></a>
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
        <a class="brand" href="<?= e(url('/')) ?>">
          <span class="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 12a8 8 0 1 1-2.34-5.66"/><path d="M20 4v4h-4"/></svg>
          </span>
          Ajo<em>Circle</em>
        </a>
        <p>Rotating savings with receipts. Every contribution, every due date and every payout in one ledger your whole circle can see.</p>
      </div>
      <div>
        <h4>Circle</h4>
        <a href="<?= e(url('/circles')) ?>">Browse circles</a>
        <a href="<?= e(url('/register')) ?>">Create an account</a>
        <a href="<?= e(url('/login')) ?>">Member sign in</a>
        <a href="<?= e(url('/#how')) ?>">How ajo works here</a>
      </div>
      <div>
        <h4>Trust</h4>
        <a href="<?= e(url('/#trust')) ?>">Transparency promise</a>
        <a href="mailto:hello@ajocircle.test">hello@ajocircle.test</a>
        <a href="tel:+2347038082491">+234 703 808 2491</a>
        <a href="<?= e(url('/#faq')) ?>">Questions</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> AjoCircle. Built for cPanel hosting, PHP 8.2 and MySQL.</span>
      <span>Lagos, Nigeria · Contributions are recorded manually and verified by your organiser.</span>
    </div>
  </footer>
</body>
</html>
