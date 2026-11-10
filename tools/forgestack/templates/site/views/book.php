<?php
$site = site_config();
$user = Auth::user();
$times = (array)cfg('form.times', []);
$quantityOptions = (array)cfg('form.quantity_options', []);
$minQty = (int)cfg('form.quantity_min', 1);
$maxQty = (int)cfg('form.quantity_max', 20);
$hasQuantityOptions = $quantityOptions !== [];
?>
<section class="page-head page-head-compact">
  <div class="page-head-inner">
    <p class="eyebrow"><?= e($site['labels']['book']) ?></p>
    <h1><?= e($site['labels']['form_title']) ?></h1>
    <p class="page-lede"><?= e($site['labels']['form_intro']) ?></p>
  </div>
</section>

<section class="section section-form">
  <div class="form-shell">
    <form class="card form-card" method="post" action="<?= e(url('/book')) ?>" novalidate>
      <?= csrf_field() ?>
      <?php if (errors()): ?>
        <div class="alert alert-error">Please check the fields marked below.</div>
      <?php endif; ?>

      <div class="form-row">
        <div>
          <label for="customer_name">Full name</label>
          <input id="customer_name" name="customer_name" value="<?= e(old('customer_name', $user['name'] ?? '')) ?>" required autocomplete="name">
          <?= field_error('customer_name') ?>
        </div>
        <div>
          <label for="customer_phone">Phone</label>
          <input id="customer_phone" name="customer_phone" value="<?= e(old('customer_phone', $user['phone'] ?? '')) ?>" required autocomplete="tel">
          <?= field_error('customer_phone') ?>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label for="customer_email">Email</label>
          <input id="customer_email" type="email" name="customer_email" value="<?= e(old('customer_email', $user['email'] ?? '')) ?>" required autocomplete="email">
          <?= field_error('customer_email') ?>
        </div>
        <div>
          <label for="quantity"><?= e($site['labels']['quantity']) ?> <span class="label-hint"><?= e(quantity_unit()) ?></span></label>
          <?php if ($hasQuantityOptions): ?>
            <select id="quantity" name="quantity">
              <?php foreach ($quantityOptions as $option): ?>
                <option value="<?= (int)$option ?>" <?= (int)old('quantity', (int)$option) === (int)$option ? 'selected' : '' ?>><?= (int)$option ?> <?= e(quantity_unit()) ?></option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input id="quantity" name="quantity" type="number" min="<?= $minQty ?>" max="<?= $maxQty ?>" value="<?= e(old('quantity', $minQty)) ?>" required>
          <?php endif; ?>
          <?= field_error('quantity') ?>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label for="request_date"><?= e($site['labels']['date']) ?></label>
          <input id="request_date" type="date" name="request_date" min="<?= date('Y-m-d') ?>" value="<?= e(old('request_date')) ?>" required>
          <?= field_error('request_date') ?>
        </div>
        <div>
          <label for="request_time"><?= e($site['labels']['time']) ?></label>
          <select id="request_time" name="request_time" required>
            <option value="">Choose a time</option>
            <?php foreach ($times as $time): ?>
              <option value="<?= e($time) ?>" <?= (string)old('request_time') === (string)$time ? 'selected' : '' ?>><?= e($time) ?></option>
            <?php endforeach; ?>
          </select>
          <?= field_error('request_time') ?>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label for="choice_a"><?= e($site['labels']['choice_a']) ?></label>
          <select id="choice_a" name="choice_a" required data-price-selector>
            <?php foreach ((array)cfg('form.choices_a', []) as $option): ?>
              <option value="<?= e($option['value']) ?>" data-price="<?= (float)$option['price'] ?>" <?= (string)old('choice_a') === (string)$option['value'] ? 'selected' : '' ?>>
                <?= e($option['value']) ?><?= (float)$option['price'] > 0 ? ' — ' . money_fmt((float)$option['price']) : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?= field_error('choice_a') ?>
        </div>
        <div>
          <label for="choice_b"><?= e($site['labels']['choice_b']) ?></label>
          <select id="choice_b" name="choice_b">
            <?php foreach ((array)cfg('form.choices_b', []) as $option): ?>
              <option value="<?= e($option) ?>" <?= (string)old('choice_b') === (string)$option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
          </select>
          <?= field_error('choice_b') ?>
        </div>
      </div>

      <div>
        <label for="notes"><?= e($site['labels']['notes']) ?></label>
        <textarea id="notes" name="notes" maxlength="1000" placeholder="<?= e($site['labels']['notes_help']) ?>"><?= e(old('notes')) ?></textarea>
        <?= field_error('notes') ?>
      </div>

      <div class="form-submit">
        <button class="btn btn-primary" type="submit"><?= e($site['labels']['book']) ?></button>
        <span class="form-total" data-price-total data-currency="₦">No payment is taken on this website.</span>
      </div>
    </form>

    <aside class="form-aside">
      <div class="aside-card">
        <h3><?= e($site['labels']['form_aside_title']) ?></h3>
        <ol class="aside-steps">
          <?php foreach ((array)$site['labels']['form_aside'] as $i => $line): ?>
            <li><span>0<?= $i + 1 ?></span><?= e($line) ?></li>
          <?php endforeach; ?>
        </ol>
      </div>
      <div class="aside-card aside-contact">
        <h3>Need a hand?</h3>
        <p>Call <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $site['contact']['phone'])) ?>"><?= e($site['contact']['phone']) ?></a> or email <a href="mailto:<?= e($site['contact']['email']) ?>"><?= e($site['contact']['email']) ?></a>.</p>
        <p class="aside-hours"><?= e($site['copy']['footer']['hours']) ?></p>
        <a class="text-link" href="<?= e(url('/track')) ?>">Already booked? <?= e($site['labels']['track']) ?> →</a>
      </div>
    </aside>
  </div>
</section>
