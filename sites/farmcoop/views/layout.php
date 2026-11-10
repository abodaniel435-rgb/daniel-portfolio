<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = static function (string $needle) use ($path): string {
    $trimmed = rtrim($path, '/');
    return ($trimmed === rtrim($needle, '/') || ($needle !== '/' && str_starts_with($trimmed, $needle))) ? 'active' : '';
};
$user = Auth::user();
$appName = (string)env('APP_NAME', 'FarmCoop');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'FarmCoop — farm-fresh produce, direct from source') ?></title>
  <meta name="description" content="FarmCoop connects Nigerian smallholder farmers with restaurants, hotels and market traders. Verified farms, live quantities and a reliable order + pickup workflow.">
  <meta name="theme-color" content="#143d2b">
  <meta property="og:title" content="<?= e($title ?? 'FarmCoop') ?>">
  <meta property="og:description" content="Verified farm storefronts, live harvest quantities and a dependable order workflow for bulk buyers.">
  <meta property="og:type" content="website">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23143d2b'/%3E%3Cpath d='M9 23c0-8 5-13 14-14-1 9-6 14-14 14Z' fill='%23d9a441'/%3E%3Cpath d='M9 23c3-4 6-7 11-9' stroke='%23143d2b' stroke-width='1.6' fill='none'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <script>document.documentElement.classList.add('js');</script>
  <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <div class="topbar">
    <div class="topbar-inner">
      <span class="topbar-note"><span class="dot"></span> Direct from Nigerian farms — no middlemen, no guesswork</span>
      <div class="topbar-links">
        <a href="<?= e(url('/track')) ?>">Track an order</a>
        <a href="tel:+2347000000000">+234 700 000 0000</a>
      </div>
    </div>
  </div>

  <header class="site-header">
    <div class="nav">
      <a class="brand" href="<?= e(url('/')) ?>" aria-label="FarmCoop home">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <path d="M7 26c0-10 6.5-16.5 18-18-1.2 11.5-7.5 18-18 18Z" fill="currentColor"/>
            <path d="M7 26c4-5.5 8-9.5 14-12" stroke="#143d2b" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </span>
        <span class="brand-word">Farm<em>Coop</em></span>
      </a>
      <button class="menu-btn" type="button" aria-expanded="false" aria-controls="navlinks" aria-label="Toggle navigation">
        <span></span><span></span><span></span>
      </button>
      <nav class="nav-links" id="navlinks" aria-label="Main navigation">
        <a class="<?= $isActive('/marketplace') ?>" href="<?= e(url('/marketplace')) ?>">Marketplace</a>
        <a href="<?= e(url('/#how')) ?>">How it works</a>
        <a href="<?= e(url('/#farmers')) ?>">For farmers</a>
        <a class="<?= $isActive('/track') ?>" href="<?= e(url('/track')) ?>">Track order</a>
      </nav>
      <div class="nav-actions">
        <?php if ($user): ?>
          <a class="btn btn-ghost btn-sm nav-hide-sm" href="<?= e(url('/dashboard')) ?>">Dashboard</a>
          <a class="avatar" href="<?= e(url('/dashboard')) ?>" title="<?= e($user['name']) ?> · <?= e($user['role']) ?>">
            <?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?>
          </a>
          <form method="post" action="<?= e(url('/logout')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-ghost btn-sm nav-hide-sm" type="submit">Sign out</button>
          </form>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/login')) ?>">Sign in</a>
          <a class="btn btn-primary btn-sm" href="<?= e(url('/register')) ?>">Join FarmCoop</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <?php if (!empty($flashes)): ?>
    <div class="flashes" role="status" aria-live="polite">
      <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>">
          <span class="flash-icon" aria-hidden="true"><?= $flash['type'] === 'error' ? '!' : '✓' ?></span>
          <span><?= e($flash['message']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <main id="main"><?= $content ?></main>

  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-brand">
        <a class="brand brand-light" href="<?= e(url('/')) ?>">
          <span class="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
              <path d="M7 26c0-10 6.5-16.5 18-18-1.2 11.5-7.5 18-18 18Z" fill="currentColor"/>
              <path d="M7 26c4-5.5 8-9.5 14-12" stroke="#143d2b" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="brand-word">Farm<em>Coop</em></span>
        </a>
        <p>A produce marketplace that pays smallholder farmers properly and gives bulk buyers a supply they can verify before they commit.</p>
      </div>
      <div>
        <h4>Buyers</h4>
        <a href="<?= e(url('/marketplace')) ?>">Browse the market</a>
        <a href="<?= e(url('/register?role=buyer')) ?>">Open a buying account</a>
        <a href="<?= e(url('/track')) ?>">Track an order</a>
        <a href="<?= e(url('/#how')) ?>">How ordering works</a>
      </div>
      <div>
        <h4>Farmers</h4>
        <a href="<?= e(url('/register?role=farmer')) ?>">List your harvest</a>
        <a href="<?= e(url('/#farmers')) ?>">Why sell on FarmCoop</a>
        <a href="<?= e(url('/login')) ?>">Farmer sign in</a>
      </div>
      <div>
        <h4>Talk to us</h4>
        <a href="mailto:hello@farmcoop.ng">hello@farmcoop.ng</a>
        <a href="tel:+2347000000000">+234 700 000 0000</a>
        <span class="footer-meta">12 Adeola Odeku Street<br>Victoria Island, Lagos</span>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> FarmCoop. Built for cPanel hosting.</span>
      <span>Lagos · Kaduna · Ebonyi · Ogun</span>
    </div>
  </footer>
</body>
</html>
