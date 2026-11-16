<?php
declare(strict_types=1);
/** @var array $slip @var array $steps @var ?array $run */
$rules = json_decode((string)($slip['rules_json'] ?? '{}'), true) ?: [];
$deductions = (float)$slip['paye'] + (float)$slip['pension'] + (float)$slip['nhf'];
// Cumulative ceilings so the stored band table reads the way the rates page does.
$ceil = 0.0;
$banded = [];
foreach ($steps as $band) {
    $width = $band['slice_width'] ?? null;
    $banded[] = ['from' => $ceil, 'to' => $width === null ? null : $ceil + (float)$width, 'rate' => (float)$band['rate']];
    if ($width !== null) {
        $ceil += (float)$width;
    }
}
?>
<section class="section" style="margin-top:0">
  <div class="tabs" style="margin-bottom:22px">
    <a class="chip" href="<?= e(url('/payslips')) ?>">All my payslips</a>
    <a class="chip" href="<?= e(url('/profile')) ?>">My package</a>
    <button class="chip" type="button" onclick="window.print()">Print this slip</button>
  </div>

  <article class="slip">
    <header class="slip-head">
      <div>
        <p class="eyebrow">Payslip · <?= e(Desk::periodLabel((string)$slip['period'])) ?></p>
        <h1><?= e((string)$slip['employee_name']) ?></h1>
        <p class="tiny" style="margin-top:4px"><?= e((string)($slip['role_title'] ?: '—')) ?> ·
          staff no. <span class="mono"><?= e((string)$slip['staff_no']) ?></span></p>
      </div>
      <div style="text-align:right">
        <p class="eyebrow" style="margin-bottom:6px">Net pay</p>
        <p class="mono" style="margin:0;font-size:26px;font-weight:600"><?= e(money((float)$slip['net'])) ?></p>
        <p class="tiny" style="margin-top:6px"><?= $run && !empty($run['locked_at'])
            ? 'locked ' . e(short_datetime((string)$run['locked_at'])) : 'draft' ?></p>
      </div>
    </header>

    <div class="slip-grid">
      <div>
        <h3 style="margin:0 0 10px;font-size:15px">Earned</h3>
        <div class="slip-line"><span>Basic salary</span><b class="mono"><?= e(money((float)$slip['basic'])) ?></b></div>
        <div class="slip-line"><span>Housing allowance</span><b class="mono"><?= e(money((float)$slip['housing'])) ?></b></div>
        <div class="slip-line"><span>Transport allowance</span><b class="mono"><?= e(money((float)$slip['transport'])) ?></b></div>
        <div class="slip-line"><span>Other taxable allowance</span><b class="mono"><?= e(money((float)$slip['other'])) ?></b></div>
        <div class="slip-line"><span><b>Gross pay</b></span><b class="mono"><?= e(money((float)$slip['gross'])) ?></b></div>

        <h3 style="margin:24px 0 10px;font-size:15px">Deducted</h3>
        <div class="slip-line"><span>PAYE income tax <span class="muted">(<?= e(rtrim(rtrim(number_format((float)$slip['marginal_rate'], 2), '0'), '.')) ?>% top slice)</span></span>
          <b class="mono">−<?= e(money((float)$slip['paye'])) ?></b></div>
        <div class="slip-line"><span>Pension <span class="muted">(<?= e(number_format((float)$slip['gross'] ? (float)$slip['pension'] / (float)$slip['gross'] * 100 : 0, 1)) ?>% of gross)</span></span>
          <b class="mono">−<?= e(money((float)$slip['pension'])) ?></b></div>
        <div class="slip-line"><span>National Housing Fund</span><b class="mono">−<?= e(money((float)$slip['nhf'])) ?></b></div>
        <div class="slip-line"><span>Total deductions</span><b class="mono">−<?= e(money($deductions)) ?></b></div>
      </div>

      <div>
        <h3 style="margin:0 0 10px;font-size:15px">Before the tax was worked out</h3>
        <dl class="kv">
          <dt>Annual gross</dt><dd><?= e(money((float)$slip['annual_gross'])) ?></dd>
          <dt>Consolidated relief</dt><dd>−<?= e(money((float)$slip['cra'])) ?></dd>
          <dt>Rent relief</dt><dd><?= (float)$slip['rent_relief'] > 0 ? '−' . e(money((float)$slip['rent_relief'])) : 'not claimed' ?></dd>
          <dt>Taxable pay, annual</dt><dd><?= e(money((float)$slip['taxable_annual'])) ?></dd>
          <dt>Taxable pay, monthly</dt><dd><?= e(money((float)$slip['taxable_monthly'])) ?></dd>
          <dt>Annual PAYE</dt><dd><?= e(money((float)$slip['paye'] * 12)) ?></dd>
        </dl>

        <h3 style="margin:24px 0 10px;font-size:15px">What the employer pays on top</h3>
        <dl class="kv">
          <dt>Employer pension</dt><dd><?= e(money((float)$slip['employer_pension'])) ?></dd>
          <dt>Employer NHF</dt><dd><?= e(money((float)$slip['employer_nhf'])) ?></dd>
          <dt>Total cost of this role</dt><dd><?= e(money((float)$slip['employer_cost'])) ?></dd>
        </dl>
        <p class="tiny">These never come out of your net pay. They are shown because a payslip that hides the
          employer's side leaves you unable to check the pension fund balance later.</p>
      </div>
    </div>

    <div class="slip-total">
      <span>Net pay for <?= e(Desk::periodLabel((string)$slip['period'])) ?></span>
      <b><?= e(money((float)$slip['net'])) ?></b>
    </div>

    <div style="margin-top:26px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:26px">
      <div>
        <h3 style="margin:0 0 10px;font-size:15px">The rate table this slip was built with</h3>
        <ul class="steps">
          <?php foreach ($banded as $band): ?>
            <li><span><?= e($band['to'] === null
              ? 'above ' . number_format($band['from'])
              : number_format($band['from']) . ' – ' . number_format($band['to'])) ?></span>
              <b><?= e(rtrim(rtrim(number_format($band['rate'], 2), '0'), '.')) ?>%</b></li>
          <?php endforeach; ?>
        </ul>
        <p class="tiny">Stored on the slip itself. If the table moves next month, this slip still shows these rates.</p>
      </div>
      <?php if ($rules): ?>
        <div>
          <h3 style="margin:0 0 10px;font-size:15px">Relief constants in force</h3>
          <dl class="kv">
            <?php foreach ($rules as $code => $value): ?>
              <dt><span class="mono"><?= e((string)$code) ?></span></dt>
              <dd><?= e(rtrim(rtrim(number_format((float)$value, 2), '0'), '.')) ?></dd>
            <?php endforeach; ?>
          </dl>
        </div>
      <?php endif; ?>
    </div>
  </article>

  <p class="tiny" style="margin-top:18px">Something wrong here?
    <a href="<?= e(url('/profile')) ?>">Leave a query on your package page</a> — the payroll officer sees it against
    your roster row. A corrected run replaces this slip with a new one.</p>
</section>
