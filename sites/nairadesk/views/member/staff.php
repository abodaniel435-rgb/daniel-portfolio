<?php declare(strict_types=1); ?>
<p class="eyebrow"><?= $company ? e($company['name']) : 'No workspace' ?></p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)">Staff</h1>
<?php if (!$company): ?>
  <div class="panel"><p>Create a workspace from the <a href="<?= e(url('/dashboard')) ?>">dashboard</a> first.</p></div>
<?php else: ?>
<div class="split-page" style="margin-top:24px">
  <div class="panel">
    <h3>Add a member of staff</h3>
    <form method="post" action="<?= e(url('/staff')) ?>">
      <?= csrf_field() ?>
      <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" required maxlength="160" value="<?= e(old('full_name')) ?>"></div>
      <div class="grid-2">
        <div class="field"><label for="staff_no">Staff no.</label><input id="staff_no" name="staff_no" maxlength="40" value="<?= e(old('staff_no')) ?>"></div>
        <div class="field"><label for="basic">Basic / month ₦</label><input id="basic" name="basic" inputmode="numeric" required value="<?= e(old('basic')) ?>"></div>
      </div>
      <div class="grid-3">
        <div class="field"><label for="housing">Housing ₦</label><input id="housing" name="housing" inputmode="numeric" value="<?= e(old('housing', '0')) ?>"></div>
        <div class="field"><label for="transport">Transport ₦</label><input id="transport" name="transport" inputmode="numeric" value="<?= e(old('transport', '0')) ?>"></div>
        <div class="field"><label for="other">Other ₦</label><input id="other" name="other" inputmode="numeric" value="<?= e(old('other', '0')) ?>"></div>
      </div>
      <label class="check"><input type="checkbox" name="use_pension" value="1" checked> Pension contribution</label>
      <div class="field"><label for="pension_rate">Employee pension %</label><input id="pension_rate" name="pension_rate" inputmode="decimal" value="8"></div>
      <label class="check"><input type="checkbox" name="use_nhf" value="1" checked> National Housing Fund (2.5%)</label>
      <label class="check"><input type="checkbox" name="use_rent" value="1"> Claim rent relief</label>
      <div class="field"><label for="annual_rent">Annual rent ₦</label><input id="annual_rent" name="annual_rent" inputmode="numeric" value="0"></div>
      <p class="err"><?= field_error('basic') ?><?= field_error('full_name') ?></p>
      <button class="btn btn-primary" type="submit">Add to roster</button>
    </form>
  </div>

  <div>
    <h2>The roster</h2>
    <p><?= count($employees) ?> people · <b><?= e(money($monthlyCost)) ?></b> total employer cost per month, computed server-side from the live statutory table.</p>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th class="n">Gross</th><th class="n">PAYE</th><th class="n">Net</th><th class="n">Marginal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($employees as $employee): $r = Payroll::compute(work_employee_input($employee), Desk::rates()); ?>
          <tr>
            <td><?= e($employee['full_name']) ?>
              <?php if ($employee['staff_no'] !== ''): ?><br><span style="font-family:var(--font-mono);font-size:.72rem;color:var(--ink-3)"><?= e($employee['staff_no']) ?></span><?php endif; ?></td>
            <td class="n"><?= e(money($r['gross'])) ?></td>
            <td class="n"><?= e(money($r['payeMonthly'])) ?></td>
            <td class="n"><?= e(money($r['net'])) ?></td>
            <td class="n"><?= e((string)$r['marginal']) ?>%</td>
            <td class="n">
              <a class="btn btn-ghost btn-sm" href="/calculate?employee=<?= (int)$employee['id'] ?>">Slip</a>
              <form method="post" action="/staff/<?= (int)$employee['id'] ?>/delete" class="inlineform"
                    onsubmit="return confirm('Remove <?= e($employee['full_name']) ?> from the roster?')">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$employee['id'] ?>">
                <button class="btn btn-ghost btn-sm" type="submit">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$employees): ?><tr><td colspan="6">Nobody yet — add your first person on the left.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
  </div>
</div>
<?php endif; ?>
