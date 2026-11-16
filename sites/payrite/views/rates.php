<?php
declare(strict_types=1);
/** @var array $bands @var array $rules @var array $ladder @var array $changed */
// Cumulative ceilings, so a band reads as "the next ₦300,000" rather than a
// width nobody can place on a payslip.
$ceil = 0.0;
$rows = [];
foreach ($bands as $i => $band) {
    $width = $band['slice_width'];
    $rows[] = [
        'from' => $ceil,
        'to' => $width === null ? null : $ceil + (float)$width,
        'rate' => (float)$band['rate'],
        'touched' => $changed[$i] ?? null,
    ];
    if ($width !== null) {
        $ceil += (float)$width;
    }
}
$ruleLabels = [
    'cra_base' => ['Consolidated relief, fixed', 'naira per year'],
    'cra_pct' => ['Consolidated relief, proportion of gross', '%'],
    'rent_pct' => ['Rent relief, proportion of annual rent', '%'],
    'rent_cap' => ['Rent relief ceiling', 'naira per year'],
    'pension_pct' => ['Default employee pension', '% of gross'],
    'employer_pension_pct' => ['Employer pension', '% of gross'],
    'nhf_pct' => ['National Housing Fund, both sides', '% of gross'],
];
?>
<section class="hero">
  <div class="hero-grid">
    <div>
      <p class="eyebrow">The rate table</p>
      <h1>Every number the payslip uses, <em>in the open.</em></h1>
      <p class="lede">PAYE under the 2026 Act is progressive: annual taxable pay is cut into slices and each slice
        is taxed at its own rate. PayRite keeps those slices and the relief constants as rows in MySQL, so when a
        budget moves them an administrator edits them on one page — and a payslip that was already issued keeps a
        copy of the table that produced it.</p>
      <div class="hero-stats">
        <div><b><?= count($rows) ?></b><span>bands</span></div>
        <div><b><?= e(money((float)$rows[0]['to'] ?? 0)) ?></b><span>first slice</span></div>
        <div><b><?= e(rtrim(rtrim(number_format((float)end($rows)['rate'], 2), '0'), '.')) ?>%</b><span>top rate</span></div>
        <div><b><?= e(money((float)$rules['rent_cap'])) ?></b><span>rent relief cap</span></div>
      </div>
    </div>
    <div class="panel">
      <h3>How a band is applied</h3>
      <ol class="steps">
        <li><span>Annual gross</span><b>monthly gross × 12</b></li>
        <li><span>less consolidated relief</span><b><?= e(number_format((float)$rules['cra_pct'], 0)) ?>% of gross + <?= e(number_format((float)$rules['cra_base'])) ?></b></li>
        <li><span>less rent relief</span><b><?= e(number_format((float)$rules['rent_pct'], 0)) ?>% of rent, max <?= e(number_format((float)$rules['rent_cap'])) ?></b></li>
        <li><span>less pension paid</span><b>employee contribution, annual</b></li>
        <li><span>= taxable pay</span><b>walked through the table below</b></li>
        <li><span>then ÷ 12</span><b>PAYE on the payslip</b></li>
      </ol>
      <p class="tiny">Reliefs are annual and are removed before any band is applied. That is why two people on the
        same gross can pay different PAYE: one claims rent, the other does not.</p>
      <p style="margin:16px 0 0"><a class="btn btn-ghost btn-sm" href="<?= e(url('/')) ?>">Back to the calculator</a></p>
    </div>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <div>
      <p class="eyebrow">Progressive slices</p>
      <h2>The PAYE bands</h2>
    </div>
    <p class="tiny">The final row has no ceiling — it taxes everything above, so no earner falls off the end of the
      table untaxed.</p>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>Band</th><th class="num">Width of slice</th><th class="num">Rate</th>
        <th class="num">Tax on a full slice</th><th>Last changed</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $i => $row): ?>
        <tr>
          <td><b><?= $i + 1 ?></b>
            <span class="muted"> · <?= e($row['to'] === null ? 'everything above ' . money($row['from']) : money($row['from']) . ' – ' . money($row['to'])) ?></span></td>
          <td class="num"><?= $row['to'] === null ? 'open-ended' : e(money($row['to'] - $row['from'])) ?></td>
          <td class="num"><?= e(rtrim(rtrim(number_format($row['rate'], 2), '0'), '.')) ?>%</td>
          <td class="num"><?= $row['to'] === null ? '—' : e(money(($row['to'] - $row['from']) * $row['rate'] / 100)) ?></td>
          <td class="tiny">
            <?php if ($row['touched'] && !empty($row['touched']['updated_at'])): ?>
              <?= e(short_date((string)$row['touched']['updated_at'])) ?>
              <span class="muted">by <?= e((string)($row['touched']['updater'] ?? 'installer')) ?></span>
            <?php else: ?>
              <span class="muted">as published</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td>Full table</td>
        <td class="num"><?= e(money($ceil)) ?> of taxable pay</td>
        <td class="num" colspan="2">then <?= e(rtrim(rtrim(number_format((float)end($rows)['rate'], 2), '0'), '.')) ?>% on the rest</td>
        <td></td>
      </tr>
    </tfoot>
  </table>
