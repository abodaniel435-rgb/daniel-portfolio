<?php
declare(strict_types=1);
/** @var array $user @var ?array $employee @var ?array $projection @var array $rules */
$note = (string)($employee['query_note'] ?? '');
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">My package</p>
      <h1>What payroll holds for me</h1>
      <p class="lede">Signed in as <span class="mono"><?= e((string)$user['email']) ?></span>. This is the record the
        desk keeps for you — the same figures that build the payslip, before the month is locked.</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/payslips')) ?>">My payslips</a>
  </div>

  <?php if ($employee === null): ?>
    <div class="verdict warn">
      <h4>Your account is not linked to a staff record yet</h4>
      <p>Sign-up creates a login; an administrator points it at a row on the roster. Until that happens there is no
        package to show and no payslip will ever appear — which is the point of the link rather than trusting an
        email address typed into a form.</p>
    </div>
  <?php endif; ?>

  <div class="hero-grid">
    <form class="panel" method="post" action="<?= e(url('/profile')) ?>">
      <?= csrf_field() ?>
      <h3>My details and a query</h3>
      <div class="field">
        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="text" maxlength="40" inputmode="tel"
               value="<?= e((string)($user['phone'] ?? '')) ?>">
      </div>
      <div class="field">
        <label for="query_note">Query about my pay<span class="muted"> (up to 255 characters)</span></label>
        <textarea id="query_note" name="query_note" rows="4" maxlength="255"
                  placeholder="e.g. my transport allowance was reduced in April and I don't see it on the May slip."><?= e($note) ?></textarea>
      </div>
      <button class="btn btn-primary" type="submit">Save</button>
      <p class="tiny" style="margin-top:12px">The note appears against your row on the administrator's roster, and
        writing it is recorded in the audit trail with your user id and the time. Anything else on this record —
        allowances, rent, pension category — is the employer's to change, not yours.</p>
    </form>

    <div>
      <?php if ($employee !== null): ?>
        <div class="panel">
          <h3>Components on file</h3>
          <dl class="kv">
            <dt>Staff number</dt><dd><?= e((string)$employee['staff_no']) ?></dd>
            <dt>Role</dt><dd><?= e((string)($employee['role_title'] ?: '—')) ?></dd>
            <dt>Department</dt><dd><?= e((string)($employee['department'] ?: 'General')) ?></dd>
            <dt>Status</dt><dd><?= e((string)$employee['status']) ?></dd>
            <dt>Basic</dt><dd><?= e(money((float)$employee['basic'])) ?></dd>
            <dt>Housing</dt><dd><?= e(money((float)$employee['housing'])) ?></dd>
            <dt>Transport</dt><dd><?= e(money((float)$employee['transport'])) ?></dd>
            <dt>Other taxable</dt><dd><?= e(money((float)$employee['other'])) ?></dd>
            <dt>Annual rent on file</dt><dd><?= e(money((float)$employee['annual_rent'])) ?></dd>
            <dt>Rent relief claimed</dt><dd><?= (int)$employee['claim_rent'] === 1 ? 'yes' : 'no' ?></dd>
            <dt>Employee pension</dt><dd><?= e(rtrim(rtrim(number_format((float)$employee['pension_rate'], 2), '0'), '.')) ?>%</dd>
          </dl>
          <?php if (!empty($employee['joined_at'])): ?>
            <p class="tiny">On the payroll since <?= e(short_date((string)$employee['joined_at'])) ?>.</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($projection !== null && empty($projection['empty'])): ?>
        <div class="panel" style="margin-top:18px">
          <h3>Projected next payslip</h3>
          <p class="tiny" style="margin-top:0">Computed now, from your stored components and the band table currently
            in force. The locked slip can differ if a rate changed between now and the run.</p>
          <div class="readout" style="margin-top:14px">
            <div class="readout-big">
              <span>Projected net, monthly</span>
              <b><?= e(money((float)$projection['net'])) ?></b>
            </div>
            <div class="readout-row"><span>Gross</span><b><?= e(money((float)$projection['gross'])) ?></b></div>
            <div class="readout-row"><span>PAYE</span><b>−<?= e(money((float)$projection['paye'])) ?></b></div>
            <div class="readout-row"><span>Pension</span><b>−<?= e(money((float)$projection['pension'])) ?></b></div>
            <div class="readout-row"><span>NHF</span><b>−<?= e(money((float)$projection['nhf'])) ?></b></div>
            <div class="readout-row"><span>Consolidated relief, annual</span><b><?= e(money((float)$projection['cra'])) ?></b></div>
            <div class="readout-row"><span>Rent relief, annual</span><b><?= (float)$projection['rent_relief'] > 0 ? '−' . e(money((float)$projection['rent_relief'])) : 'not claimed' ?></b></div>
            <div class="readout-row"><span>Effective rate</span><b><?= e(number_format((float)$projection['effective_rate'], 2)) ?>%</b></div>
          </div>
          <?php if ((float)$projection['rent_relief'] === 0.0 && (int)($employee['claim_rent'] ?? 0) === 0): ?>
            <p class="tiny" style="margin-bottom:0">Rent relief is switched off on your record. If you pay rent and it
              is not being claimed, raise it in the query box — it is usually the single biggest change available to
              a payslip at this level.</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="card" style="margin-top:18px">
        <h3>The rules behind these figures</h3>
        <dl class="kv">
          <dt>Consolidated relief</dt><dd><?= e(rtrim(rtrim(number_format((float)$rules['cra_pct'], 0), '0'), '.')) ?>% of gross + <?= e(number_format((float)$rules['cra_base'])) ?> / yr</dd>
          <dt>Rent relief</dt><dd><?= e(rtrim(rtrim(number_format((float)$rules['rent_pct'], 0), '0'), '.')) ?>% of rent, max <?= e(number_format((float)$rules['rent_cap'])) ?> / yr</dd>
          <dt>Employer pension</dt><dd><?= e(rtrim(rtrim(number_format((float)$rules['employer_pension_pct'], 0), '0'), '.')) ?>% of gross</dd>
          <dt>NHF</dt><dd><?= e(rtrim(rtrim(number_format((float)$rules['nhf_pct'], 1), '0'), '.')) ?>% of gross, both sides</dd>
        </dl>
        <p class="tiny" style="margin-bottom:0"><a href="<?= e(url('/rates')) ?>">See the full PAYE band table</a>.</p>
      </div>
    </div>
  </div>
</section>
