<?php
$editing = $listing !== null;
$val = static function (string $key, $default = '') use ($listing) {
    if ($listing !== null && array_key_exists($key, $listing)) {
        $default = $listing[$key];
    }
    return old($key, $default);
};
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1><?= $editing ? 'Edit listing' : 'New harvest listing' ?></h1>
        <p><?= $editing ? 'Update quantity, price or availability. Buyers see changes immediately.' : 'Tell buyers exactly what is ready, how much there is and when they can collect.' ?></p>
      </div>
      <a class="btn btn-ghost" href="<?= e(url('/farmer/listings')) ?>">← Back to listings</a>
    </div>

    <div class="detail-grid" style="grid-template-columns:1.4fr .6fr">
      <div class="form-card">
        <form method="post" action="<?= e($editing ? url('/farmer/listings/' . $listing['id']) : url('/farmer/listings')) ?>" class="form-grid">
          <?= csrf_field() ?>
          <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$listing['id'] ?>"><?php endif; ?>

          <div>
            <label for="produce_name">Produce name</label>
            <input id="produce_name" name="produce_name" value="<?= e($val('produce_name')) ?>" required maxlength="160" placeholder="Roma tomatoes — grade A">
            <?= field_error('produce_name') ?>
            <p class="hint">Be specific: variety, grade and size help buyers decide faster.</p>
          </div>

          <div class="form-row">
            <div>
              <label for="category">Category</label>
              <select id="category" name="category" required>
                <?php foreach ($categories as $slug => $label): ?>
                  <option value="<?= e($slug) ?>" <?= $val('category', 'vegetables') === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <?= field_error('category') ?>
            </div>
            <div>
              <label for="available_from">Available from</label>
              <input id="available_from" name="available_from" type="date" value="<?= e($val('available_from', date('Y-m-d'))) ?>" min="<?= e(date('Y-m-d')) ?>" required>
              <?= field_error('available_from') ?>
            </div>
          </div>

          <div class="form-row-3">
            <div>
              <label for="quantity">Quantity</label>
              <input id="quantity" name="quantity" type="number" step="0.5" min="0.5" value="<?= e($val('quantity')) ?>" required placeholder="120">
              <?= field_error('quantity') ?>
            </div>
            <div>
              <label for="unit">Unit</label>
              <select id="unit" name="unit" required>
                <?php foreach ($units as $unit): ?>
                  <option value="<?= e($unit) ?>" <?= $val('unit', 'kg') === $unit ? 'selected' : '' ?>>per <?= e($unit) ?></option>
                <?php endforeach; ?>
              </select>
              <?= field_error('unit') ?>
            </div>
            <div>
              <label for="unit_price">Unit price (₦)</label>
              <input id="unit_price" name="unit_price" type="number" step="0.01" min="1" value="<?= e($val('unit_price')) ?>" required placeholder="1450">
              <?= field_error('unit_price') ?>
            </div>
          </div>

          <div class="form-row">
            <div>
              <label for="state">State</label>
              <select id="state" name="state" required>
                <option value="">Select state</option>
                <?php foreach ($states as $state): ?>
                  <option value="<?= e($state) ?>" <?= $val('state', $profile['state'] ?? '') === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                <?php endforeach; ?>
              </select>
              <?= field_error('state') ?>
            </div>
            <div>
              <label for="lga">Local government area</label>
              <input id="lga" name="lga" value="<?= e($val('lga', $profile['lga'] ?? '')) ?>" required maxlength="90" placeholder="Abeokuta North">
              <?= field_error('lga') ?>
            </div>
          </div>

          <div>
            <label for="pickup_window">Pickup window</label>
            <input id="pickup_window" name="pickup_window" value="<?= e($val('pickup_window', 'Mon–Sat · 7:00am – 4:00pm')) ?>" required maxlength="120" placeholder="Mon–Sat · 6:00am – 4:00pm">
            <?= field_error('pickup_window') ?>
          </div>

          <div>
            <label for="location">Pickup location / landmark</label>
            <input id="location" name="location" value="<?= e($val('location')) ?>" required maxlength="190" placeholder="Ogunlana Farm, Kobape Road, Abeokuta">
            <?= field_error('location') ?>
          </div>

          <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="2000" placeholder="How it was grown, how it is graded, how it is packed, minimum order…"><?= e($val('description')) ?></textarea>
            <?= field_error('description') ?>
            <p class="hint">Grading, packaging and minimum order details reduce back-and-forth with buyers.</p>
          </div>

          <div>
            <label for="image_url">Photo URL</label>
            <input id="image_url" name="image_url" type="url" value="<?= e($val('image_url')) ?>" placeholder="https://images.unsplash.com/…">
            <?= field_error('image_url') ?>
            <p class="hint">Optional. Leave blank to use a category photo. Use a clear, recent photo of this harvest.</p>
          </div>

          <div class="btn-row" style="margin-top:6px">
            <button class="btn btn-primary btn-lg" type="submit"><?= $editing ? 'Save changes' : 'Publish listing' ?></button>
            <a class="btn btn-ghost btn-lg" href="<?= e(url('/farmer/listings')) ?>">Cancel</a>
          </div>
          <?php if ($editing): ?>
            <p class="hint" style="margin:0">Quantity cannot drop below the amount already committed to open orders.</p>
          <?php endif; ?>
        </form>
      </div>

      <aside>
        <div class="panel">
          <div class="panel-head"><h2>Listing checklist</h2></div>
          <ul class="small muted" style="padding-left:18px;margin:0">
            <li style="margin-bottom:8px"><strong style="color:var(--forest)">Weigh what you sell.</strong> Publish the quantity you can actually hand over this week.</li>
            <li style="margin-bottom:8px"><strong style="color:var(--forest)">Name the window.</strong> Buyers plan transport around your pickup hours.</li>
            <li style="margin-bottom:8px"><strong style="color:var(--forest)">Price per unit.</strong> FarmCoop compares nothing — your price is the price.</li>
            <li style="margin-bottom:8px"><strong style="color:var(--forest)">Update fast.</strong> If the field is empty, mark the listing sold or paused.</li>
          </ul>
        </div>
        <?php if ($profile): ?>
          <div class="panel">
            <p class="eyebrow" style="margin-bottom:8px">Your farm</p>
            <h3 style="margin-bottom:4px"><?= e($profile['farm_name']) ?></h3>
            <p class="small muted" style="margin:0 0 12px"><?= e($profile['lga']) ?>, <?= e($profile['state']) ?><?= (int)$profile['is_verified'] === 1 ? ' · Verified' : ' · Verification pending' ?></p>
            <a class="btn btn-ghost btn-sm btn-block" href="<?= e(url('/farmer/profile')) ?>">Edit farm profile</a>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</div>
