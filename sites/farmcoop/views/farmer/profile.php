<?php
$val = static function (string $key, $default = '') use ($profile) {
    if ($profile !== null && array_key_exists($key, $profile)) {
        $default = $profile[$key];
    }
    return old($key, $default);
};
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Farm profile</h1>
        <p>The storefront buyers see. Verification is granted by FarmCoop operations after a short review.</p>
      </div>
      <?php if ($profile): ?>
        <?= (int)$profile['is_verified'] === 1 ? '<span class="badge badge-ok">✓ Verified farm</span>' : '<span class="badge badge-warn">Verification pending</span>' ?>
      <?php endif; ?>
    </div>

    <div class="detail-grid" style="grid-template-columns:1.3fr .7fr">
      <div class="form-card">
        <form method="post" action="<?= e(url('/farmer/profile')) ?>" class="form-grid">
          <?= csrf_field() ?>

          <div>
            <label for="farm_name">Farm name</label>
            <input id="farm_name" name="farm_name" value="<?= e($val('farm_name')) ?>" required maxlength="160" placeholder="Ogunlana Family Farms">
            <?= field_error('farm_name') ?>
          </div>

          <div class="form-row">
            <div>
              <label for="state">State</label>
              <select id="state" name="state" required>
                <option value="">Select state</option>
                <?php foreach ($states as $state): ?>
                  <option value="<?= e($state) ?>" <?= $val('state') === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                <?php endforeach; ?>
              </select>
              <?= field_error('state') ?>
            </div>
            <div>
              <label for="lga">Local government area</label>
              <input id="lga" name="lga" value="<?= e($val('lga')) ?>" required maxlength="90" placeholder="Abeokuta North">
              <?= field_error('lga') ?>
            </div>
          </div>

          <div>
            <label for="produce_focus">Produce focus</label>
            <input id="produce_focus" name="produce_focus" value="<?= e($val('produce_focus')) ?>" required maxlength="190" placeholder="Tomatoes, tatashe and leafy greens">
            <?= field_error('produce_focus') ?>
            <p class="hint">A short phrase buyers see on your farm card.</p>
          </div>

          <div>
            <label for="about">About the farm</label>
            <textarea id="about" name="about" maxlength="1200" placeholder="How long you have farmed, how you grade and pack, which buyers you already supply…"><?= e($val('about')) ?></textarea>
            <?= field_error('about') ?>
          </div>

          <div>
            <label for="photo_url">Farm photo URL</label>
            <input id="photo_url" name="photo_url" type="url" value="<?= e($val('photo_url')) ?>" placeholder="https://images.unsplash.com/…">
            <?= field_error('photo_url') ?>
            <p class="hint">Optional but recommended — a real photo of the farm builds buyer confidence.</p>
          </div>

          <div class="btn-row">
            <button class="btn btn-primary btn-lg" type="submit"><?= $profile ? 'Save profile' : 'Create farm profile' ?></button>
            <a class="btn btn-ghost btn-lg" href="<?= e(url('/farmer')) ?>">Back to dashboard</a>
          </div>
        </form>
      </div>

      <aside>
        <div class="panel">
          <p class="eyebrow" style="margin-bottom:10px">Preview</p>
          <div style="border:1px solid var(--line);border-radius:14px;overflow:hidden;background:var(--paper)">
            <div style="height:140px;background:var(--cream-deep) center/cover no-repeat;background-image:url('<?= e($val('photo_url') ?: 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=800&q=80') ?>')"></div>
            <div style="padding:16px">
              <div class="flex-between" style="margin-bottom:6px">
                <?php if ($profile && (int)$profile['is_verified'] === 1): ?>
                  <span class="badge badge-ok">Verified</span>
                <?php else: ?>
                  <span class="badge badge-neutral">Unverified</span>
                <?php endif; ?>
                <span class="small muted"><?= e($val('state') ?: 'State') ?></span>
              </div>
              <strong style="font-family:var(--font-display);font-size:1.05rem;display:block;color:var(--forest-deep)"><?= e($val('farm_name') ?: 'Your farm name') ?></strong>
              <span class="small muted" style="display:block;margin-bottom:8px"><?= e($val('name', Auth::user()['name'])) ?> · <?= e($val('lga') ?: 'LGA') ?></span>
              <span class="farm-focus" style="font-size:.78rem;color:var(--forest);background:var(--cream);border:1px solid var(--line);display:inline-block;padding:4px 9px;border-radius:7px"><?= e($val('produce_focus') ?: 'Produce focus') ?></span>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head"><h2>Verification</h2></div>
          <?php if ($profile && (int)$profile['is_verified'] === 1): ?>
            <p class="small muted">Verified on <?= e(short_date($profile['verified_at'])) ?>. Your listings carry a verified badge in the marketplace.</p>
            <div class="note note-ok">Keep your quantity and pickup windows accurate — verification is reviewed if buyers report issues.</div>
          <?php else: ?>
            <p class="small muted">Complete every field and add a farm photo. Operations verifies farms within two working days by phone and location check.</p>
            <div class="note">Unverified farms can still list produce, but only verified farms carry the badge buyers filter for.</div>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </div>
</div>
