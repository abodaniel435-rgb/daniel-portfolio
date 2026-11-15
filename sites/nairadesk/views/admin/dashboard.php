<?php declare(strict_types=1); ?>
<p class="eyebrow">Administrator</p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)">Console</h1>
<p class="lede" style="max-width:64ch">Accounts, statutory figures and the trail every write left behind. Nothing here is decorative — each number below is a live query against the same tables the calculator reads.</p>

<div class="stat-row">
  <div class="stat"><b><?= number_format($users) ?></b><span>Accounts</span></div>
  <div class="stat"><b><?= number_format($companies) ?></b><span>Workspaces</span></div>
  <div class="stat"><b><?= number_format($runs) ?></b><span>Pay runs stored</span></div>
  <div class="stat"><b><?= number_format($calcs) ?></b><span>Calculations logged</span></div>
</div>

<div class="grid-2" style="margin-top:28px">
  <div class="panel">
    <h3>Where to go</h3>
    <div class="readout-row"><span>Roster &amp; account status</span><b><a href="<?= e(url('/admin/users')) ?>">Accounts →</a></b></div>
    <div class="readout-row"><span>PAYE bands, CRA, pension, NHF</span><b><a href="<?= e(url('/admin/rates')) ?>">Statutory rates →</a></b></div>
    <div class="readout-row"><span>Every write, with its actor</span><b><a href="<?= e(url('/admin/audit')) ?>">Audit trail →</a></b></div>
    <div class="readout-row"><span>All companies, one screen</span><b><a href="<?= e(url('/admin/runs')) ?>">Pay runs →</a></b></div>
  </div>

  <div class="panel">
    <h3>Calculations by tool</h3>
    <?php if ($topTools): ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tool</th><th class="n">Logged</th></tr></thead>
        <tbody>
        <?php foreach ($topTools as $tool): ?>
          <tr><td><?= e(ucfirst((string)$tool['tool'])) ?></td><td class="n"><?= number_format((int)$tool['n']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p style="font-size:.9rem;color:var(--ink-3)">Nothing has been computed yet. The table fills the first time a signed-in member runs the engine.</p>
    <?php endif; ?>
  </div>
</div>

<h2 style="margin-top:32px;font-size:1.15rem">Latest activity</h2>
<div class="table-wrap" style="margin-top:14px">
  <table>
    <thead><tr><th>When</th><th>Action</th><th>Target</th><th>Detail</th></tr></thead>
    <tbody>
    <?php foreach ($recentAudit as $row): ?>
      <?php $meta = json_decode((string)($row['meta'] ?? ''), true); ?>
      <tr>
        <td><?= e(short_datetime($row['created_at'])) ?></td>
        <td><code><?= e($row['action']) ?></code></td>
        <td><?= e($row['target']) ?></td>
        <td style="font-size:.84rem;color:var(--ink-3)"><?= $meta === null ? '—' : e(implode(' · ', array_map(
            static fn ($k, $v) => $k . '=' . (is_scalar($v) ? (string)$v : json_encode($v)),
            array_keys($meta), array_values($meta)
        ))) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$recentAudit): ?><tr><td colspan="4">No writes recorded yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
