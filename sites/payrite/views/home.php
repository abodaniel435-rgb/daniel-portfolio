<?php
declare(strict_types=1);
/** @var array $package @var array $result @var array $verdict @var ?array $whatif @var array $bands @var array $rules */
$v = static fn (array $r, string $key): string => money((float)($r[$key] ?? 0));
$empty = !empty($result['empty']);
?>
<section class="hero">
  <div class="hero-grid">
    <div>
      <p class="eyebrow">Payroll for Nigerian small employers</p>
      <h1>The payslip, <em>worked in front of you.</em></h1>
      <p class="lede">Type a monthly package and PayRite applies the progressive PAYE bands, takes consolidated
        relief and rent relief off annual gross, deducts pension and NHF, and shows the net figure with every
        step that produced it. Nothing here is a JavaScript approximation — the same PHP engine writes the
        payslip a signed-in employee reads.</p>
      <div class="hero-stats">
        <div><b><?= number_format((float)$rules['cra_base']) ?></b><span>CRA fixed naira</span></div>
        <div><b><?= rtrim(rtrim(number_format((float)$rules['cra_pct'], 1), '0'), '.') ?>%</b><span>CRA of gross</span></div>
        <div><b><?= count($bands) ?></b><span>tax bands live</span></div>
        <div><b><?= number_format($asked) ?></b><span>calculations run</span></div>
      </div>
      <p class="tiny">Every figure on this page comes from the rate table in the database. An administrator can
        move a band without a redeploy, and a payslip that was already issued keeps the table it was computed
        with.</p>
    </div>

    <form class="panel" method="post" action="<?= e(url('/')) ?>">
      <?= csrf_field() ?>
      <h3>Build a payslip</h3>
      <p class="tiny" style="margin-top:2px">Leave the name blank — this calculator works without an account.</p>

      <div class="field">
        <label for="who">Whose pay is this? (optional)</label>
        <input id="who" name="who" type="text" maxlength="60" value="<?= e((string)($_POST['who'] ?? '')) ?>">
      </div>
      <div class="grid-2">
        <div class="field">
          <label for="basic">Basic salary / month</label>
          <input id="basic" name="basic" type="text" inputmode="numeric"
                 value="<?= e((string)round((float)$package['basic'])) ?>" <?= field_error('basic') ? 'aria-invalid="true"' : '' ?>>
          <?= field_error('basic') ?>
        </div>
        <div class="field">
          <label for="housing">Housing allowance</label>
          <input id="housing" name="housing" type="text" inputmode="numeric" value="<?= e((string)round((float)$package['housing'])) ?>">
        </div>
        <div class="field">
          <label for="transport">Transport allowance</label>
          <input id="transport" name="transport" type="text" inputmode="numeric" value="<?= e((string)round((float)$package['transport'])) ?>">
        </div>
        <div class="field">
          <label for="other">Other taxable allowance</label>
          <input id="other" name="other" type="text" inputmode="numeric" value="<?= e((string)round((float)$package['other'])) ?>">
        </div>
      </div>
      <div class="grid-2">
        <div class="field">
          <label for="annual_rent">Annual rent paid</label>
          <input id="annual_rent" name="annual_rent" type="text" inputmode="numeric" value="<?= e((string)round((float)$package['annual_rent'])) ?>">
        </div>
        <div class="field">
          <label for="pension_rate">Employee pension rate (%)</label>
          <select id="pension_rate" name="pension_rate">
            <?php foreach ([5, 8, 10, 12, 15, 20] as $option): ?>
              <option value="<?= $option ?>" <?= (float)$package['pension_rate'] === (float)$option ? 'selected' : '' ?>><?= $option ?>%</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <!-- Without the hidden 0 an unticked box posts nothing, and payrite_package()
           defaults a missing claim_rent to "claimed" — so the tick would be cosmetic. -->
      <input type="hidden" name="claim_rent" value="0">
      <label class="check"><input type="checkbox" name="claim_rent" value="1" <?= $package['claim_rent'] ? 'checked' : '' ?>>
        Claim rent relief on the rent above</label>
      <button class="btn btn-primary" type="submit">Work out the payslip</button>
      <?php if ($posted): ?><p class="tiny">Recomputed by PHP on the server — this run is logged in the
        <span class="mono">calculations</span> table.</p><?php endif; ?>
    </form>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <div>
      <p class="eyebrow">The answer</p>
      <h2>What reaches the account, and what does not</h2>
    </div>
    <p class="section-intro tiny">Net pay is gross less PAYE, employee pension and NHF. The employer's own
      pension and housing contributions sit on top of gross — they never touch the employee's figure.</p>
  </div>

  <?php if ($empty): ?>
    <div class="verdict warn"><h4>Nothing to compute yet</h4><p>Enter at least one monthly component and the
      payslip builds itself.</p></div>
  <?php else: ?>
    <div class="hero-grid">
      <div class="panel">
        <div class="readout">
          <div class="readout-big">
            <span>Net pay, monthly</span>
            <b><?= e(money((float)$result['net'])) ?></b>
          </div>
          <div class="readout-row"><span>Gross pay</span><b><?= e($v($result, 'gross')) ?></b></div>
          <div class="readout-row"><span>PAYE (<?= e(rtrim(rtrim(number_format((float)$result['marginal_rate'], 2), '0'), '.')) ?>% top slice)</span><b>−<?= e($v($result, 'paye')) ?></b></div>
          <div class="readout-row"><span>Employee pension (<?= e(rtrim(rtrim(number_format((float)$result['pension_rate'], 2), '0'), '.')) ?>%)</span><b>−<?= e($v($result, 'pension')) ?></b></div>
          <div class="readout-row"><span>National Housing Fund</span><b>−<?= e($v($result, 'nhf')) ?></b></div>
          <div class="readout-row"><span>Taxable pay, monthly</span><b><?= e($v($result, 'taxable_monthly')) ?></b></div>
          <div class="readout-row"><span>Employer cost per month</span><b><?= e($v($result, 'employer_cost')) ?></b></div>
          <div class="readout-row"><span>Effective tax rate</span><b><?= e(number_format((float)$result['effective_rate'], 2)) ?>%</b></div>
          <div class="readout-row"><span>Take-home share of gross</span><b><?= e(number_format((float)$result['take_home_pct'], 1)) ?>%</b></div>
          <div class="readout-row"><span>Annual net</span><b><?= e($v($result, 'annual_net')) ?></b></div>
        </div>
      </div>

      <div style="display:grid;gap:18px">
        <div class="verdict <?= e($verdict['tone']) ?>"><h4><?= e($verdict['title']) ?></h4><p><?= e($verdict['body']) ?></p></div>

        <?php if ($whatif): ?>
          <div class="card">
            <h3>If basic rose by ₦50,000</h3>
            <p class="tiny" style="margin-bottom:10px">The marginal rate is what makes a raise expensive: it is the
              rate on the top slice, not an average.</p>
            <div class="readout-row"><span>Net pay would be</span><b><?= e(money((float)$whatif['net'])) ?></b></div>
            <div class="readout-row"><span>PAYE would rise by</span><b><?= e(money((float)$whatif['paye'] - (float)$result['paye'])) ?></b></div>
            <div class="readout-row"><span>Employer cost would rise by</span><b><?= e(money((float)$whatif['employer_cost'] - (float)$result['employer_cost'])) ?></b></div>
          </div>
        <?php endif; ?>

        <div class="card">
          <h3>The PAYE slice by slice</h3>
          <p class="tiny" style="margin-bottom:12px">Taxable annual pay of <?= e(money((float)$result['taxable_annual'])) ?>
            taxed band by band, not at one flat rate.</p>
          <ul class="steps">
            <?php foreach ($result['steps'] as $step): ?>
              <li><span><?= e(money((float)$step['width'])) ?> at <?= e(rtrim(rtrim(number_format((float)$step['rate'], 2), '0'), '.')) ?>%</span>
                  <b><?= e(money((float)$step['tax'])) ?></b></li>
            <?php endforeach; ?>
            <li><span>Annual PAYE ÷ 12</span><b><?= e(money((float)$result['paye'])) ?> / month</b></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:26px">
      <h3>How this figure was built</h3>
      <ol class="steps" style="grid-template-columns:repeat(2,minmax(0,1fr));display:grid">
        <li><span>Gross</span><b><?= e(money((float)$package['basic'])) ?> + <?= e(money((float)$package['housing'])) ?> + <?= e(money((float)$package['transport'])) ?> + <?= e(money((float)$package['other'])) ?> = <?= e($v($result, 'gross')) ?></b></li>
        <li><span>Annual gross</span><b><?= e($v($result, 'annual_gross')) ?></b></li>
        <li><span>Consolidated relief (<?= e(rtrim(rtrim(number_format((float)$rules['cra_pct'], 0), '0'), '.')) ?>% + <?= e(number_format((float)$rules['cra_base'])) ?>)</span><b>−<?= e($v($result, 'cra')) ?> / yr</b></li>
        <li><span>Rent relief (<?= e(rtrim(rtrim(number_format((float)$rules['rent_pct'], 0), '0'), '.')) ?>% of rent, capped at <?= e(number_format((float)$rules['rent_cap'])) ?>)</span><b><?= $result['rent_relief'] > 0 ? '−' . e($v($result, 'rent_relief')) . ' / yr' : 'not claimed' ?></b></li>
        <li><span>Pension before tax</span><b>−<?= e($v($result, 'pension_annual')) ?> / yr</b></li>
        <li><span>Taxable pay</span><b><?= e($v($result, 'taxable_annual')) ?> / yr</b></li>
        <li><span>PAYE</span><b><?= e($v($result, 'paye_annual')) ?> / yr · <?= e($v($result, 'paye')) ?> / month</b></li>
        <li><span>Net</span><b><?= e($v($result, 'gross')) ?> − <?= e($v($result, 'paye')) ?> − <?= e($v($result, 'pension')) ?> − <?= e($v($result, 'nhf')) ?> = <?= e($v($result, 'net')) ?></b></li>
        <li><span>Employer side (pension <?= e(rtrim(rtrim(number_format((float)$rules['employer_pension_pct'], 0), '0'), '.')) ?>% + NHF <?= e(rtrim(rtrim(number_format((float)$rules['nhf_pct'], 1), '0'), '.')) ?>%)</span><b><?= e($v($result, 'employer_cost')) ?> / month</b></li>
      </ol>
    </div>
  <?php endif; ?>
