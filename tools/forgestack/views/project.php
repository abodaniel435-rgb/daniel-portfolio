<?php
$bp = $project['blueprint'] ?? [];
$intake = $bp['intake'] ?? [];
$arch = $bp['architecture'] ?? [];
$gen = $bp['generation'] ?? [];
$steps = (array)($project['steps'] ?? []);
$qa = (array)($project['qa'] ?? []);
$db = (array)($project['database'] ?? []);
$manifest = (array)($project['manifest'] ?? []);
$port = (int)($project['port'] ?? 0);
$status = (string)($project['status'] ?? 'ready');
$siteUrl = $port > 0 ? 'http://127.0.0.1:' . $port : '';
$projectId = (string)($project['id'] ?? '');
$modules = [];
foreach (Blueprint::FEATURES as $feature) {
    if (in_array($feature, (array)($intake['features'] ?? []), true)) {
        $modules[] = $feature;
    }
}
$statusLabel = static function (string $value): string {
    return match ($value) {
        'done' => 'Done',
        'skipped' => 'Skipped',
        'failed' => 'Failed',
        default => ucfirst($value),
    };
};
$tone = static function (string $value): string {
    return match ($value) {
        'done', 'ready' => 'ok',
        'skipped' => 'warn',
        'failed', 'review' => 'bad',
        default => 'neutral',
    };
};
$qaPassed = count(array_filter((array)($qa['checks'] ?? []), static fn ($check) => !empty($check['ok'])));
$qaTotal = count((array)($qa['checks'] ?? []));
$fileCount = (int)($manifest['count'] ?? 0);
?>
<?php if (!empty($created)): ?>
  <div class="build-flash">
    <span class="build-flash-icon"><?= fs_icon('check', 18) ?></span>
    <div>
      <strong>Build complete — <?= $fileCount ?> files written.</strong>
      <p><?= fs_e((string)($db['label'] ?? 'Database not installed.')) ?> The administrator password exists only in <code>.env</code>; it is never stored in the registry.</p>
    </div>
    <?php if ($siteUrl !== ''): ?>
      <a class="btn btn-ghost btn-sm" href="<?= fs_e($siteUrl) ?>" target="_blank" rel="noopener">Open site <?= fs_icon('external', 14) ?></a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<header class="project-header">
  <div class="project-header-copy">
    <a class="back-link" href="/"><?= fs_icon('arrow-left', 15) ?> All builds</a>
    <div class="project-title-row">
      <h1><?= fs_e($project['name'] ?? 'Untitled project') ?></h1>
      <span class="status-pill status-<?= $tone($status) ?>"><?= fs_e(ucfirst($status)) ?></span>
    </div>
    <p class="lede"><?= fs_e($arch['tagline'] ?? '') ?></p>
    <div class="tag-row">
      <span class="tag"><?= fs_e($project['profile_label'] ?? '') ?></span>
      <span class="tag"><?= fs_e($project['industry'] ?? '') ?></span>
      <span class="tag"><?= fs_e($arch['palette_name'] ?? '') ?></span>
      <span class="tag tag-mono"><?= fs_e($project['id'] ?? '') ?></span>
      <span class="tag">Built <?= fs_e(fs_relative_time($project['created_at'] ?? null)) ?></span>
    </div>
  </div>
  <div class="page-actions">
    <?php if ($siteUrl !== ''): ?>
      <a class="btn btn-primary" href="<?= fs_e($siteUrl) ?>" target="_blank" rel="noopener"><?= fs_icon('play', 16) ?> Open site</a>
      <a class="btn btn-ghost" href="<?= fs_e($siteUrl) ?>/install.php" target="_blank" rel="noopener"><?= fs_icon('external', 16) ?> Installer</a>
    <?php endif; ?>
    <a class="btn btn-ghost" href="/report?id=<?= fs_e(urlencode($projectId)) ?>" target="_blank" rel="noopener"><?= fs_icon('file', 16) ?> Report</a>
  </div>
</header>

