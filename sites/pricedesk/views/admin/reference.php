<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $markets @var array<int,array<string,mixed>> $commodities */
require BASE_PATH . '/views/admin/nav.php';
$kinds = ['farmgate', 'wholesale', 'retail'];
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">The axis every quote is normalised onto</p>
      <h1>Markets and commodities</h1>
      <p class="lede">These two tables decide what the board means. A commodity's unit weights decide that a
        "crate" is 22 kg and a "mudu" is 0.12 kg; a market's distance decides what a trip costs. Change a
        weight here and every existing quote is re-read through it on the next page view — nothing has to be
        re-filed, because the raw quote is stored and the conversion is not.</p>
    </div>
  </div>

  <div class="panel">
    <h3>Add a market</h3>
    <form method="post" action="<?= e(url('/admin/markets')) ?>" class="filters" style="margin-bottom:0">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="0">
      <div class="field" style="flex:1"><label for="mk-name">Name</label>
        <input type="text" id="mk-name" name="name" required maxlength="120" value="<?= e((string)old('name')) ?>" placeholder="Ariaria Market"></div>
      <div class="field"><label for="mk-state">State</label>
        <input type="text" id="mk-state" name="state" maxlength="60" value="<?= e((string)old('state', 'Lagos')) ?>"></div>
      <div class="field"><label for="mk-kind">Kind</label>
        <select id="mk-kind" name="kind"><?php foreach ($kinds as $kind): ?><option value="<?= e($kind) ?>"><?= e(ucfirst($kind)) ?></option><?php endforeach; ?></select></div>
      <div class="field" style="min-width:130px"><label for="mk-km">Typical distance (km)</label>
        <input type="text" id="mk-km" name="haul_km" value="<?= e((string)old('haul_km', '0')) ?>" inputmode="decimal"></div>
      <label class="check" for="mk-active" style="margin:0"><input type="checkbox" id="mk-active" name="is_active" checked> On the board</label>
      <button class="btn btn-primary btn-sm" type="submit">Add market</button>
    </form>
    <p class="tiny" style="margin:12px 0 0">Distance is what a load typically travels to reach this market
      from where it is bought. Leave the comparison form's distance blank and this is the figure used.</p>
  </div>

  <div class="section">
    <div class="section-head"><h2>Every market</h2><span class="tiny"><?= count($markets) ?> rows</span></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th scope="col">Name</th><th scope="col">State</th><th scope="col">Kind</th>
            <th scope="col" class="n">Distance km</th><th scope="col">On the board</th>
            <th scope="col"><span class="visually-hidden">Save</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($markets as $market): ?>
            <tr>
              <td><input type="text" name="name" form="mk<?= (int)$market['id'] ?>" required maxlength="120" value="<?= e((string)$market['name']) ?>"></td>
              <td><input type="text" name="state" form="mk<?= (int)$market['id'] ?>" maxlength="60" value="<?= e((string)$market['state']) ?>" style="width:110px"></td>
              <td><select name="kind" form="mk<?= (int)$market['id'] ?>" style="width:130px">
                <?php foreach ($kinds as $kind): ?>
                  <option value="<?= e($kind) ?>" <?= (string)$market['kind'] === $kind ? 'selected' : '' ?>><?= e(ucfirst($kind)) ?></option>
                <?php endforeach; ?>
              </select></td>
              <td class="n"><input type="text" name="haul_km" form="mk<?= (int)$market['id'] ?>" value="<?= e((string)$market['haul_km']) ?>" inputmode="decimal" style="width:90px;text-align:right"></td>
              <td><label class="check" style="margin:0">
                <input type="checkbox" name="is_active" form="mk<?= (int)$market['id'] ?>" <?= (int)$market['is_active'] === 1 ? 'checked' : '' ?>> active</label></td>
              <td class="n"><button class="btn btn-ghost btn-sm" type="submit" form="mk<?= (int)$market['id'] ?>">Save</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="section">
    <div class="panel">
      <h3>Add a commodity</h3>
      <form method="post" action="<?= e(url('/admin/commodities')) ?>" class="filters" style="margin-bottom:0">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="0">
        <div class="field" style="flex:1"><label for="cm-name">Name</label>
          <input type="text" id="cm-name" name="name" required maxlength="90" value="<?= e((string)old('name')) ?>" placeholder="Tomato (Fresh)"></div>
        <div class="field"><label for="cm-category">Category</label>
          <input type="text" id="cm-category" name="category" maxlength="40" value="<?= e((string)old('category', 'crop')) ?>"></div>
        <div class="field" style="min-width:230px"><label for="cm-units">Units and their weight in kg</label>
          <input type="text" id="cm-units" name="units_text" value="<?= e((string)old('units_text', 'crate=22, basket=8, kg=1')) ?>" placeholder="crate=22, mudu=0.12"></div>
        <div class="field" style="min-width:130px"><label for="cm-default">Default kg</label>
          <input type="text" id="cm-default" name="unit_weight_kg" value="<?= e((string)old('unit_weight_kg', '1')) ?>" inputmode="decimal"></div>
        <div class="field" style="min-width:140px"><label for="cm-label">Shown as</label>
          <input type="text" id="cm-label" name="unit_label" maxlength="40" value="<?= e((string)old('unit_label', 'kg')) ?>"></div>
        <label class="check" for="cm-active" style="margin:0"><input type="checkbox" id="cm-active" name="is_active" checked> Tracked</label>
        <button class="btn btn-primary btn-sm" type="submit">Add commodity</button>
      </form>
      <p class="tiny" style="margin:12px 0 0">Pairs that will not parse are dropped rather than stored, so a
        stray comma cannot poison the axis the whole board is normalised onto. "Default kg" is what an
        unrecognised unit is treated as, so make it the unit your reporters use most.</p>
    </div>
  </div>

  <div class="section">
    <div class="section-head"><h2>Every commodity</h2><span class="tiny"><?= count($commodities) ?> rows</span></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th scope="col">Name</th><th scope="col">Category</th><th scope="col">Unit weights (kg)</th>
            <th scope="col" class="n">Default kg</th><th scope="col">Shown as</th>
            <th scope="col"><span class="visually-hidden">Save</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($commodities as $commodity): ?>
            <?php
              // The form element itself sits after the table: a <form> may not
              // span several <td>s, so each control points back at it with the
              // HTML5 form attribute.
              $formId = 'cm' . (int)$commodity['id'];
              $pairs = [];
              foreach (Board::weights($commodity) as $unit => $kg) {
                  $pairs[] = $unit . '=' . rtrim(rtrim(number_format((float)$kg, 3, '.', ''), '0'), '.');
              }
            ?>
            <tr>
              <td><input type="text" name="name" form="<?= e($formId) ?>" required maxlength="90" value="<?= e((string)$commodity['name']) ?>"></td>
              <td><input type="text" name="category" form="<?= e($formId) ?>" maxlength="40" value="<?= e((string)$commodity['category']) ?>" style="width:100px"></td>
              <td><input type="text" name="units_text" form="<?= e($formId) ?>" value="<?= e(implode(', ', $pairs)) ?>" style="min-width:220px"></td>
              <td class="n"><input type="text" name="unit_weight_kg" form="<?= e($formId) ?>" value="<?= e((string)$commodity['unit_weight_kg']) ?>" inputmode="decimal" style="width:80px;text-align:right"></td>
              <td><input type="text" name="unit_label" form="<?= e($formId) ?>" maxlength="40" value="<?= e((string)$commodity['unit_label']) ?>" style="width:130px"></td>
              <td class="n" style="white-space:nowrap">
                <label class="check" style="margin:0 8px 0 0;display:inline-flex">
                  <input type="checkbox" name="is_active" form="<?= e($formId) ?>" <?= (int)$commodity['is_active'] === 1 ? 'checked' : '' ?>> on</label>
                <button class="btn btn-ghost btn-sm" type="submit" form="<?= e($formId) ?>">Save</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="tiny" style="margin-top:14px">Switching a commodity off hides it from the board and from the
      filing form. It does not delete the quotes filed against it — history is the only thing a price board
      has to sell, so nothing in this console destroys it.</p>
  </div>

  <div style="display:none" aria-hidden="true">
    <?php foreach ($markets as $market): ?>
      <form id="mk<?= (int)$market['id'] ?>" method="post" action="<?= e(url('/admin/markets')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$market['id'] ?>">
      </form>
    <?php endforeach; ?>
    <?php foreach ($commodities as $commodity): ?>
      <form id="cm<?= (int)$commodity['id'] ?>" method="post" action="<?= e(url('/admin/commodities')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$commodity['id'] ?>">
      </form>
    <?php endforeach; ?>
  </div>
</section>
