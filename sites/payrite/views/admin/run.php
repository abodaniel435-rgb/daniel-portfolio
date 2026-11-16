<?php
declare(strict_types=1);
/** @var array $run @var array $slips @var array $totals @var array $stored */
require BASE_PATH . '/views/admin/nav.php';
$period = (string)$run['period'];
$delta = [];
foreach (['gross_total', 'paye_total', 'net_total', 'cost_total'] as $key) {
    $delta[$key] = (float)$totals[$key] - (float)$stored[$key];
}
$drift = array_sum(array_map('abs', $delta)) > 0.01;
$recost = 0.0;
$today = 0.0;
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Run · <?= e((string)$run['status']) ?></p>
      <h1><?= e(Desk::periodLabel($period)) ?></h1>
      <p class="lede"><?= (int)$run['headcount'] ?> payslip<?= (int)$run['headcount'] === 1 ? '' : 's' ?>
        <?php if ((string)$run['note'] !== ''): ?> · <?= e((string)$run['note']) ?><?php endif; ?>
        · built <?= e(short_datetime((string)$run['created_at'])) ?><?= $run['locked_at'] ? ' · locked ' . e(short_datetime((string)$run['locked_at'])) : '' ?></p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button class="btn btn-ghost" type="button" onclick="window.print()">Print</button>
      <a class="btn btn-ghost" href="<?= e(url('/admin/runs')) ?>">All runs</a>
    </div>
  </div>

  <div class="grid-4" style="margin-bottom:20px">
    <div class="stat"><b><?= e(money((float)$totals['gross_total'])) ?></b><span>gross for the month</span></div>
    <div class="stat stat-red"><b><?= e(money((float)$totals['paye_total'])) ?></b><span>PAYE to remit</span></div>
    <div class="stat"><b><?= e(money((float)$totals['pension_total'] + (float)$totals['nhf_total'])) ?></b><span>pension + NHF</span></div>
    <div class="stat stat-mint"><b><?= e(money((float)$totals['net_total'])) ?></b><span>net to pay out</span></div>
  </div>

  <?php if ($drift): ?>
    <div class="verdict warn" style="margin-bottom:20px">
      <h4>The stored totals do not match the slips</h4>
      <p>Recomputed from the payslip rows: gross <?= e(money((float)$totals['gross_total'])) ?> against
        <?= e(money((float)$stored['gross_total'])) ?> stored, net <?= e(money((float)$totals['net_total'])) ?> against
        <?= e(money((float)$stored['net_total'])) ?>. A slip was edited directly in the database. The figures above
        and below come from the slips, because that is what people were shown.</p>
    </div>
  <?php endif; ?>

  <?php if (!$slips): ?>
    <div class="verdict bad">
      <h4>This run has no slips</h4>
      <p>Every active employee either had a package that summed to zero or was added after the run was built. Void
        this run, fix the roster, then build the month again.</p>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Employee</th><th class="num">Gross</th><th class="num">Taxable / yr</th><th class="num">PAYE</th>
          <th class="num">Pension</th><th class="num">NHF</th><th class="num">Net</th><th class="num">Cost</th>
          <th class="num">Net on today's table</th></tr>
      </thead>
      <tbody>
        <?php foreach ($slips as $slip): ?>
          <?php
          // The slip's own taxable pay, run through the band table as it stands
          // today. Nothing is reverse-engineered from the deductions, so a rent
          // relief that hit the cap cannot distort the comparison. This answers
          // one question: did the rate table move since the run was built?
          $payeToday = Payroll::paye((float)$slip['taxable_annual'], Desk::bands())['annual'] / 12;
          $netToday = (float)$slip['gross'] - $payeToday - (float)$slip['pension'] - (float)$slip['nhf'];
          $recost += (float)$slip['employer_cost'];
          $today += $netToday;
          $diff = $payeToday - (float)$slip['paye'];
          ?>
          <tr>
            <td><a href="<?= e(url('/admin/employees/' . (int)$slip['employee_id'] . '/edit')) ?>"><b><?= e((string)$slip['employee_name']) ?></b></a>
              <div class="tiny mono"><?= e((string)$slip['staff_no']) ?> ·
                <?= e(rtrim(rtrim(number_format((float)$slip['marginal_rate'], 2), '0'), '.')) ?>% top slice</div></td>
            <td class="num"><?= e(money((float)$slip['gross'])) ?></td>
            <td class="num"><?= e(money((float)$slip['taxable_annual'])) ?></td>
            <td class="num"><?= e(money((float)$slip['paye'])) ?></td>
            <td class="num"><?= e(money((float)$slip['pension'])) ?></td>
            <td class="num"><?= e(money((float)$slip['nhf'])) ?></td>
            <td class="num"><b><?= e(money((float)$slip['net'])) ?></b>
              <div class="tiny"><a href="<?= e(url('/admin/employees/' . (int)$slip['employee_id'] . '/edit')) ?>">slip</a></div></td>
            <td class="num"><?= e(money((float)$slip['employer_cost'])) ?></td>
            <td class="num"><?= e(money($netToday)) ?>
              <?php if (abs($diff) >= 0.005): ?>
                <div class="tiny" style="color:<?= $diff > 0 ? 'var(--red)' : 'var(--mint)' ?>">PAYE <?= $diff > 0 ? '+' : '−' ?><?= e(number_format(abs($diff))) ?></div>
              <?php else: ?>
                <div class="tiny muted">unchanged</div>
              <?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td><?= count($slips) ?> slips</td>
          <td class="num"><?= e(money((float)$totals['gross_total'])) ?></td>
          <td class="num"><?= e(money(array_sum(array_map(static fn(array $s): float => (float)$s['taxable_annual'], $slips)))) ?></td>
          <td class="num"><?= e(money((float)$totals['paye_total'])) ?></td>
          <td class="num"><?= e(money((float)$totals['pension_total'])) ?></td>
          <td class="num"><?= e(money((float)$totals['nhf_total'])) ?></td>
          <td class="num"><?= e(money((float)$totals['net_total'])) ?></td>
          <td class="num"><?= e(money($recost)) ?></td>
          <td class="num"><?= e(money($today)) ?></td>
        </tr>
      </tfoot>
    </table>
    <p class="tiny">The last column re-runs each stored package through the band table as it stands now. On a locked
      run it is a warning about next month, not this one — an issued slip keeps the figures it was issued with.</p>
  <?php endif; ?>

  <div class="grid-3" style="margin-top:26px">
    <?php if ($run['status'] === 'draft'): ?>
      <div class="panel">
        <h3>Lock this run</h3>
        <p class="tiny">Locking publishes every slip to its linked staff account and stops the period being rebuilt.</p>
        <form method="post" action="<?= e(url('/admin/runs/' . (int)$run['id'] . '/lock')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-ok" type="submit">Lock <?= e(Desk::periodLabel($period)) ?></button>
        </form>
      </div>
    <?php elseif ($run['status'] === 'locked'): ?>
      <div class="panel">
        <h3>Notify staff</h3>
        <p class="tiny">Queues one message per employee on this run who has a linked, active login — gross, PAYE,
          pension, NHF and net, with a link to the slip. Nothing leaves the server unless
          <span class="mono">MAIL_ENABLED</span> is on.</p>
        <form method="post" action="<?= e(url('/admin/runs/' . (int)$run['id'] . '/notify')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-primary" type="submit">Queue payslip notices</button>
        </form>
        <p class="tiny" style="margin-bottom:0"><a href="<?= e(url('/admin/outbox')) ?>">Message log →</a></p>
      </div>
    <?php else: ?>
      <div class="verdict bad">
        <h4>This run is void</h4>
        <p>Its slips are hidden from staff and excluded from what anyone is expected to pay, but the rows remain so
          the trail still shows what was drafted. <?= (string)$run['note'] !== '' ? 'Reason: ' . e((string)$run['note']) . '.' : '' ?></p>
      </div>
    <?php endif; ?>

    <?php if ($run['status'] !== 'void'): ?>
      <div class="panel">
        <h3>Void and rebuild</h3>
        <p class="tiny">For when the rate table moved, or a package was wrong. The slips stay; the run stops counting
          and the month can be drafted again.</p>
        <form method="post" action="<?= e(url('/admin/runs/' . (int)$run['id'] . '/void')) ?>"
              onsubmit="return confirm('Void <?= e(Desk::periodLabel($period)) ?>? Staff will stop seeing these slips.') ">
          <?= csrf_field() ?>
          <div class="field">
            <label for="void_note">Why</label>
            <input id="void_note" name="note" type="text" maxlength="255" placeholder="Rebuilt after the June rate change">
          </div>
          <button class="btn btn-bad" type="submit">Void this run</button>
        </form>
      </div>
    <?php endif; ?>

    <div class="card">
      <h3>What was in force</h3>
      <?php $b = json_decode((string)($slips[0]['bands_json'] ?? '[]'), true) ?: []; ?>
      <?php if ($b): ?>
        <ul class="steps">
          <?php $ceil = 0.0; ?>
          <?php foreach ($b as $band): ?>
            <li><span><?= e($band['slice_width'] === null ? 'above ' . number_format($ceil)
                : number_format($ceil) . ' – ' . number_format($ceil + (float)$band['slice_width'])) ?></span>
              <b><?= e(rtrim(rtrim(number_format((float)$band['rate'], 2), '0'), '.')) ?>%</b></li>
            <?php if ($band['slice_width'] !== null) { $ceil += (float)$band['slice_width']; } ?>
          <?php endforeach; ?>
        </ul>
        <p class="tiny" style="margin-bottom:0">Copied onto every slip in this run. That is why an old slip can still
          explain itself after the table changes.</p>
      <?php else: ?>
        <p class="tiny" style="margin:0">No slips, so nothing was recorded.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
