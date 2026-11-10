<?php
$status = (string)($preflight['status'] ?? 'warn');
$tone = $status === 'pass' ? 'ok' : ($status === 'warn' ? 'warn' : 'bad');
$labels = ['pass' => 'Pass', 'warn' => 'Warning', 'fail' => 'Fail'];
$failures = array_values(array_filter((array)$preflight['checks'], static fn ($check) => $check['status'] === 'fail'));
$warnings = array_values(array_filter((array)$preflight['checks'], static fn ($check) => $check['status'] === 'warn'));
?>
<header class="page-head page-head-compact">
  <div class="page-head-copy">
    <p class="eyebrow">Host compatibility</p>
    <h1>Can this host run what we generate?</h1>
    <p class="lede">A required failure blocks generation. Missing MySQL is a warning: the build still writes, and the database step is marked “skipped — schema included” rather than pretending to install.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-ghost" href="/preflight"><?= fs_icon('shield', 17) ?> Run again</a>
    <?php if ($preflight['can_generate']): ?>
      <a class="btn btn-primary" href="/new"><?= fs_icon('plus', 17) ?> Start a brief</a>
    <?php else: ?>
      <span class="btn btn-primary is-disabled" aria-disabled="true">Generation blocked</span>
    <?php endif; ?>
  </div>
</header>

<section class="preflight-hero status-<?= $tone ?>">
  <div class="preflight-hero-copy">
    <p class="eyebrow">Overall result</p>
    <h2><?= $status === 'pass' ? 'Everything required is present.' : ($status === 'warn' ? 'Usable, with warnings.' : 'A required check is failing.') ?></h2>
    <p>
      <?php if ($status === 'pass'): ?>
        PHP, extensions, writable paths and the shared core all check out. MySQL is reachable, so generated sites can be installed immediately.
      <?php elseif ($status === 'warn'): ?>
        Generation can proceed, but at least one check needs attention. Database work will be skipped if MySQL stays offline — the schema is still delivered.
      <?php else: ?>
        Fix the failing checks below before generating. ForgeStack refuses to produce a build it cannot stand behind.
      <?php endif; ?>
    </p>
    <div class="preflight-hero-meta">
      <span><?= fs_e($preflight['php'] ?? PHP_VERSION) ?></span>
      <span><?= fs_e(Preflight::dbLabel()) ?></span>
      <span><?= fs_e(date('H:i:s', strtotime((string)($preflight['generated_at'] ?? 'now')))) ?></span>
      <span><?= $preflight['can_generate'] ? 'generation allowed' : 'generation blocked' ?></span>
    </div>
  </div>
  <div class="preflight-seal">
    <span class="seal-ring"></span>
    <strong><?= fs_e($labels[$status] ?? strtoupper($status)) ?></strong>
    <small><?= count($failures) ?> failures · <?= count($warnings) ?> warnings</small>
  </div>
</section>

<div class="preflight-layout">
  <section class="panel">
    <div class="panel-head">
      <div>
        <p class="eyebrow">Environment</p>
        <h2><?= count((array)$preflight['checks']) ?> checks</h2>
      </div>
      <span class="status-pill status-<?= $tone ?>"><?= fs_e(strtoupper($status)) ?></span>
    </div>
    <ul class="checks-list">
      <?php foreach ((array)$preflight['checks'] as $check): ?>
        <?php
          $checkStatus = (string)$check['status'];
          $checkTone = $checkStatus === 'pass' ? 'ok' : ($checkStatus === 'warn' ? 'warn' : 'bad');
        ?>
        <li class="check-row is-<?= fs_e($checkStatus) ?>">
          <span class="check-badge check-<?= $checkTone ?>">
            <?= $checkStatus === 'pass' ? fs_icon('check', 14) : ($checkStatus === 'warn' ? fs_icon('alert', 14) : fs_icon('alert', 14)) ?>
          </span>
          <div class="check-copy">
            <div class="check-title">
              <b><?= fs_e($check['label']) ?></b>
              <span class="micro-tag"><?= !empty($check['required']) ? 'Required' : 'Informational' ?></span>
            </div>
            <p><?= fs_e($check['detail']) ?></p>
          </div>
          <span class="status-pill status-<?= $checkTone ?>"><?= fs_e($labels[$checkStatus] ?? $checkStatus) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <aside class="preflight-aside">
    <section class="panel panel-tight">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Target stack</p>
          <h2>What generated sites need</h2>
        </div>
      </div>
      <dl class="summary-list">
        <div><dt>PHP</dt><dd><?= fs_e($preflight['target']['php'] ?? '8.1+') ?></dd></div>
        <div><dt>Extensions</dt><dd><?= fs_e(strtoupper(implode(', ', (array)($preflight['target']['extensions'] ?? [])))) ?></dd></div>
        <div><dt>Web server</dt><dd>Apache + .htaccess</dd></div>
        <div><dt>Database</dt><dd>MySQL 5.7+ / MariaDB 10.3+</dd></div>
        <div><dt>Node.js</dt><dd>Not required</dd></div>
        <div><dt>Redis / worker</dt><dd>Not required</dd></div>
        <div><dt>Cron</dt><dd>cPanel cron, CLI or HTTP</dd></div>
      </dl>
    </section>

    <section class="panel panel-tight">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Database</p>
          <h2><?= !empty($preflight['database']['ok']) ? 'Reachable' : 'Unavailable' ?></h2>
        </div>
        <span class="status-pill status-<?= !empty($preflight['database']['ok']) ? 'ok' : 'warn' ?>"><?= !empty($preflight['database']['ok']) ? 'Connected' : 'Skipped' ?></span>
      </div>
      <dl class="summary-list">
        <div><dt>Host</dt><dd><?= fs_e($preflight['database']['host'] ?? '') ?>:<?= (int)($preflight['database']['port'] ?? 0) ?></dd></div>
        <div><dt>Version</dt><dd><?= fs_e($preflight['database']['version'] ?: '—') ?></dd></div>
      </dl>
      <?php if (empty($preflight['database']['ok'])): ?>
        <p class="panel-note"><?= fs_e((string)($preflight['database']['error'] ?? 'Could not connect.')) ?></p>
        <p class="panel-note">Builds still include <code>database/schema.sql</code>, <code>seed.sql</code> and the cPanel install guide.</p>
      <?php endif; ?>
    </section>

    <section class="panel panel-tight">
      <div class="panel-head">
        <div>
          <p class="eyebrow">Note</p>
          <h2>Shared hosting first</h2>
        </div>
      </div>
      <p class="panel-note">ForgeStack targets the constraints of cPanel accounts: no Composer, no Node build step, no Redis, no queue worker. Automation uses cPanel cron and MySQL; email queues to a real outbox table.</p>
      <a class="panel-link" href="/new">Write a brief <?= fs_icon('arrow-right', 15) ?></a>
    </section>
  </aside>
</div>
