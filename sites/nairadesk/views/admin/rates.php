<?php declare(strict_types=1); ?>
<p class="eyebrow">Administrator</p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)">Statutory rates</h1>
<p class="lede" style="max-width:64ch">The engine reads its numbers from this table, not from the source file. Change a figure, save, and the next calculation — and the next pay run — uses it. Runs that were already created keep the figures they were frozen with.</p>

<form method="post" action="<?= e(url('/admin/rates')) ?>" style="margin-top:26px">
  <?= csrf_field() ?>

  <div class="panel">
    <h3>PAYE bands</h3>
    <p style="font-size:.84rem;color:var(--ink-3);margin-bottom:16px">Taxable pay is sliced from the top: the first ₦x is taxed at 7%, the next at 11%, and so on. The sixth band has no ceiling — everything above band five is taxed there. Widths are annual ₦; rates are percentages.</p>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Figure</th><th>Code</th><th class="n">Current</th><th>New value</th><th>Last touched</th></tr></thead>
        <tbody>
        <?php foreach ($rates as $rate): ?>
          <?php /* DECIMAL(16,4) always arrives with four places, so format first
                 and then trim — trimming the raw string would strip the zeros
                 out of a whole figure like 200000. */ ?>
          <?php $shown = rtrim(rtrim(number_format((float)$rate['value'], 4, '.', ''), '0'), '.'); ?>
          <tr>
            <td><strong><?= e($rate['label']) ?></strong></td>
            <td><code><?= e($rate['code']) ?></code></td>
            <td class="n"><?= e($shown) ?><?= $rate['unit'] !== '' ? ' ' . e($rate['unit']) : '' ?></td>
            <td class="n" style="width:170px">
              <input name="rates[<?= e($rate['code']) ?>]" inputmode="decimal" aria-label="<?= e($rate['label']) ?>"
                     value="<?= e($shown) ?>">
            </td>
            <td style="font-size:.82rem;color:var(--ink-3)"><?= e(short_datetime($rate['updated_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p style="font-size:.8rem;color:var(--ink-3);margin-top:12px">Leave a figure alone and it is not written. Only values that actually differ are updated, and each change is recorded in the audit trail with your account as the actor.</p>
    <button class="btn btn-primary" type="submit" style="margin-top:16px">Save changes</button>
  </div>
</form>

<div class="panel" style="margin-top:22px">
  <h3>Why this is a table and not a constant</h3>
  <p style="font-size:.9rem;color:var(--ink-2)">A relief ceiling or a band width can move between one filing season and the next. Shipping that as PHP means a redeploy for a single number — and an old deployment quietly calculating last year's tax. Here the schedule is data, the calculator is code, and the two can change independently.</p>
</div>
