<?php declare(strict_types=1); ?>
<section class="hero">
  <div class="hero-grid">
    <div>
      <p class="eyebrow">PHP 8 · MySQL · runs on cPanel</p>
      <h1>Payroll is arithmetic.<br><em>The record behind it is the product.</em></h1>
      <p class="lede">NairaDesk works out PAYE on the server — progressive bands, Consolidated Relief Allowance, rent relief, pension and the Housing Fund — then keeps every run as a row you can produce to a labour officer, an auditor or a departing employee two years from now.</p>
      <p class="lede">The statutory figures live in a database table, not in the code. When a state moves a number, an administrator edits one field and the next run is right.</p>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:22px">
        <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Open a workspace</a>
        <a class="btn btn-ghost" href="<?= e(url('/calculate')) ?>">Run one calculation</a>
      </div>
    </div>
    <div class="panel">
      <p class="eyebrow" style="margin-bottom:10px">Worked example</p>
      <h3 style="font-size:1rem"><?= e(money($example['gross'])) ?> gross a month, rent relief claimed</h3>
      <div class="readout-row"><span>Annual gross</span><b><?= e(money($example['annualGross'])) ?></b></div>
      <div class="readout-row"><span>CRA + rent relief</span><b><?= e(money($example['cra'] + $example['rentRelief'])) ?></b></div>
      <div class="readout-row"><span>Taxable</span><b><?= e(money($example['taxable'])) ?></b></div>
      <div class="readout-row"><span>PAYE / month</span><b><?= e(money($example['payeMonthly'])) ?></b></div>
      <div class="readout-row" style="border-bottom:0"><span>Net / month</span><b><?= e(money($example['net'])) ?></b></div>
      <p style="font-size:.78rem;color:var(--ink-3);margin:14px 0 0">Recomputed live by <code>Payroll::compute()</code> against the bands in the table below.</p>
    </div>
  </div>
</section>

<section id="how">
  <h2>What the server actually does</h2>
  <div class="grid-3">
    <div class="card"><h3>1 · Bands, not a rate</h3><p>Taxable pay is sliced across the bands and each slice taxed at its own rate. The engine returns the marginal rate and every slice, so the payslip can show the working rather than a number nobody can check.</p></div>
    <div class="card"><h3>2 · Reliefs in order</h3><p>CRA is 20% of gross plus ₦200,000. Rent relief is 20% of annual rent capped at ₦6,250,000. Pension comes off before tax; NHF comes off after. Get the order wrong and the figure is wrong.</p></div>
    <div class="card"><h3>3 · Stored, not displayed</h3><p>Every calculation writes its inputs and full result to MySQL. A pay run freezes the lines it produced, so reopening last March shows what was paid in March.</p></div>
  </div>
</section>

<section id="rates" style="margin-top:44px">
  <h2>The statutory table</h2>
  <p>Read from the <code>rates</code> table at run time. An administrator can change any of these without redeploying.</p>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Band</th><th class="n">Width / year</th><th class="n">Rate</th><th class="n">Cumulative</th></tr></thead>
      <tbody>
      <?php $cum = 0; foreach ($bands as $i => $band): $finite = is_finite($band['width']); $cum += $finite ? $band['width'] : 0; ?>
        <tr>
          <td>Band <?= $i + 1 ?></td>
          <td class="n"><?= $finite ? e(money($band['width'])) : 'and above' ?></td>
          <td class="n"><?= e((string)$band['rate']) ?>%</td>
          <td class="n"><?= $finite ? e(money($cum)) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p style="margin-top:18px"><a class="btn btn-ghost" href="<?= e(url('/how')) ?>">See how a payslip is built →</a></p>
</section>
