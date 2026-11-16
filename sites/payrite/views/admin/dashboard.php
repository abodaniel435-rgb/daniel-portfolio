<?php
declare(strict_types=1);
/** @var array $summary @var ?array $last @var ?array $draft @var string $period @var array $roster @var array $queries @var array $calculations @var array $bands */
require BASE_PATH . '/views/admin/nav.php';
$monthly = 0.0;
foreach ($roster as $row) {
    $p = Desk::compute($row);
    $monthly += empty($p['empty']) ? (float)$p['employer_cost'] : 0.0;
}
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Payroll desk</p>
      <h1>The month, and what it costs</h1>
      <p class="lede">The roster, the band table and the runs all sit behind this one role. Nothing on this screen is
        cached from a previous build: the projections are computed from the current rate table every time, so a band
        you change here is reflected before you finish scrolling.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn btn-ghost" href="<?= e(url('/admin/employees/new')) ?>">Add to roster</a>
      <a class="btn btn-primary" href="<?= e(url('/admin/runs')) ?>">Build <?= e(Desk::periodLabel($period)) ?> run</a>
    </div>
  </div>

  <div class="grid-4" style="margin-bottom:18px">
    <div class="stat"><b><?= (int)$summary['roster'] ?></b><span>active on roster</span></div>
    <div class="stat stat-mint"><b><?= (int)$summary['linked'] ?></b><span>linked to a login</span></div>
    <div class="stat"><b><?= (int)$summary['runs'] ?></b><span>runs built</span></div>
    <div class="stat <?= (int)$summary['drafts'] ? 'stat-red' : '' ?>"><b><?= (int)$summary['drafts'] ?></b><span>drafts waiting to lock</span></div>
  </div>
  <div class="grid-3" style="margin-bottom:30px">
    <div class="stat"><b><?= e(money((float)$summary['paye_ytd'])) ?></b><span>PAYE across non-void runs</span></div>
    <div class="stat"><b><?= e(money((float)$summary['net_ytd'])) ?></b><span>net paid out</span></div>
    <div class="stat stat-mint"><b><?= e(money($monthly)) ?></b><span>projected employer cost / month</span></div>
  </div>

  <div class="hero-grid">
    <div>
      <div class="panel">
        <h3>Last run</h3>
        <?php if ($last === null): ?>
          <p class="tiny" style="margin:0">No payroll run has been built yet. Add the roster, then build a month —
            every slip in it is created in one transaction, so a failed run leaves no half-written month behind.</p>
        <?php else: ?>
          <dl class="kv">
            <dt>Period</dt><dd><?= e(Desk::periodLabel((string)$last['period'])) ?></dd>
            <dt>Status</dt><dd><span class="badge badge-<?= $last['status'] === 'locked' ? 'ok' : ($last['status'] === 'void' ? 'bad' : 'warn') ?>"><?= e((string)$last['status']) ?></span></dd>
            <dt>Headcount</dt><dd><?= (int)$last['headcount'] ?></dd>
            <dt>Gross</dt><dd><?= e(money((float)$last['gross_total'])) ?></dd>
            <dt>PAYE</dt><dd><?= e(money((float)$last['paye_total'])) ?></dd>
            <dt>Net</dt><dd><?= e(money((float)$last['net_total'])) ?></dd>
            <dt>Employer cost</dt><dd><?= e(money((float)$last['cost_total'])) ?></dd>
          </dl>
          <p class="tiny" style="margin-bottom:0"><a href="<?= e(url('/admin/runs/' . (int)$last['id'])) ?>">Open the run</a></p>
        <?php endif; ?>
      </div>

      <?php if ($draft !== null): ?>
        <div class="verdict warn" style="margin-top:18px">
          <h4><?= e(Desk::periodLabel((string)$draft['period'])) ?> is still a draft</h4>
          <p>Staff cannot see a draft. Check the slips, then
            <a href="<?= e(url('/admin/runs/' . (int)$draft['id'])) ?>">lock it on the run page</a>.</p>
        </div>
      <?php endif; ?>

      <div class="panel" style="margin-top:18px">
        <h3>Roster at a glance</h3>
        <?php if (!$roster): ?>
          <p class="tiny" style="margin:0">The roster is empty. <a href="<?= e(url('/admin/employees/new')) ?>">Add the first person</a>.</p>
        <?php else: ?>
          <table class="table">
            <thead><tr><th>Name</th><th class="num">Gross</th><th class="num">PAYE</th><th class="num">Net</th><th class="num">Cost</th></tr></thead>
            <tbody>
              <?php foreach ($roster as $row): ?>
                <?php $p = Desk::compute($row); ?>
                <tr>
                  <td><a href="<?= e(url('/admin/employees/' . (int)$row['id'] . '/edit')) ?>"><b><?= e((string)$row['name']) ?></b></a>
                    <div class="tiny mono"><?= e((string)$row['staff_no']) ?></div></td>
                  <td class="num"><?= e(money((float)($p['gross'] ?? 0))) ?></td>
                  <td class="num"><?= e(money((float)($p['paye'] ?? 0))) ?></td>
                  <td class="num"><?= e(money((float)($p['net'] ?? 0))) ?></td>
                  <td class="num"><?= e(money((float)($p['employer_cost'] ?? 0))) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p class="tiny"><a href="<?= e(url('/admin/employees')) ?>">Full roster with status and filters →</a></p>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <div class="panel">
        <h3>Queries from staff</h3>
        <?php if (!$queries): ?>
          <p class="tiny" style="margin:0">Nobody has raised a query. Notes staff write on their own package page
            land here and clear when you overwrite them on the roster.</p>
        <?php else: ?>
          <?php foreach ($queries as $row): ?>
            <div class="readout-row" style="align-items:flex-start">
              <span><a href="<?= e(url('/admin/employees/' . (int)$row['id'] . '/edit')) ?>"><?= e((string)$row['name']) ?></a>
                <span class="mono muted"> · <?= e((string)$row['staff_no']) ?></span>
                <div class="tiny" style="margin-top:4px"><?= e((string)$row['query_note']) ?></div></span>
              <b class="tiny" style="font-weight:400"><?= e(short_date((string)$row['updated_at'])) ?></b>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="panel" style="margin-top:18px">
        <h3>Band table in force</h3>
        <ul class="steps">
          <?php $ceil = 0.0; ?>
          <?php foreach ($bands as $band): ?>
            <li><span><?= e($band['slice_width'] === null
                ? 'above ' . number_format($ceil)
                : number_format($ceil) . ' – ' . number_format($ceil + (float)$band['slice_width'])) ?></span>
              <b><?= e(rtrim(rtrim(number_format((float)$band['rate'], 2), '0'), '.')) ?>%</b></li>
            <?php if ($band['slice_width'] !== null) { $ceil += (float)$band['slice_width']; } ?>
          <?php endforeach; ?>
        </ul>
        <p class="tiny" style="margin-bottom:0"><a href="<?= e(url('/admin/rules')) ?>">Edit bands and reliefs</a> —
          <?= (int)$summary['band_count'] ?> bands live. Changing them affects future runs only; locked slips keep
          the table they were built with.</p>
      </div>

      <div class="panel" style="margin-top:18px">
        <h3>Recent calculator use</h3>
        <?php if (!$calculations): ?>
          <p class="tiny" style="margin:0">Nothing logged yet.</p>
        <?php else: ?>
          <?php foreach ($calculations as $row): ?>
            <div class="readout-row">
              <span><?= e((string)$row['label']) ?></span>
              <b><?= e((string)$row['headline']) ?></b>
            </div>
          <?php endforeach; ?>
          <p class="tiny" style="margin:12px 0 0">Public visits to the calculator are stored with the inputs and the
            result, so a figure someone quotes back to you can be reproduced. <a href="<?= e(url('/admin/audit')) ?>">Audit trail →</a></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
