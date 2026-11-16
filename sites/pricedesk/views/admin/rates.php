<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $rates */
require BASE_PATH . '/views/admin/nav.php';
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Nothing in the application hard-codes a figure you see here</p>
      <h1>Haulage rates</h1>
      <p class="lede">Diesel moves, market levies get renegotiated, and a porter's price in Mile 12 is not
        his price in Bodija. Every comparison on this site reads these six numbers at request time, so the
        day fuel changes is an edit in a form rather than a redeploy — and the board's answers change on
        the next page load, not the next release.</p>
    </div>
  </div>

  <?php if ($rates === []): ?>
    <div class="panel">
      <h3>No rates stored yet</h3>
      <p style="margin:0">The engine is answering from its published defaults. Apply
        <span class="mono">database/seed.sql</span>, or add the six rows here, and this screen becomes the
        source of truth.</p>
    </div>
  <?php else: ?>
    <form method="post" action="<?= e(url('/admin/rates')) ?>">
      <?= csrf_field() ?>
      <div class="table-wrap">
        <table>
          <caption>Only the figures you actually change are written; untouched rows are skipped rather than
            re-saved, so the audit trail records a real edit.</caption>
          <thead>
            <tr>
              <th scope="col">Rate</th><th scope="col">Code</th><th scope="col" class="n">Value</th><th scope="col">Unit</th>
              <th scope="col">Last moved</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rates as $rate): ?>
              <tr>
                <td><strong><?= e((string)$rate['label']) ?></strong></td>
                <td class="mono tiny"><?= e((string)$rate['code']) ?></td>
                <td class="n"><input type="text" name="rates[<?= e((string)$rate['code']) ?>]"
                                     value="<?= e((string)(float)$rate['value']) ?>" inputmode="decimal"
                                     style="width:130px;text-align:right"></td>
                <td><?= e((string)$rate['unit']) ?></td>
                <td class="tiny"><?= e(short_datetime((string)$rate['updated_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p style="margin-top:16px"><button class="btn btn-primary" type="submit">Save the rates</button></p>
    </form>
  <?php endif; ?>

  <div class="panel" style="margin-top:26px">
    <h3>How each figure enters the arithmetic</h3>
    <div class="grid-2">
      <p class="tiny" style="margin:0"><span class="mono">fuel_per_km</span> is charged twice, there and back —
        a trip that only goes one way is not a trip, it is a relocation.</p>
      <p class="tiny" style="margin:0"><span class="mono">levy_flat</span> is per visit and
        <span class="mono">levy_per_km</span> scales with distance, because association dues and checkpoints
        are different beasts with different geometry.</p>
      <p class="tiny" style="margin:0"><span class="mono">porter_per_load</span> is charged once per trip, not
        per kilogram. On a small load it is the rate that quietly kills the deal, which is why the break-even
        figure exists.</p>
      <p class="tiny" style="margin:0"><span class="mono">spoilage_pct</span> reduces the <em>sellable</em>
        weight while the cost of arriving is unchanged, so it shows up as a higher cost per kilogram rather
        than as a line item.</p>
    </div>
    <p class="tiny" style="margin:14px 0 0"><span class="mono">vehicle_capacity_kg</span> sets what "a full
      load" means, and therefore whether the break-even is reachable in one trip or needs two. Every answer
      on the comparison page states its own assumptions in these exact terms.</p>
  </div>
</section>
