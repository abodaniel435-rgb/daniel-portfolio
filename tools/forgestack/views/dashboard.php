<?php
$projects = $projects ?? [];
$stats = $stats ?? Store::stats();
$qaRate = count($projects) ? (int)round(($qaPassed / count($projects)) * 100) : 0;
$pipeline = [
    'Intake', 'Research', 'Architecture', 'Preflight',
    'Design', 'Frontend', 'Backend', 'Database',
    'Auth', 'Automation', 'QA', 'Fixes',
    'Optimisation', 'Package', 'Portfolio', 'Report',
];
$pfStatus = (string)($preflightMini['status'] ?? 'warn');
$pfTone = $pfStatus === 'pass' ? 'ok' : ($pfStatus === 'warn' ? 'warn' : 'bad');
$pfFails = 0;
$pfWarns = 0;
foreach ((array)($preflightMini['checks'] ?? []) as $check) {
    $pfFails += $check['status'] === 'fail' ? 1 : 0;
    $pfWarns += $check['status'] === 'warn' ? 1 : 0;
}
?>
<header class="page-head">
  <div class="page-head-copy">
    <p class="eyebrow"><span class="eyebrow-mark"></span>Local development cockpit</p>
    <h1>Ship a real site,<br><em>not a mockup.</em></h1>
    <p class="lede">ForgeStack takes a business brief, derives the architecture, writes a full PHP + MySQL application, proves it with checks and packages it for cPanel. Files on disk, tables in MySQL, no hand-waving.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="/new"><?= fs_icon('plus', 17) ?> New site brief</a>
    <a class="btn btn-ghost" href="/preflight"><?= fs_icon('shield', 17) ?> Preflight</a>
  </div>
</header>

<section class="stats-grid" aria-label="Workspace statistics">
  <article class="stat-card">
    <p class="stat-label">Sites generated</p>
    <p class="stat-value"><?= (int)$stats['projects'] ?></p>
    <p class="stat-foot"><?= count($projects) ?> in the registry</p>
  </article>
  <article class="stat-card">
    <p class="stat-label">Files written</p>
    <p class="stat-value"><?= number_format((int)$stats['files']) ?></p>
    <p class="stat-foot"><?= fs_format_bytes((int)$stats['bytes']) ?> on disk</p>
  </article>
  <article class="stat-card">
    <p class="stat-label">Databases installed</p>
    <p class="stat-value"><?= (int)$stats['databases'] ?></p>
    <p class="stat-foot">Local MySQL · 127.0.0.1:3307</p>
  </article>
  <article class="stat-card stat-card-accent">
    <p class="stat-label">QA pass rate</p>
    <p class="stat-value"><?= $qaRate ?><span class="stat-unit">%</span></p>
    <p class="stat-foot"><?= (int)$qaPassed ?> of <?= count($projects) ?> builds clean</p>
  </article>
</section>

<div class="section-head">
  <div>
    <p class="eyebrow">Recent builds</p>
    <h2>Every project keeps its manifest, checks and logs.</h2>
  </div>
  <a class="text-link" href="/new">Start another <?= fs_icon('arrow-right', 15) ?></a>
</div>

<?php if (!$projects): ?>
  <section class="empty-state">
    <span class="empty-icon"><?= fs_icon('spark', 26) ?></span>
    <h2>No builds yet.</h2>
    <p>Give ForgeStack a brief. It will derive a page map and data model, run the cPanel preflight, then generate a real application you can install and browse.</p>
    <a class="btn btn-primary" href="/new"><?= fs_icon('plus', 17) ?> Write the first brief</a>
  </section>
<?php else: ?>
  <section class="project-grid">
    <?php foreach (array_slice($projects, 0, 6) as $project): ?>
      <?php
        $steps = (array)($project['steps'] ?? []);
        $done = count(array_filter($steps, static fn ($s) => ($s['status'] ?? '') === 'done'));
        $db = (string)($project['database']['status'] ?? 'skipped');
        $status = (string)($project['status'] ?? 'ready');
      ?>
      <a class="project-card" href="/project?id=<?= fs_e(urlencode((string)$project['id'])) ?>">
        <div class="project-top">
          <span class="profile-tag"><?= fs_e($project['profile_label'] ?? $project['industry'] ?? '') ?></span>
          <span class="status-pill status-<?= $status === 'ready' ? 'ok' : 'warn' ?>"><?= fs_e(ucfirst($status)) ?></span>
        </div>
        <h3><?= fs_e($project['name'] ?? $project['slug'] ?? 'Untitled') ?></h3>
        <p class="project-tagline"><?= fs_e($project['blueprint']['architecture']['tagline'] ?? '') ?></p>
        <div class="step-bar" aria-label="<?= (int)$done ?> of 16 steps complete">
          <?php for ($i = 0; $i < 16; $i++): ?>
            <?php $stepStatus = $steps[$i]['status'] ?? 'pending'; ?>
            <span class="step-tick is-<?= fs_e($stepStatus) ?>" title="<?= fs_e(($steps[$i]['label'] ?? 'Step ' . ($i + 1)) . ' — ' . $stepStatus) ?>"></span>
          <?php endfor; ?>
        </div>
        <dl class="project-meta">
          <div><dt>Files</dt><dd><?= (int)($project['manifest']['count'] ?? 0) ?></dd></div>
          <div><dt>Database</dt><dd><?= fs_e($db) ?></dd></div>
          <div><dt>Port</dt><dd><?= (int)($project['port'] ?? 0) ?: '—' ?></dd></div>
          <div><dt>Built</dt><dd><?= fs_e(fs_relative_time($project['created_at'] ?? null)) ?></dd></div>
        </dl>
      </a>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<div class="lower-grid">
  <section class="panel">
    <div class="panel-head">
      <div>
        <p class="eyebrow">Target host</p>
        <h2>cPanel preflight</h2>
      </div>
      <span class="status-pill status-<?= $pfTone ?>"><?= fs_e(strtoupper($pfStatus)) ?></span>
    </div>
    <p class="panel-lede">Generation is blocked only by required failures. Missing MySQL is a warning: the build proceeds and the database step is marked “skipped — schema included”.</p>
    <div class="preflight-summary">
      <div class="pf-metric"><strong><?= count((array)($preflightMini['checks'] ?? [])) ?></strong><span>checks</span></div>
      <div class="pf-metric"><strong><?= (int)$pfWarns ?></strong><span>warnings</span></div>
      <div class="pf-metric"><strong><?= (int)$pfFails ?></strong><span>failures</span></div>
      <div class="pf-metric"><strong><?= fs_e($preflightMini['php'] ?? PHP_VERSION) ?></strong><span>PHP</span></div>
    </div>
    <a class="panel-link" href="/preflight">Open the full report <?= fs_icon('arrow-right', 15) ?></a>
  </section>

  <section class="panel">
    <div class="panel-head">
      <div>
        <p class="eyebrow">Always visible</p>
        <h2>The 16-step pipeline</h2>
      </div>
      <span class="count-chip">16</span>
    </div>
    <ol class="pipeline-mini">
      <?php foreach ($pipeline as $i => $label): ?>
        <li><span><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span><?= fs_e($label) ?></li>
      <?php endforeach; ?>
    </ol>
    <div class="panel-foot-row">
      <span><?= (int)$portfolioCount ?> portfolio <?= $portfolioCount === 1 ? 'entry' : 'entries' ?></span>
      <a class="panel-link" href="/new#review">See how it runs <?= fs_icon('arrow-right', 15) ?></a>
    </div>
  </section>
</div>
