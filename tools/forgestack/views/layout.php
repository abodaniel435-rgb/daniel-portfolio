<?php
$active = $active ?? '';
$dbOk = (bool)($preflightMini['database']['ok'] ?? false);
$pfStatus = (string)($preflightMini['status'] ?? 'warn');
$navItems = [
    ['key' => 'dashboard', 'href' => '/', 'label' => 'Dashboard', 'icon' => 'grid'],
    ['key' => 'new', 'href' => '/new', 'label' => 'New site brief', 'icon' => 'plus'],
    ['key' => 'preflight', 'href' => '/preflight', 'label' => 'cPanel preflight', 'icon' => 'shield'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= fs_e($title ?? 'ForgeStack') ?></title>
  <meta name="description" content="ForgeStack designs, scaffolds, documents and packages full-stack PHP + MySQL sites for cPanel hosting.">
  <meta name="theme-color" content="#201e19">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/app.css">
  <script src="/assets/app.js" defer></script>
</head>
<body class="<?= fs_e($bodyClass ?? '') ?>">
  <a class="skip-link" href="#main">Skip to content</a>
  <div class="nav-scrim" data-nav-close hidden></div>
  <div class="shell">
    <aside class="sidebar" id="sidebar" aria-label="ForgeStack navigation">
      <div class="brand">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 32 32" fill="none"><path d="M7 5h19v6H13v4h11v6H13v9H7V5z" fill="currentColor"/><path d="M22 18h4v12h-4z" fill="currentColor" opacity=".55"/></svg>
        </span>
        <span class="brand-copy">
          <strong>ForgeStack</strong>
          <span>Local site factory</span>
        </span>
      </div>

      <nav class="side-nav">
        <p class="side-label">Workspace</p>
        <?php foreach ($navItems as $item): ?>
          <a class="side-link <?= $active === $item['key'] ? 'is-active' : '' ?>" href="<?= fs_e($item['href']) ?>" <?= $active === $item['key'] ? 'aria-current="page"' : '' ?>>
            <?= fs_icon($item['icon'], 19) ?>
            <span><?= fs_e($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
        <a class="side-link" href="/new#review">
          <?= fs_icon('layers', 19) ?>
          <span>16-step pipeline</span>
        </a>
      </nav>

      <div class="side-spacer"></div>

      <div class="env-card">
        <div class="env-head">
          <span class="status-dot <?= $dbOk ? 'is-ok' : 'is-warn' ?>"></span>
          <span>Local environment</span>
        </div>
        <dl class="env-list">
          <div><dt>PHP</dt><dd><?= fs_e($preflightMini['php'] ?? PHP_VERSION) ?></dd></div>
          <div><dt>MySQL</dt><dd><?= $dbOk ? 'connected' : 'unavailable' ?></dd></div>
          <div><dt>Preflight</dt><dd><?= fs_e(strtoupper($pfStatus)) ?></dd></div>
          <div><dt>Core</dt><dd>1.0.0</dd></div>
        </dl>
        <a class="env-link" href="/preflight">Inspect checks <?= fs_icon('arrow-right', 14) ?></a>
      </div>

      <div class="side-foot">
        <span>ForgeStack <?= fs_e(FS_VERSION) ?></span>
        <span>PHP + MySQL · cPanel</span>
      </div>
    </aside>

    <div class="main-wrap">
      <header class="mobile-bar">
        <a class="brand brand-compact" href="/">
          <span class="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 32 32" fill="none"><path d="M7 5h19v6H13v4h11v6H13v9H7V5z" fill="currentColor"/></svg>
          </span>
          <strong>ForgeStack</strong>
        </a>
        <button class="icon-btn" type="button" data-nav-toggle aria-expanded="false" aria-controls="sidebar" aria-label="Open navigation">
          <span class="burger"></span>
        </button>
      </header>

      <main class="main" id="main">
        <?= $content ?>
      </main>
    </div>
  </div>
</body>
</html>
