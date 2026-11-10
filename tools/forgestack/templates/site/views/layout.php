<?php
$site = site_config();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = base_path();
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ?: '/';
}
$isActive = static function (string $needle) use ($path): string {
    $needle = rtrim($needle, '/') ?: '/';
    if ($needle === '/') {
        return $path === '/' ? 'is-active' : '';
    }
    return str_starts_with($path, $needle) ? 'is-active' : '';
};
$user = Auth::user();
$palette = $site['palette'];
$variant = (string)$site['variant'];
$navPages = array_values(array_filter($site['pages'], static fn ($p) => !empty($p['nav'])));
$bookPath = '/book';
$portalPath = Auth::is('admin') ? '/admin' : '/portal';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $site['app_name']) ?></title>
  <meta name="description" content="<?= e($description ?? $site['description']) ?>">
  <meta name="theme-color" content="<?= e($palette['brand-deep']) ?>">
  <meta property="og:title" content="<?= e($title ?? $site['app_name']) ?>">
  <meta property="og:description" content="<?= e($description ?? $site['description']) ?>">
  <meta property="og:type" content="website">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?<?= e($site['fonts']['google']) ?>&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <style>
    :root{
      --ink:<?= e($palette['ink']) ?>;
      --brand:<?= e($palette['brand']) ?>;
      --brand-deep:<?= e($palette['brand-deep']) ?>;
      --accent:<?= e($palette['accent']) ?>;
      --paper:<?= e($palette['paper']) ?>;
      --surface:<?= e($palette['surface']) ?>;
      --soft:<?= e($palette['soft']) ?>;
      --line:<?= e($palette['line']) ?>;
      --muted:<?= e($palette['muted']) ?>;
      --on-brand:<?= e($palette['on-brand']) ?>;
      --font-display:"<?= e($site['fonts']['display']) ?>",Georgia,serif;
      --font-body:"<?= e($site['fonts']['body']) ?>",system-ui,sans-serif;
    }
  </style>
  <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body class="variant-<?= e($variant) ?> area-<?= e($area ?? 'public') ?>">
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="nav">
      <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($site['app_name']) ?> home">
        <span class="brand-mark" aria-hidden="true"><?= e(mb_substr($site['app_name'], 0, 1)) ?></span>
        <span class="brand-name"><?= e($site['app_name']) ?></span>
      </a>
      <button class="menu-btn" type="button" aria-expanded="false" aria-controls="navlinks" aria-label="Toggle navigation">
        <span></span><span></span>
      </button>
      <nav class="nav-links" id="navlinks" aria-label="Main navigation">
        <?php foreach ($navPages as $page): ?>
          <a class="<?= $isActive($page['path']) ?>" href="<?= e(url($page['path'])) ?>"><?= e($page['label']) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="nav-actions">
        <?php if ($user): ?>
          <a class="nav-portal" href="<?= e(url($portalPath)) ?>"><?= e(Auth::is('admin') ? 'Desk' : $site['labels']['portal']) ?></a>
          <form method="post" action="<?= e(url('/logout')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-quiet btn-sm" type="submit">Sign out</button>
          </form>
        <?php else: ?>
          <?php if (cfg('modules.auth', false)): ?>
            <a class="nav-portal" href="<?= e(url('/login')) ?>">Sign in</a>
          <?php endif; ?>
          <a class="btn btn-primary btn-sm" href="<?= e(url($bookPath)) ?>"><?= e($site['labels']['book']) ?></a>
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

  <main id="main"><?= $content ?></main>

  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-brand">
        <a class="brand brand-light" href="<?= e(url('/')) ?>">
          <span class="brand-mark" aria-hidden="true"><?= e(mb_substr($site['app_name'], 0, 1)) ?></span>
          <span class="brand-name"><?= e($site['app_name']) ?></span>
        </a>
        <p><?= e($site['copy']['footer']['blurb']) ?></p>
      </div>
      <div>
        <h4>Explore</h4>
        <?php foreach ($site['pages'] as $page): ?>
          <?php if (in_array($page['path'], ['/book', '/track', '/shop', '/contact', '/about', '/services', '/faq'], true)): ?>
            <a href="<?= e(url($page['path'])) ?>"><?= e($page['label']) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div>
        <h4>Visit &amp; contact</h4>
        <p class="footer-address"><?= e($site['contact']['address']) ?></p>
        <p class="footer-address"><?= e($site['copy']['footer']['hours']) ?></p>
        <a href="mailto:<?= e($site['contact']['email']) ?>"><?= e($site['contact']['email']) ?></a>
        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $site['contact']['phone'])) ?>"><?= e($site['contact']['phone']) ?></a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= e($site['app_name']) ?>. All rights reserved.</span>
      <span>
        <?php if (cfg('modules.auth', false)): ?><a href="<?= e(url('/login')) ?>">Staff sign in</a><?php endif; ?>
        <a href="<?= e(url('/contact')) ?>">Contact</a>
      </span>
    </div>
  </footer>
</body>
</html>
