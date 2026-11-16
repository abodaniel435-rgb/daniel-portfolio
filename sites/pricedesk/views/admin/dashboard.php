<?php
declare(strict_types=1);
/** @var array<string,mixed> $counts @var array<int,array<string,mixed>> $coverage @var array<int,array<string,mixed>> $recentAudit */
require BASE_PATH . '/views/admin/nav.php';
$pending = (int)($counts['pending'] ?? 0);
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Console</p>
      <h1>The queue is the product</h1>
      <p class="lede">Everything else on this screen exists to keep the queue honest: the reference data
        decides what a crate weighs, the rates decide what a trip costs, and the audit trail says who
        moved which number.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/admin/queue')) ?>">Open the queue<?= $pending ? ' (' . $pending . ')' : '' ?></a>
  </div>

  <div class="stat-row" style="margin:0 0 30px">
    <div class="stat"><b><?= $pending ?></b><span>reports awaiting a decision</span></div>
    <div class="stat"><b><?= (int)($counts['verified'] ?? 0) ?></b><span>verified prices on the board</span></div>
    <div class="stat"><b><?= (int)($counts['rejected'] ?? 0) ?></b><span>rejected with a reason</span></div>
    <div class="stat"><b><?= (int)($counts['markets'] ?? 0) ?></b><span>active markets</span></div>
    <div class="stat"><b><?= (int)($counts['commodities'] ?? 0) ?></b><span>tracked commodities</span></div>
    <div class="stat"><b><?= (int)($counts['users'] ?? 0) ?></b><span>accounts</span></div>
    <div class="stat"><b><?= (int)($counts['queued'] ?? 0) ?></b><span>emails queued</span></div>
  </div>

  <div class="split-page">
    <div class="panel">
      <h3>Board coverage</h3>
      <p class="tiny">How much verified evidence each commodity line stands on, and when it last heard
        from a market. A line nobody has filed against for a fortnight is a line the board should admit
        it does not know.</p>
      <div class="table-wrap" style="box-shadow:none">
        <table>
          <thead>
            <tr>
              <th scope="col">Commodity</th>
              <th scope="col" class="n">Markets</th>
              <th scope="col" class="n">Verified</th>
              <th scope="col">Last filed</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($coverage as $row): ?>
              <tr>
                <td><?= e((string)$row['name']) ?></td>
                <td class="n"><?= (int)$row['markets'] ?></td>
                <td class="n"><?= (int)$row['verified'] ?></td>
                <td class="mono"><?= e(short_date($row['latest'] ?? null)) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($coverage === []): ?>
              <tr><td colspan="4" class="muted">No commodities yet. Add them under Markets and commodities.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div>
      <div class="panel">
        <h3>Run the automation now</h3>
        <p class="tiny">Three tasks: nudge watchers whose line has gone quiet, flag queued quotes that sit
          far from their verified peers, and send the weekly digest to administrators. On a server this is
          a cPanel cron job pointing at <span class="mono">php cron/run.php</span>; the button runs the
          same code once, on purpose.</p>
        <form method="post" action="<?= e(url('/admin/automation')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-ghost" type="submit">Run automation</button>
        </form>
        <p class="tiny" style="margin:12px 0 0">It locks
          <span class="mono">storage/cron.lock</span>, so an overlapping cron tick is skipped rather than
          doubled, and every run is appended to
          <span class="mono">storage/automation.log</span>.</p>
      </div>

      <div class="panel" style="margin-top:16px">
        <h3>Most recent activity</h3>
        <?php if ($recentAudit === []): ?>
          <p class="tiny" style="margin:0">Nothing recorded yet.</p>
        <?php else: ?>
          <?php foreach ($recentAudit as $entry): ?>
            <div class="readout-row">
              <span><?= e((string)$entry['action']) ?> <span class="muted">· <?= e((string)($entry['actor'] ?? 'system')) ?></span></span>
              <b><?= e(short_datetime((string)$entry['created_at'])) ?></b>
            </div>
          <?php endforeach; ?>
          <p class="tiny" style="margin:12px 0 0"><a href="<?= e(url('/admin/audit')) ?>">Full audit trail</a> — 200 most recent entries.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