</section>

<section class="section reveal">
  <div class="section-head">
    <div>
      <p class="eyebrow">The published table</p>
      <h2>Where each naira goes</h2>
    </div>
    <p class="tiny">Six slices, applied in order. <a href="<?= e(url('/rates')) ?>">See the bands and reliefs →</a></p>
  </div>
  <table class="table">
    <thead><tr><th>Package</th><th class="num">Taxable / yr</th><th class="num">PAYE</th><th class="num">Pension + NHF</th><th class="num">Net pay</th><th class="num">Take home</th><th class="num">Employer cost</th></tr></thead>
    <tbody>
      <?php foreach ($ladder as $row): ?>
        <tr>
          <td><b><?= e((string)$row['label']) ?></b> gross</td>
          <td class="num"><?= e(money((float)$row['taxable_annual'])) ?></td>
          <td class="num"><?= e(money((float)$row['paye'])) ?></td>
          <td class="num"><?= e(money((float)$row['pension'] + (float)$row['nhf'])) ?></td>
          <td class="num"><b><?= e(money((float)$row['net'])) ?></b></td>
          <td class="num"><?= e(number_format((float)$row['take_home_pct'], 1)) ?>%</td>
          <td class="num"><?= e(money((float)$row['employer_cost'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p class="tiny">Each row is computed on request by the same engine that writes payslips, so this table cannot
    drift away from what a payroll run would produce.</p>
</section>

<section class="section reveal">
  <div class="section-head">
    <div>
      <p class="eyebrow">From calculator to payroll system</p>
      <h2>What the back end adds</h2>
    </div>
  </div>
  <div class="grid-3">
    <div class="card">
      <h3>A roster, not a spreadsheet</h3>
      <p class="tiny">Eight people, their allowances, rent, pension category and status, with a staff number that
        stays unique. Change a package and the next run uses it; the runs already locked do not.</p>
    </div>
    <div class="card">
      <h3>One click, one month</h3>
      <p class="tiny">A payroll run builds every payslip in a single database transaction, totals the month, and
        refuses a second run for the same period. Lock it when it is right; void it and it disappears from
        everyone's list without deleting the audit trail.</p>
    </div>
    <div class="card">
      <h3>Employees see only their own</h3>
      <p class="tiny">A staff account is created by self-registration and linked to a roster row by the
        administrator. Every payslip query is joined through that link, so another person's slip is a 404 rather
        than a guess.</p>
    </div>
    <div class="card">
      <h3>Rates the accountant can edit</h3>
      <p class="tiny">Bands and relief constants are rows in MySQL with a who-and-when stamp. When the budget
        changes, that is an edit on one page — not a code change and not a redeploy.</p>
    </div>
    <div class="card">
      <h3>A slip that explains itself</h3>
      <p class="tiny">Each payslip stores the band table and the relief constants it was computed with, so a slip
        printed in December still shows the arithmetic behind it after the rates move.</p>
    </div>
    <div class="card">
      <h3>An honest trail</h3>
      <p class="tiny">Logins, roster edits, rate changes, runs, locks, voids and notifications all land in
        <span class="mono">audit_log</span> with the actor and the values involved.</p>
    </div>
  </div>
  <p style="margin-top:26px;display:flex;gap:14px;flex-wrap:wrap">
    <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Sign in to the desk</a>
    <a class="btn btn-ghost" href="<?= e(url('/register')) ?>">Create a staff account</a>
    <a class="btn btn-ghost" href="<?= e(url('/api/paye')) ?>?gross=450000&amp;rent=2400000">Check our figures with the JSON API</a>
  </p>
</section>
