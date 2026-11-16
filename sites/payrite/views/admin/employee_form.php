<?php
declare(strict_types=1);
/** @var array $employee @var bool $creating @var ?array $preview @var ?array $linked */
require BASE_PATH . '/views/admin/nav.php';
$f = static fn (string $key, mixed $default = ''): string => (string)old($key, $default ?? '');
$claim = in_array((string)old('claim_rent', (string)(int)($employee['claim_rent'] ?? 1)), ['1', 'on', 'yes', 'true'], true);
$status = (string)old('status', (string)($employee['status'] ?? 'active'));
$pension = (string)old('pension_rate', rtrim(rtrim(number_format((float)($employee['pension_rate'] ?? 8), 2), '0'), '.'));
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow"><?= $creating ? 'New roster row' : 'Editing' ?></p>
      <h1><?= $creating ? 'Add someone to the payroll' : e((string)$employee['name']) ?></h1>
      <p class="lede">The four components are all taxable pay — an allowance that is genuinely not taxable belongs in
        a note, not in this form, because PAYE is worked out on the total below.</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/admin/employees')) ?>">Back to roster</a>
  </div>

  <div class="hero-grid">
    <form class="panel" method="post"
          action="<?= e(url($creating ? '/admin/employees/new' : '/admin/employees/' . (int)$employee['id'] . '/edit')) ?>">
      <?= csrf_field() ?>
      <div class="grid-2">
        <div class="field">
          <label for="staff_no">Staff number</label>
          <input id="staff_no" name="staff_no" type="text" required maxlength="24" value="<?= e($f('staff_no', (string)($employee['staff_no'] ?? ''))) ?>">
          <?= field_error('staff_no') ?>
          <p class="tiny" style="margin-top:6px">Unique. Uppercase letters, numbers and dashes.</p>
        </div>
        <div class="field">
          <label for="name">Full name</label>
          <input id="name" name="name" type="text" required maxlength="120" value="<?= e($f('name', (string)($employee['name'] ?? ''))) ?>">
          <?= field_error('name') ?>
        </div>
        <div class="field">
          <label for="role_title">Role</label>
          <input id="role_title" name="role_title" type="text" maxlength="120" value="<?= e($f('role_title', (string)($employee['role_title'] ?? ''))) ?>">
        </div>
        <div class="field">
          <label for="department">Department</label>
          <input id="department" name="department" type="text" maxlength="80" value="<?= e($f('department', (string)($employee['department'] ?? 'General'))) ?>">
        </div>
      </div>

      <h3 style="margin:20px 0 10px;font-size:15px">Monthly package</h3>
      <div class="grid-2">
        <div class="field">
          <label for="basic">Basic salary</label>
          <input id="basic" name="basic" type="text" inputmode="numeric" value="<?= e($f('basic', rtrim((string)round((float)($employee['basic'] ?? 0)), '.'))) ?>">
          <?= field_error('basic') ?>
        </div>
        <div class="field">
          <label for="housing">Housing allowance</label>
          <input id="housing" name="housing" type="text" inputmode="numeric" value="<?= e($f('housing', rtrim((string)round((float)($employee['housing'] ?? 0)), '.'))) ?>">
        </div>
        <div class="field">
          <label for="transport">Transport allowance</label>
          <input id="transport" name="transport" type="text" inputmode="numeric" value="<?= e($f('transport', rtrim((string)round((float)($employee['transport'] ?? 0)), '.'))) ?>">
        </div>
        <div class="field">
          <label for="other">Other taxable allowance</label>
          <input id="other" name="other" type="text" inputmode="numeric" value="<?= e($f('other', rtrim((string)round((float)($employee['other'] ?? 0)), '.'))) ?>">
        </div>
      </div>

      <div class="grid-2" style="margin-top:6px">
        <div class="field">
          <label for="annual_rent">Annual rent paid</label>
          <input id="annual_rent" name="annual_rent" type="text" inputmode="numeric" value="<?= e($f('annual_rent', rtrim((string)round((float)($employee['annual_rent'] ?? 0)), '.'))) ?>">
          <p class="tiny" style="margin-top:6px">Drives rent relief: <?= e(rtrim(rtrim(number_format(Desk::rules()['rent_pct'], 0), '0'), '.')) ?>% of it, up to the cap.</p>
        </div>
        <div class="field">
          <label for="pension_rate">Employee pension rate (%)</label>
          <select id="pension_rate" name="pension_rate">
            <?php foreach ([0, 5, 8, 10, 12, 15, 20] as $option): ?>
              <option value="<?= $option ?>" <?= $pension === (string)$option || (float)$pension === (float)$option ? 'selected' : '' ?>><?= $option ?>%</option>
            <?php endforeach; ?>
          </select>
          <?= field_error('pension_rate') ?>
        </div>
      </div>

      <!-- The hidden field means "unticked" actually reaches the server, so a
           failed save cannot silently re-check relief the officer just removed. -->
      <input type="hidden" name="claim_rent" value="0">
      <label class="check"><input type="checkbox" name="claim_rent" value="1" <?= $claim ? 'checked' : '' ?>>
        Claim rent relief for this person</label>
      <?php if ($claim): ?><p class="tiny" style="margin-top:4px">Uncheck it if the accommodation is provided by the
        business — claiming relief on rent nobody paid is how an audit starts.</p><?php endif; ?>

      <div class="grid-2" style="margin-top:14px">
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <?php foreach (['active' => 'Active — paid in every run', 'paused' => 'Paused — kept, not paid', 'left' => 'Left — off the payroll'] as $key => $label): ?>
              <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <?= field_error('status') ?>
        </div>
        <div class="field">
          <label for="joined_at">Joined</label>
          <input id="joined_at" name="joined_at" type="date" value="<?= e($f('joined_at', (string)($employee['joined_at'] ?? ''))) ?>">
        </div>
      </div>

      <button class="btn btn-primary" type="submit"><?= $creating ? 'Add to roster' : 'Save package' ?></button>
    </form>

    <div>
      <?php if ($preview !== null && empty($preview['empty'])): ?>
        <div class="panel">
          <h3>What this produces</h3>
          <p class="tiny" style="margin-top:0">Recomputed from the stored row against the band table in force right now.</p>
          <div class="readout" style="margin-top:14px">
            <div class="readout-big">
              <span>Net pay, monthly</span>
              <b><?= e(money((float)$preview['net'])) ?></b>
            </div>
            <div class="readout-row"><span>Gross</span><b><?= e(money((float)$preview['gross'])) ?></b></div>
            <div class="readout-row"><span>Taxable pay, annual</span><b><?= e(money((float)$preview['taxable_annual'])) ?></b></div>
            <div class="readout-row"><span>Consolidated relief</span><b>−<?= e(money((float)$preview['cra'])) ?>/yr</b></div>
            <div class="readout-row"><span>Rent relief</span><b><?= (float)$preview['rent_relief'] > 0 ? '−' . e(money((float)$preview['rent_relief'])) . '/yr' : 'not claimed' ?></b></div>
            <div class="readout-row"><span>PAYE <span class="muted">(<?= e(rtrim(rtrim(number_format((float)$preview['marginal_rate'], 2), '0'), '.')) ?>% top slice)</span></span><b>−<?= e(money((float)$preview['paye'])) ?></b></div>
            <div class="readout-row"><span>Pension + NHF</span><b>−<?= e(money((float)$preview['pension'] + (float)$preview['nhf'])) ?></b></div>
            <div class="readout-row"><span>Employer cost</span><b><?= e(money((float)$preview['employer_cost'])) ?></b></div>
          </div>
          <ul class="steps" style="margin-top:16px">
            <?php foreach ($preview['steps'] as $step): ?>
              <li><span><?= e(money((float)$step['width'])) ?> at <?= e(rtrim(rtrim(number_format((float)$step['rate'], 2), '0'), '.')) ?>%</span>
                <b><?= e(money((float)$step['tax'])) ?></b></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php elseif (!$creating): ?>
        <div class="verdict warn">
          <h4>This package adds up to nothing</h4>
          <p>All four components are zero, so a run will skip the row rather than payslip ₦0. Enter the basic salary.</p>
        </div>
      <?php endif; ?>

      <?php if (!$creating): ?>
        <div class="panel" style="margin-top:18px">
          <h3>Sign-in link</h3>
          <?php if ($linked !== null): ?>
            <dl class="kv">
              <dt>Name</dt><dd><?= e((string)$linked['name']) ?></dd>
              <dt>Email</dt><dd><?= e((string)$linked['email']) ?></dd>
              <dt>Account</dt><dd><span class="badge badge-<?= (int)$linked['is_active'] === 1 ? 'ok' : 'bad' ?>"><?= (int)$linked['is_active'] === 1 ? 'active' : 'disabled' ?></span></dd>
            </dl>
            <p class="tiny" style="margin-bottom:0">This person sees their own locked slips at
              <span class="mono">/payslips</span>. Change the link under <a href="<?= e(url('/admin/staff')) ?>">Staff logins</a>.</p>
          <?php else: ?>
            <p class="tiny" style="margin:0">No account is linked, so nobody can read these payslips but you. Once the
              person registers with their work email, link it under
              <a href="<?= e(url('/admin/staff')) ?>">Staff logins</a>.</p>
          <?php endif; ?>
        </div>

        <?php if ((string)($employee['query_note'] ?? '') !== ''): ?>
          <div class="verdict info" style="margin-top:18px">
            <h4>Query from this employee</h4>
            <p><?= e((string)$employee['query_note']) ?></p>
          </div>
        <?php endif; ?>

        <div class="card" style="margin-top:18px">
          <h3>Deleting is not an option here</h3>
          <p class="tiny">Set a leaver to <span class="mono">left</span> instead of removing the row: the payslips they
            were issued in past runs point at it, and the year's PAYE totals stop adding up if it goes. This screen
            offers no delete for exactly that reason.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