</section>

<section class="section reveal">
  <div class="section-head">
    <div>
      <p class="eyebrow">Reliefs and statutory deductions</p>
      <h2>The constants</h2>
    </div>
  </div>
  <div class="grid-2">
    <?php foreach ($ruleLabels as $code => $label): ?>
      <div class="stat">
        <b><?= e(rtrim(rtrim(number_format((float)$rules[$code], 2), '0'), '.')) ?><?= str_contains($label[1], '%') ? '%' : '' ?></b>
        <span><?= e($label[0]) ?></span>
        <p class="tiny" style="margin-top:6px"><?= e($label[1]) ?> · <span class="mono"><?= e($code) ?></span></p>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="tiny">These are read from the <span class="mono">rates</span> table on every request. The engine in
    <span class="mono">app/engines/Payroll.php</span> holds the same figures as constants, which is what lets the
    smoke test prove that a database row and a bare call agree.</p>
</section>

<section class="section reveal">
  <div class="section-head">
    <div>
      <p class="eyebrow">The table in practice</p>
      <h2>What six packages come out to</h2>
    </div>
    <p class="tiny">Each row is a fresh computation: basic 60% / housing 20% / transport 20%, rent claimed at half
      of annual gross, pension at 8%.</p>
  </div>
  <table class="table">
    <thead>
      <tr><th>Monthly gross</th><th class="num">Taxable / yr</th><th class="num">Top slice</th><th class="num">PAYE / mo</th>
        <th class="num">Deductions</th><th class="num">Net / mo</th><th class="num">Effective</th></tr>
    </thead>
    <tbody>
      <?php foreach ($ladder as $row): ?>
        <tr>
          <td><b><?= e((string)$row['label']) ?></b></td>
          <td class="num"><?= e(money((float)$row['taxable_annual'])) ?></td>
          <td class="num"><?= e(rtrim(rtrim(number_format((float)$row['marginal_rate'], 2), '0'), '.')) ?>%</td>
          <td class="num"><?= e(money((float)$row['paye'])) ?></td>
          <td class="num"><?= e(money((float)$row['pension'] + (float)$row['nhf'])) ?></td>
          <td class="num"><b><?= e(money((float)$row['net'])) ?></b></td>
          <td class="num"><?= e(number_format((float)$row['effective_rate'], 2)) ?>%</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p style="margin-top:22px">
    <a class="btn btn-primary" href="<?= e(url('/api/paye')) ?>?gross=450000&amp;rent=2400000">Read these as JSON</a>
    <a class="btn btn-ghost" href="<?= e(url('/register')) ?>" style="margin-left:12px">Create a staff account</a>
  </p>
</section>