<section class="steps-panel">
  <div class="section-head section-head-compact">
    <div>
      <p class="eyebrow">Visible workflow</p>
      <h2>16 steps, each with a real timestamp and log.</h2>
    </div>
    <span class="count-chip"><?= count(array_filter($steps, static fn ($s) => ($s['status'] ?? '') === 'done')) ?>/16 done</span>
  </div>
  <ol class="step-grid">
    <?php foreach ($steps as $i => $step): ?>
      <?php $stepStatus = (string)($step['status'] ?? 'pending'); ?>
      <li class="step-card is-<?= fs_e($stepStatus) ?>">
        <div class="step-card-head">
          <span class="step-index"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <h3><?= fs_e($step['label'] ?? 'Step') ?></h3>
          <span class="status-pill status-<?= $tone($stepStatus) ?>"><?= fs_e($statusLabel($stepStatus)) ?></span>
        </div>
        <p class="step-log"><?= fs_e($step['log'] ?? '') ?></p>
        <div class="step-card-foot">
          <span class="step-time"><?= fs_icon('clock', 13) ?> <?= fs_e(date('H:i:s', strtotime((string)($step['at'] ?? 'now')))) ?></span>
          <span>Step <?= $i + 1 ?> of 16</span>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
</section>

<div class="project-lower">
  <div class="lower-main">
    <section class="panel">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Verification</p>
          <h2>Quality checks</h2>
        </div>
        <span class="status-pill status-<?= !empty($qa['passed']) ? 'ok' : 'bad' ?>"><?= (int)$qaPassed ?>/<?= (int)$qaTotal ?> passed</span>
      </div>
      <ul class="qa-list">
        <?php foreach ((array)($qa['checks'] ?? []) as $check): ?>
          <li class="qa-row">
            <span class="qa-mark <?= !empty($check['ok']) ? 'is-ok' : 'is-bad' ?>"><?= !empty($check['ok']) ? fs_icon('check', 13) : fs_icon('alert', 13) ?></span>
            <div><b><?= fs_e($check['label'] ?? '') ?></b><p><?= fs_e($check['detail'] ?? '') ?></p></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="panel">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Deployment package</p>
          <h2>Manifest&nbsp;· <?= $fileCount ?> files</h2>
        </div>
        <span class="badge-soft"><?= fs_format_bytes((int)($manifest['bytes'] ?? 0)) ?></span>
      </div>
      <p class="panel-lede">Every deployed file is hashed with sha256. <code>.env</code>, the manifest itself and runtime locks are deliberately excluded.</p>
      <div class="package-links">
        <a class="doc-link" href="/project/file?id=<?= fs_e(urlencode($projectId)) ?>&file=manifest.json" target="_blank">manifest.json <?= fs_icon('external', 14) ?></a>
        <a class="doc-link" href="/project/file?id=<?= fs_e(urlencode($projectId)) ?>&file=checksums.sha256" target="_blank">checksums.sha256 <?= fs_icon('external', 14) ?></a>
        <a class="doc-link" href="/project/file?id=<?= fs_e(urlencode($projectId)) ?>&file=.env.example" target="_blank">.env.example <?= fs_icon('external', 14) ?></a>
      </div>
      <details class="file-manifest">
        <summary>Show all <?= $fileCount ?> files</summary>
        <div class="file-list">
          <?php foreach ((array)($manifest['files'] ?? []) as $path => $meta): ?>
            <div class="file-row"><span><?= fs_e($path) ?></span><span><?= fs_format_bytes((int)($meta['bytes'] ?? 0)) ?></span></div>
          <?php endforeach; ?>
        </div>
      </details>
    </section>

    <section class="panel">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Database</p>
          <h2><?= fs_e((string)($db['database'] ?? 'Not named')) ?></h2>
        </div>
        <span class="status-pill status-<?= $tone((string)($db['status'] ?? '')) ?>"><?= fs_e(ucfirst((string)($db['status'] ?? 'unknown'))) ?></span>
      </div>
      <p class="panel-lede"><?= fs_e((string)($db['label'] ?? '')) ?></p>
      <ol class="db-steps">
        <?php foreach ((array)($db['steps'] ?? []) as $line): ?>
          <li><?= fs_e($line) ?></li>
        <?php endforeach; ?>
      </ol>
      <?php if (!empty($db['tables'])): ?>
        <div class="chip-list chip-list-tight">
          <?php foreach ((array)$db['tables'] as $table): ?><span class="chip chip-mono"><?= fs_e($table) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <aside class="lower-aside">
    <section class="panel panel-tight">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Build</p>
          <h2>At a glance</h2>
        </div>
      </div>
      <dl class="summary-list">
        <div><dt>Folder</dt><dd><code><?= fs_e($project['build_path'] ?? '') ?></code></dd></div>
        <div><dt>Pages</dt><dd><?= (int)($project['config']['pages'] ?? 0) ?> routed</dd></div>
        <div><dt>Profile</dt><dd><?= fs_e($project['profile_label'] ?? '') ?></dd></div>
        <div><dt>Palette</dt><dd><?= fs_e($project['config']['palette'] ?? '') ?></dd></div>
        <div><dt>Typography</dt><dd><?= fs_e($project['config']['fonts'] ?? '') ?></dd></div>
        <div><dt>Modules</dt><dd><?= count($modules) ?> enabled</dd></div>
        <div><dt>Local port</dt><dd><?= $port > 0 ? (int)$port : 'not set' ?></dd></div>
      </dl>
      <div class="chip-list chip-list-tight">
        <?php foreach ($modules as $module): ?><span class="chip"><?= fs_e($module) ?></span><?php endforeach; ?>
      </div>
    </section>

    <section class="panel panel-tight">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Run locally</p>
          <h2>Commands</h2>
        </div>
      </div>
      <div class="command-block">
        <span class="command-label">Serve</span>
        <code><?= fs_e((string)($project['commands']['serve'] ?? '')) ?></code>
      </div>
      <div class="command-block">
        <span class="command-label">Folder</span>
        <code>agent/forgestack/<?= fs_e($project['build_path'] ?? '') ?></code>
      </div>
      <div class="command-block">
        <span class="command-label">Installer</span>
        <code><?= $siteUrl !== '' ? fs_e($siteUrl) : 'https://yourdomain.com' ?>/install.php?key=INSTALL_KEY</code>
      </div>
    </section>

    <section class="panel panel-tight">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Documentation</p>
          <h2>Delivered with the build</h2>
        </div>
      </div>
      <div class="doc-list">
        <a class="doc-link" href="/project/file?id=<?= fs_e(urlencode($projectId)) ?>&file=README.md" target="_blank"><?= fs_icon('file', 15) ?> README.md</a>
        <a class="doc-link" href="/project/file?id=<?= fs_e(urlencode($projectId)) ?>&file=docs/DEPLOYMENT.md" target="_blank"><?= fs_icon('server', 15) ?> Deployment guide</a>
        <a class="doc-link" href="/project/file?id=<?= fs_e(urlencode($projectId)) ?>&file=docs/TECH_STACK.md" target="_blank"><?= fs_icon('code', 15) ?> Technical specification</a>
        <a class="doc-link" href="/report?id=<?= fs_e(urlencode($projectId)) ?>" target="_blank"><?= fs_icon('file', 15) ?> Printable report</a>
      </div>
    </section>

    <section class="panel panel-tight">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Portfolio</p>
          <h2>Registry entry</h2>
        </div>
      </div>
      <?php $portfolio = (array)($project['portfolio'] ?? []); ?>
      <dl class="summary-list">
        <div><dt>Category</dt><dd><?= fs_e($portfolio['category'] ?? '') ?></dd></div>
        <div><dt>Live link</dt><dd><?= fs_e($portfolio['live_link'] ?? '—') ?></dd></div>
        <div><dt>Thumbnail</dt><dd><?= fs_e($portfolio['thumbnail'] ?? '') ?></dd></div>
      </dl>
      <div class="chip-list chip-list-tight">
        <?php foreach ((array)($portfolio['key_features'] ?? []) as $feature): ?><span class="chip"><?= fs_e($feature) ?></span><?php endforeach; ?>
      </div>
      <a class="panel-link" href="/project/file?id=<?= fs_e(urlencode($projectId)) ?>&file=portfolio-entry.json" target="_blank">portfolio-entry.json <?= fs_icon('external', 14) ?></a>
    </section>
  </aside>
</div>
