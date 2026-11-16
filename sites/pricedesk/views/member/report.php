<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $markets @var array<int,array<string,mixed>> $commodities @var string $today */
$units = [];
$weightsByCommodity = [];
foreach ($commodities as $commodity) {
    $weights = Board::weights($commodity);
    $weightsByCommodity[(int)$commodity['id']] = [
        'weights' => $weights,
        'fallback' => (float)$commodity['unit_weight_kg'],
        'label' => (string)$commodity['unit_label'],
    ];
    foreach (array_keys($weights) as $unit) {
        $units[(string)$unit] = true;
    }
}
$units['kg'] = true;
ksort($units);
$byCategory = [];
foreach ($commodities as $commodity) {
    $byCategory[(string)$commodity['category']][] = $commodity;
}
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Members only · queued until a reviewer sees it</p>
      <h1>File today's price</h1>
      <p class="lede">Quote it the way you traded it. A mudu, a crate, a load of tubers, a 50 kg bag — the
        server converts every one of them to naira per kilogram using the weights on the commodity, then
        places your quote beside everybody else's for the same market on the same day.</p>
    </div>
  </div>

  <div class="split-page">
    <form method="post" action="<?= e(url('/report')) ?>" class="panel" id="report-form" autocomplete="off"
          data-weights="<?= e(json_encode($weightsByCommodity)) ?>">
      <?= csrf_field() ?>

      <div class="field">
        <label for="commodity_id">What did you sell?</label>
        <select name="commodity_id" id="commodity_id" required>
          <option value="">Choose a commodity</option>
          <?php foreach ($byCategory as $category => $items): ?>
            <optgroup label="<?= e(ucfirst((string)$category)) ?>">
              <?php foreach ($items as $commodity): ?>
                <option value="<?= (int)$commodity['id'] ?>" <?= (string)old('commodity_id') === (string)$commodity['id'] ? 'selected' : '' ?>>
                  <?= e((string)$commodity['name']) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
        <?= field_error('commodity_id') ?>
      </div>

      <div class="field">
        <label for="market_id">Which market?</label>
        <select name="market_id" id="market_id" required>
          <?php foreach ($markets as $market): ?>
            <option value="<?= (int)$market['id'] ?>" <?= (string)old('market_id') === (string)$market['id'] ? 'selected' : '' ?>>
              <?= e((string)$market['name']) ?> · <?= e((string)$market['state']) ?> (<?= e((string)$market['kind']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <?= field_error('market_id') ?>
      </div>

      <div class="grid-2">
        <div class="field">
          <label for="price">Price (₦)</label>
          <input type="text" name="price" id="price" inputmode="decimal" required
                 value="<?= e((string)old('price')) ?>" placeholder="18500">
          <?= field_error('price') ?>
        </div>
        <div class="field">
          <label for="unit">For what quantity?</label>
          <input list="unit-list" name="unit" id="unit" required maxlength="24"
                 value="<?= e((string)(old('unit') ?: 'crate')) ?>">
          <datalist id="unit-list">
            <?php foreach (array_keys($units) as $unit): ?>
              <option value="<?= e((string)$unit) ?>"></option>
            <?php endforeach; ?>
          </datalist>
          <?= field_error('unit') ?>
        </div>
      </div>

      <div class="grid-2">
        <div class="field">
          <label for="quantity">How many of them?</label>
          <input type="number" name="quantity" id="quantity" min="1" max="9999" value="<?= e((string)(old('quantity') ?: 1)) ?>">
        </div>
        <div class="field">
          <label for="report_date">Trading day</label>
          <input type="date" name="report_date" id="report_date" required max="<?= e($today) ?>"
                 value="<?= e((string)(old('report_date') ?: $today)) ?>">
          <?= field_error('report_date') ?>
        </div>
      </div>

      <div class="field">
        <label for="note">Note <span class="tiny">— optional, and it travels with the price</span></label>
        <textarea name="note" id="note" rows="2" maxlength="255"><?= e((string)old('note')) ?></textarea>
        <?= field_error('note') ?>
      </div>

      <div class="panel" style="background:rgba(232,163,61,.1);margin-bottom:16px">
        <p class="tiny" style="margin:0">That is <b class="mono" id="per-kg">—</b> for this commodity.
          <span id="per-kg-note">Choose a commodity and a unit and this converts itself.</span></p>
      </div>

      <button class="btn btn-primary" type="submit">Send it to the queue</button>
      <p class="tiny" style="margin-top:14px">Nobody sees this as a board price until a reviewer verifies
        it. The people watching this line get told it is <em>queued</em>, which is the truth at that moment.</p>
    </form>

    <div>
      <div class="readout panel">
        <p class="eyebrow" style="margin-bottom:8px">Before you file</p>
        <h3 style="margin-bottom:10px">One member, one price, one line, one day</h3>
        <p class="tiny">If you already filed this commodity at this market for this date, the form will
          point you at your history instead of adding a second quote. That rule is a UNIQUE key in MySQL,
          not a polite request — it is the difference between a board and a shout.</p>
        <h3 style="margin:18px 0 10px">Quote the price you were paid</h3>
        <p class="tiny">Not the price you wanted, not the price from the truck that came before yours. The
          median is robust to one optimistic morning; it is not a substitute for honest reporting, and the
          spread column makes a market full of wishful thinking visible to everyone.</p>
        <h3 style="margin:18px 0 10px">Weights belong to the commodity</h3>
        <p class="tiny">If a crate in your market holds 18 kg rather than 22, that is not a rounding
          error, it is a 20% distortion on every comparison. Say so — an administrator edits the basket
          weight in the console and the whole board re-normalises on the next read.</p>
      </div>
      <p class="tiny" style="margin-top:16px">Your last reports live on
        <a href="<?= e(url('/history')) ?>">Your reports</a>, where a queued price can still be withdrawn
        and a verified one cannot, because at that point other people have traded against it.</p>
    </div>
  </div>
</section>

<script>
(function () {
  // The conversion shown here is the same division Pricing::perKg() performs on
  // the server, fed from the same weights the form posts. A reporter sees their
  // quote normalised before they file it, not after somebody else doubts it.
  var form = document.getElementById('report-form');
  if (!form) return;
  var table = JSON.parse(form.getAttribute('data-weights') || '{}');
  var out = document.getElementById('per-kg');
  var note = document.getElementById('per-kg-note');

  function key(unit) { return String(unit || '').toLowerCase().replace(/[^a-z]/g, ''); }

  function paint() {
    var entry = table[form.commodity_id.value];
    var price = parseFloat(String(form.price.value || '').replace(/[, ]/g, ''));
    var qty = Math.max(1, parseInt(form.quantity.value || '1', 10) || 1);
    if (!entry || !(price > 0)) {
      out.textContent = '—';
      note.textContent = 'Choose a commodity and a price and this converts itself.';
      return;
    }
    var kg = entry.weights[key(form.unit.value)] || entry.fallback || 1;
    var total = kg * qty;
    var perKg = price / total;
    out.textContent = '\u20A6' + perKg.toLocaleString('en-NG', { maximumFractionDigits: 0 }) + ' / kg';
    note.textContent = key(form.unit.value)
      ? 'One ' + form.unit.value + ' is booked at ' + kg + ' kg' + (qty > 1 ? ', and you sold ' + qty : '') + '.'
      : 'That unit is not on this commodity\u2019s list, so the default ' + entry.label + ' (' + entry.fallback + ' kg) was used. '
        + 'Name the unit exactly and the conversion follows it.';
  }

  ['commodity_id', 'price', 'quantity', 'unit'].forEach(function (name) {
    form.elements[name].addEventListener('input', paint);
    form.elements[name].addEventListener('change', paint);
  });
  paint();
})();
</script>
