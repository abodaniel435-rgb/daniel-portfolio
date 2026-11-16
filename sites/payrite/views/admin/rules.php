<?php
declare(strict_types=1);
/** @var array $bands @var array $rates @var array $ladder */
require BASE_PATH . '/views/admin/nav.php';
$ceil = 0.0;
$lastIndex = count($bands) - 1;
// Remembered input after a rejected save, so a typo does not mean retyping the
// whole table. The database is untouched until every row validates.
$kept = errors();
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Rates</p>
      <h1>The table the engine reads</h1>
      <p class="lede">Bands and relief constants are rows in MySQL, not numbers inside a PHP file. When the budget
        changes, this is the page that changes — and every payslip built afterwards uses the new figures while the
        slips already issued keep the copy stored on them.</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/rates')) ?>">Public view</a>
  </div>

  <?php if ($kept): ?>
    <div class="verdict bad" style="margin-bottom:20px">
      <h4>Nothing was saved</h4>
      <p><?= e((string)reset($kept)) ?> The table below still holds the figures that were in force, and every
        calculation in between used those.</p>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/admin/rules')) ?>">
    <?= csrf_field() ?>
    <div class="panel" style="margin-bottom:22px">
      <h3>PAYE bands</h3>
      <p class="tiny" style="margin-top:0">Width is the size of the slice, not where it starts. The final row must be
        left blank — that means "everything above", and without it a top earner falls off the table untaxed.</p>
      <table class="table">
        <thead>
          <tr><th>Order</th><th>Applies to</th><th class="num">Slice width</th><th class="num">Rate %</th><th>Last changed</th></tr>
        </thead>
        <tbody>
          <?php foreach ($bands as $i => $band): ?>
            <?php $width = $band['slice_width'] === null ? null : (float)$band['slice_width']; ?>
            <tr>
              <td><input class="mono" type="number" name="band_order[]" value="<?= (int)$band['band_order'] ?>" min="1" max="99" style="width:78px"></td>
              <td class="tiny"><?= e($width === null
                  ? 'everything above ' . number_format($ceil)
                  : number_format($ceil) . ' – ' . number_format($ceil + $width)) ?></td>
              <td class="num">
                <input type="hidden" name="band_id[]" value="<?= (int)$band['id'] ?>">
                <input class="mono" type="text" inputmode="numeric" name="slice_width[]" style="width:130px"
                       value="<?= $width === null ? '' : e((string)round($width)) ?>" <?= $i === $lastIndex ? 'placeholder="blank = above"' : '' ?>>
              </td>
              <td class="num"><input class="mono" type="number" step="0.01" min="0" max="60" name="rate[]" value="<?= e((string)(float)$band['rate']) ?>" style="width:86px"></td>
              <td class="tiny"><?= !empty($band['updated_at']) ? e(short_date((string)$band['updated_at'])) : '—' ?></td>
            </tr>
            <?php if ($width !== null) { $ceil += $width; } ?>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="tiny">A rate above 60% or a width of zero is rejected on save. Reordering by the order column is fine —
        the engine walks the rows in that order, and forces the last slice open-ended whatever is typed.</p>
    </div>

    <div class="panel" style="margin-bottom:22px">
      <h3>Reliefs and statutory percentages</h3>
      <table class="table">
        <thead><tr><th>Code</th><th>What it does</th><th class="num">Value</th><th>Unit</th></tr></thead>
        <tbody>
          <?php
          $blurb = [
              'cra_base' => 'Fixed part of the consolidated relief, added to the percentage.',
              'cra_pct' => 'Consolidated relief as a share of annual gross.',
              'rent_pct' => 'Share of annual rent allowed as relief.',
              'rent_cap' => 'Most rent relief can ever be, however much rent is paid.',
              'pension_pct' => 'Employee pension used when a roster row sets none.',
              'employer_pension_pct' => 'Employer pension, on top of gross.',
              'nhf_pct' => 'National Housing Fund, taken from employee and employer alike.',
          ];
          ?>
          <?php foreach ($rates as $rate): ?>
            <tr>
              <td><span class="mono"><?= e((string)$rate['code']) ?></span></td>
              <td class="tiny"><?= e($blurb[(string)$rate['code']] ?? (string)$rate['label']) ?></td>
              <td class="num"><input class="mono" type="number" step="0.01" min="0" name="rule[<?= e((string)$rate['code']) ?>]"
                                     value="<?= e((string)(float)$rate['value']) ?>" style="width:130px"></td>
              <td class="tiny"><?= e((string)$rate['unit']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="tiny">Only these seven codes are read. A negative value, or a code that is not on the list, is ignored
        or rejected — the engine falls back to its published defaults rather than computing on garbage.</p>
    </div>

    <button class="btn btn-primary" type="submit">Save the table</button>
    <p class="tiny" style="margin-top:10px">Saving is one transaction: bands and constants either all move together
      or none of them do, and the write is recorded in the audit trail with your user id.</p>
  </form>

  <div class="section-head" style="margin-top:38px">
    <div>
      <p class="eyebrow">Live check</p>
      <h2>What the current table produces</h2>
    </div>
    <p class="tiny">Computed on this request by the same engine the runs use. Change a band, save, and this table is
      different — that is the test that the edit took.</p>
  </div>
  <table class="table">
    <thead>
      <tr><th>Gross</th><th class="num">Taxable / yr</th><th class="num">Top slice</th><th class="num">PAYE</th>
        <th class="num">Net</th><th class="num">Effective</th></tr>
    </thead>
    <tbody>
      <?php foreach ($ladder as $row): ?>
        <tr>
          <td><b><?= e((string)$row['label']) ?></b></td>
          <td class="num"><?= e(money((float)$row['taxable_annual'])) ?></td>
          <td class="num"><?= e(rtrim(rtrim(number_format((float)$row['marginal_rate'], 2), '0'), '.')) ?>%</td>
          <td class="num"><?= e(money((float)$row['paye'])) ?></td>
          <td class="num"><b><?= e(money((float)$row['net'])) ?></b></td>
          <td class="num"><?= e(number_format((float)$row['effective_rate'], 2)) ?>%</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
