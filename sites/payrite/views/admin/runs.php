<?php
declare(strict_types=1);
/** @var array $runs @var string $period @var int $headcount */
require BASE_PATH . '/views/admin/nav.php';
$built = [];
foreach ($runs as $run) {
    $key = (string)$run['period'];
    /* A voided month can be rebuilt, so one period may hold more than one run.
       The warning is about the latest attempt, which is the one still live. */
    if (!isset($built[$key]) || (int)$run['id'] > (int)$built[$key]['id']) {
        $built[$key] = $run;
    }
}
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Payroll runs</p>
      <h1>One month, one transaction</h1>
      <p class="lede">Building a run computes every active employee through the band table currently in force and
        writes the slips inside a single database transaction — so a failure halfway leaves no partial month. The
        period is unique: a second run for the same month is refused rather than quietly doubling what people are
        owed.</p>
    </div>
  </div>

  <div class="hero-grid">
    <div>
      <div class="panel">
        <h3>Build a run</h3>
        <?php if ($headcount === 0): ?>
          <div class="verdict bad" style="margin-top:10px">
            <h4>No active employees</h4>
            <p>A run pays the roster. <a href="<?= e(url('/admin/employees/new')) ?>">Add someone first</a>.</p>
          </div>
        <?php else: ?>
          <p class="tiny" style="margin-top:0"><?= (int)$headcount ?> active employee<?= $headcount === 1 ? '' : 's' ?>
            will be paysliped, using the band table and relief constants as they stand right now.</p>
          <form method="post" action="<?= e(url('/admin/runs')) ?>" style="margin-top:14px">
            <?= csrf_field() ?>
            <div class="grid-2">
              <div class="field">
                <label for="period">Period</label>
                <input id="period" name="period" type="month" required value="<?= e($period) ?>"
                       min="2020-01" max="<?= e(date('Y-12')) ?>">
              </div>
              <div class="field">
                <label for="note">Note for the record</label>
                <input id="note" name="note" type="text" maxlength="255" placeholder="13th month, arrears, advance…">
              </div>
            </div>
            <?php if (isset($built[$period])): ?>
              <p class="tiny" style="color:var(--red)"><?= e(Desk::periodLabel($period)) ?> already exists
                (<span class="mono"><?= e((string)$built[$period]['status']) ?></span>).
                <a href="<?= e(url('/admin/runs/' . (int)$built[$period]['id'])) ?>">Open it</a> instead — or void that
                run first if the month has to be recalculated.</p>
            <?php endif; ?>
            <button class="btn btn-primary" type="submit">Build <?= e(Desk::periodLabel($period)) ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <div class="card">
        <h3>Draft, locked, void</h3>
        <p class="tiny"><b>Draft</b> — built, visible to you only. Staff cannot see it.<br>
          <b>Locked</b> — published: the slips appear on each linked employee's list, and the figures stop moving.<br>
          <b>Void</b> — withdrawn from every list. The slips stay in the database so the trail still shows what was
          drafted before it was corrected.</p>
      </div>
      <div class="card" style="margin-top:16px">
        <h3>Changing a locked month</h3>
        <p class="tiny">You don't. Build a run for the correct month after voiding this one, or carry the difference
          in the next period with a note. Editing a locked slip in place would break the totals an employee already
          read.</p>
      </div>
    </div>
  </div>

  <h2 style="font-size:22px;margin:34px 0 6px">Every run</h2>
  <?php if (!$runs): ?>
    <p class="tiny">Nothing built yet.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Period</th><th>Status</th><th class="num">Heads</th><th class="num">Gross</th><th class="num">PAYE</th>
          <th class="num">Net</th><th class="num">Employer cost</th><th>Locked</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($runs as $run): ?>
          <tr>
            <td><b><?= e(Desk::periodLabel((string)$run['period'])) ?></b>
              <?php if ((string)$run['note'] !== ''): ?><div class="tiny"><?= e((string)$run['note']) ?></div><?php endif; ?></td>
            <td><span class="badge badge-<?= $run['status'] === 'locked' ? 'ok' : ($run['status'] === 'void' ? 'bad' : 'warn') ?>"><?= e((string)$run['status']) ?></span></td>
            <td class="num"><?= (int)$run['headcount'] ?></td>
            <td class="num"><?= e(money((float)$run['gross_total'])) ?></td>
            <td class="num"><?= e(money((float)$run['paye_total'])) ?></td>
            <td class="num"><b><?= e(money((float)$run['net_total'])) ?></b></td>
            <td class="num"><?= e(money((float)$run['cost_total'])) ?></td>
            <td class="tiny"><?= $run['locked_at'] ? e(short_datetime((string)$run['locked_at'])) : '—' ?></td>
            <td><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/runs/' . (int)$run['id'])) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="tiny">Totals are the sums stored when the run was built. The run page recomputes them from the slips and
      flags the difference if a row was ever edited by hand.</p>
  <?php endif; ?>
</section>
