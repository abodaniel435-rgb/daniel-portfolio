<?php declare(strict_types=1); ?>
<p class="eyebrow"><?= e($company['name'] ?? 'Workspace') ?> · frozen run</p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)"><?= e($run['period']) ?></h1>
<p class="lede" style="max-width:64ch">These figures were computed the moment the run was created and stored line by line. Changing a statutory rate afterwards will not move anything on this page.</p>

<div class="stat-row" style="margin-top:22px">
  <div class="stat"><b><?= e(money($run['gross_total'])) ?></b><span>Gross / month</span></div>
  <div class="stat"><b><?= e(money($run['paye_total'])) ?></b><span>PAYE to remit</span></div>
  <div class="stat"><b><?= e(money($run['net_total'])) ?></b><span>Net to staff</span></div>
  <div class="stat"><b><?= e(money($run['employer_cost_total'])) ?></b><span>Employer cost</span></div>
</div>

<div class="table-wrap" style="margin-top:26px">
  <table>
    <thead>
      <tr>
        <th>Staff</th><th class="n">Gross</th><th class="n">Taxable / yr</th><th class="n">PAYE</th>
        <th class="n">Pension</th><th class="n">NHF</th><th class="n">Marginal</th><th class="n">Net</th><th class="n">Employer cost</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($lines as $line): ?>
      <tr>
        <td>
          <strong><?= e($line['full_name'] ?? 'Removed from roster') ?></strong>
          <?php if (($line['staff_no'] ?? '') !== ''): ?><span style="color:var(--ink-3)"> · <?= e($line['staff_no']) ?></span><?php endif; ?>
        </td>
        <td class="n"><?= e(money($line['gross'])) ?></td>
        <td class="n"><?= e(money($line['taxable_annual'])) ?></td>
        <td class="n"><?= e(money($line['paye_monthly'])) ?></td>
        <td class="n"><?= e(money($line['pension_monthly'])) ?></td>
        <td class="n"><?= e(money($line['nhf_monthly'])) ?></td>
        <td class="n"><?= e(rtrim(rtrim(number_format((float)$line['marginal_rate'], 2, '.', ''), '0'), '.')) ?>%</td>
        <td class="n"><strong><?= e(money($line['net'])) ?></strong></td>
        <td class="n"><?= e(money($line['employer_cost'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$lines): ?><tr><td colspan="9">Every member of this run was removed from the roster afterwards.</td></tr><?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td><?= (int)$run['headcount'] ?> paid · created <?= e(short_datetime($run['created_at'])) ?></td>
        <td class="n"><?= e(money($run['gross_total'])) ?></td>
        <td class="n"></td>
        <td class="n"><?= e(money($run['paye_total'])) ?></td>
        <td class="n"><?= e(money($run['pension_total'])) ?></td>
        <td class="n"><?= e(money($run['nhf_total'])) ?></td>
        <td class="n"></td>
        <td class="n"><?= e(money($run['net_total'])) ?></td>
        <td class="n"><?= e(money($run['employer_cost_total'])) ?></td>
      </tr>
    </tfoot>
  </table>
</div>

<div class="split-page" style="margin-top:26px">
  <div class="panel">
    <h3>Remittance split</h3>
    <div class="readout-row"><span>Pension (employee + employer)</span><b><?= e(money($run['pension_total'])) ?></b></div>
    <div class="readout-row"><span>National Housing Fund</span><b><?= e(money($run['nhf_total'])) ?></b></div>
    <div class="readout-row"><span>PAYE to the state board</span><b><?= e(money($run['paye_total'])) ?></b></div>
    <div class="readout-row"><span>Cash out of the account</span><b><?= e(money($run['employer_cost_total'])) ?></b></div>
  </div>
  <div class="panel">
    <h3>What happens next</h3>
    <p style="font-size:.9rem;color:var(--ink-3)">Pay the net column into staff accounts, remit the PAYE and the two contributions by the statutory deadline, then keep this page as the record. Deleting the run removes every frozen line with it.</p>
    <form method="post" action="<?= e(url('/runs/' . $run['id'] . '/delete')) ?>" class="inlineform" style="margin-top:14px"
          onsubmit="return confirm('Delete the <?= e($run['period']) ?> run and every line in it?')">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$run['id'] ?>">
      <button class="btn btn-ghost btn-sm" type="submit">Delete this run</button>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/runs')) ?>">Back to runs</a>
    </form>
  </div>
</div>
