<?php
declare(strict_types=1);
/** @var array $employee @var array $slips @var array $totals */
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">My payslips</p>
      <h1><?= e((string)$employee['name']) ?></h1>
      <p class="lede">Staff number <span class="mono"><?= e((string)$employee['staff_no']) ?></span> ·
        <?= e((string)($employee['role_title'] ?: 'no title recorded')) ?> ·
        <?= e((string)($employee['department'] ?: 'General')) ?></p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/profile')) ?>">My package</a>
  </div>

  <div class="grid-3" style="margin-bottom:26px">
    <div class="stat"><b><?= e(money((float)$totals['net'])) ?></b><span>net pay, shown slips</span></div>
    <div class="stat stat-mint"><b><?= e(money((float)$totals['gross'])) ?></b><span>gross</span></div>
    <div class="stat stat-red"><b><?= e(money((float)$totals['paye'])) ?></b><span>PAYE deducted</span></div>
  </div>

  <?php if (!$slips): ?>
    <div class="verdict warn">
      <h4>No locked payslip yet</h4>
      <p>A slip appears here the moment the payroll officer locks a run that includes you. A draft run is not
        published — that is what the lock is for.</p>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Period</th><th class="num">Gross</th><th class="num">PAYE</th><th class="num">Pension</th>
          <th class="num">NHF</th><th class="num">Net</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($slips as $slip): ?>
          <tr>
            <td><b><?= e(Desk::periodLabel((string)$slip['period'])) ?></b>
              <div class="tiny">issued <?= e(short_date((string)$slip['created_at'])) ?></div></td>
            <td class="num"><?= e(money((float)$slip['gross'])) ?></td>
            <td class="num"><?= e(money((float)$slip['paye'])) ?></td>
            <td class="num"><?= e(money((float)$slip['pension'])) ?></td>
            <td class="num"><?= e(money((float)$slip['nhf'])) ?></td>
            <td class="num"><b><?= e(money((float)$slip['net'])) ?></b></td>
            <td><a class="btn btn-sm btn-ghost" href="<?= e(url('/payslips/' . (int)$slip['id'])) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="tiny">Each slip is a snapshot: it stores the band table and relief constants that were in force when
      it was built, so opening an old one still shows the arithmetic that produced it.</p>
  <?php endif; ?>
</section>
