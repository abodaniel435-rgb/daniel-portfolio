<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $markets @var array<int,array<string,mixed>> $commodities @var array<string,mixed>|null $last @var array<string,float> $rates */
// A posted form that failed validation comes back through remember_old(); a link
// from the board arrives through the query string. Either way the ids are cast,
// so nothing typed here reaches the page unescaped.
$pick = static function (string $key, string $default = ''): int {
    $value = old($key, $_GET[$key] ?? $default);
    return is_numeric($value) ? (int)$value : 0;
};
$picked = [
    'commodity' => $pick('commodity'),
    'from_market' => $pick('from_market'),
    'to_market' => $pick('to_market'),
];
$rateList = [];
foreach ($rates as $code => $value) {
    $rateList[] = '<span class="mono">' . e((string)$code) . '</span> '
        . e(rtrim(rtrim(number_format((float)$value, 2), '0'), '.'));
}
$rateListHtml = implode(', ', $rateList);
$distancePrefill = (string)(old('distance', $_GET['distance'] ?? '') ?: '');
$daysPrefill = (int)(old('days', $_GET['days'] ?? 14) ?: 14);
$byCategory = [];
foreach ($commodities as $commodity) {
    $byCategory[(string)$commodity['category']][] = $commodity;
}
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Buy here, sell there · the server does the subtraction</p>
      <h1>Is it worth the trip?</h1>
      <p class="lede">The price difference is the least interesting number in the decision. This form takes
        both medians from the verified board, converts every quote to naira per kilogram first, then pays
        for the journey — return fuel, levies, checkpoints, porters and the share that never reaches the
        market — and tells you how many kilograms have to move before hauling beats selling where you
        already stand.</p>
    </div>
  </div>

  <div class="split-page">
    <form method="post" action="<?= e(url('/compare')) ?>" class="panel" autocomplete="off">
      <?= csrf_field() ?>

      <div class="field">
        <label for="commodity">Commodity</label>
        <select name="commodity" id="commodity" required>
          <option value="">Choose a line on the board</option>
          <?php foreach ($byCategory as $category => $items): ?>
            <optgroup label="<?= e(ucfirst((string)$category)) ?>">
              <?php foreach ($items as $commodity): ?>
                <option value="<?= (int)$commodity['id'] ?>"<?= $picked['commodity'] === (int)$commodity['id'] ? ' selected' : '' ?>>
                  <?= e((string)$commodity['name']) ?> — <?= e((string)$commodity['unit_label']) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
        <?= field_error('commodity') ?>
      </div>

      <div class="grid-2">
        <div class="field">
          <label for="from_market">Buying at</label>
          <select name="from_market" id="from_market" required>
            <?php foreach ($markets as $market): ?>
              <option value="<?= (int)$market['id'] ?>"<?= $picked['from_market'] === (int)$market['id'] ? ' selected' : '' ?>>
                <?= e((string)$market['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="to_market">Selling at</label>
          <select name="to_market" id="to_market" required>
            <?php foreach ($markets as $market): ?>
              <option value="<?= (int)$market['id'] ?>"<?= $picked['to_market'] === (int)$market['id'] ? ' selected' : '' ?>>
                <?= e((string)$market['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid-2">
        <div class="field">
          <label for="distance">Distance, one way (km)</label>
          <input type="number" name="distance" id="distance" min="0" max="99999" step="0.1"
                 value="<?= e($distancePrefill) ?>" placeholder="leaves blank = the market's own figure">
        </div>
        <div class="field">
          <label for="days">Priced from</label>
          <select name="days" id="days">
            <?php foreach ([7 => 'the last 7 days', 14 => 'the last 14 days', 30 => 'the last 30 days'] as $value => $label): ?>
              <option value="<?= $value ?>" <?= $daysPrefill === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <button class="btn btn-primary" type="submit">Work it out</button>
      <p class="tiny" style="margin-top:14px">Every run is stored in <span class="mono">calculations</span>
        with the inputs the engine consumed, so the number you quoted at the gate can be shown again with
        its working.</p>
    </form>

    <div>
      <?php if ($last === null): ?>
        <div class="panel readout">
          <h3>Nothing run yet</h3>
          <p>Pick a commodity and two markets. If either side has fewer than three verified quotes the
            answer is printed but marked as unreliable — two thin boards do not make a trustworthy gap,
            and this page would rather say so than round a guess into a decision.</p>
          <p class="tiny" style="margin:0">The rates it uses are editable by an administrator:
            <?= $rateListHtml ?>.
          </p>
        </div>
      <?php else: ?>
        <?php $result = $last['result']; $aggFrom = $last['aggFrom']; $aggTo = $last['aggTo']; ?>
        <div class="readout">
          <div class="panel">
            <p class="eyebrow" style="margin-bottom:6px"><?= e((string)$last['commodity']) ?> ·
              <?= e((string)$last['from']) ?> → <?= e((string)$last['to']) ?></p>
            <div class="readout-big"><?= e(money((float)$result['netPerKg'])) ?><span class="tiny" style="display:block;font-family:var(--font-body)">net per kilogram, after the trip</span></div>

            <div class="readout-row"><span>Median where you buy</span><b><?= e(money((float)$aggFrom['median'])) ?>/kg · <?= (int)$aggFrom['count'] ?> quotes</b></div>
            <div class="readout-row"><span>Median where you sell</span><b><?= e(money((float)$aggTo['median'])) ?>/kg · <?= (int)$aggTo['count'] ?> quotes</b></div>
            <div class="readout-row"><span>Gap to chase</span><b><?= e(money((float)$result['gapPerKg'])) ?>/kg</b></div>
            <div class="readout-row"><span>Trip cost</span><b><?= e(money((float)$result['tripCost'])) ?></b></div>
            <p class="tiny" style="margin:8px 0 0">
              <?= e(money((float)$result['fuel'])) ?> fuel for
              <?= e(number_format((float)$result['distanceKm'], 0)) ?> km each way ·
              <?= e(money((float)$result['levies'])) ?> levies and checkpoints ·
              <?= e(money((float)$result['porter'])) ?> porters at the gate
            </p>
            <div class="readout-row" style="margin-top:8px"><span>Cost per sellable kilogram</span><b><?= e(money((float)$result['costPerKg'])) ?></b></div>
            <div class="readout-row"><span>Break-even load</span><b><?= e(number_format((float)$result['breakEvenKg'], 0)) ?> kg</b></div>
            <div class="readout-row"><span>If the vehicle fills</span><b><?= e(money((float)$result['fullLoadProfit'])) ?></b></div>

            <p class="verdict <?= (float)$result['netPerKg'] > 0 ? 'verdict-ok' : 'verdict-bad' ?>" style="margin-top:16px">
              <?= e((string)$result['verdict']) ?>
            </p>

            <?php if (!$result['trustworthy']): ?>
              <p class="verdict verdict-bad" style="margin-top:10px">
                Not enough verified quotes on at least one side. This is arithmetic on thin data — treat it
                as a question to ask, not an answer.
              </p>
            <?php endif; ?>

            <p class="tiny" style="margin-top:14px">
              <?= e(number_format((float)$result['capacityKg'], 0)) ?> kg is what one trip carries,
              <?= e(number_format((float)$result['sellableKg'], 0)) ?> kg of it arrives sellable after the
              expected spoilage. Change the assumption in the console and this answers differently.
            </p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
