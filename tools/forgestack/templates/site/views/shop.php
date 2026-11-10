<?php
$site = site_config();
$user = Auth::user();
$confirmed = trim((string)($_GET['ref'] ?? ''));
?>
<section class="page-head page-head-compact">
  <div class="page-head-inner">
    <p class="eyebrow"><?= e($site['labels']['shop_title']) ?></p>
    <h1><?= $site['profile'] === 'restaurant' ? 'The counter, at home.' : 'Packing & supplies.' ?></h1>
    <p class="page-lede">Order for collection. Stock is checked inside a database transaction, so the last one cannot be sold twice.</p>
  </div>
</section>

<section class="section section-form">
  <?php if ($confirmed !== ''): ?>
    <div class="alert alert-success">Order <strong><?= e($confirmed) ?></strong> is confirmed. We will email you when it is ready.</div>
  <?php endif; ?>

  <?php if (!$products): ?>
    <div class="empty-state"><strong>Nothing in the catalogue yet.</strong><p>The team is setting it up. Check back shortly.</p></div>
  <?php else: ?>
    <div class="shop-layout">
      <div class="product-grid product-grid-wide">
        <?php foreach ($products as $product): ?>
          <article class="product-card">
            <div class="product-top">
              <span class="product-sku"><?= e($product['sku']) ?></span>
              <?= $product['stock'] > 0 ? '<span class="badge badge-ok">' . (int)$product['stock'] . ' in stock</span>' : '<span class="badge badge-bad">Sold out</span>' ?>
            </div>
            <h3><?= e($product['name']) ?></h3>
            <p><?= e($product['description']) ?></p>
            <div class="product-foot">
              <strong><?= money_fmt((float)$product['price']) ?></strong>
              <button class="btn btn-quiet btn-sm" type="button" data-pick-product="<?= (int)$product['id'] ?>" data-pick-name="<?= e($product['name']) ?>" data-pick-price="<?= (float)$product['price'] ?>" <?= $product['stock'] > 0 ? '' : 'disabled' ?>>Choose</button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <form class="card order-card" id="order-form" method="post" action="<?= e(url('/order')) ?>">
        <?= csrf_field() ?>
        <h2>Your order</h2>
        <label for="product_id">Item</label>
        <select id="product_id" name="product_id" required>
          <?php foreach ($products as $product): ?>
            <option value="<?= (int)$product['id'] ?>" data-price="<?= (float)$product['price'] ?>" <?= $product['stock'] > 0 ? '' : 'disabled' ?>>
              <?= e($product['name']) ?> — <?= money_fmt((float)$product['price']) ?><?= $product['stock'] > 0 ? '' : ' (sold out)' ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="form-row">
          <div>
            <label for="quantity">Quantity</label>
            <input id="quantity" name="quantity" type="number" min="1" max="20" value="1" required data-order-qty>
          </div>
          <div>
            <label for="name">Name</label>
            <input id="name" name="name" value="<?= e($user['name'] ?? '') ?>" required>
          </div>
        </div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= e($user['email'] ?? '') ?>" required>
        <label for="phone">Phone</label>
        <input id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>" required>

        <div class="order-total">
          <span>Estimated total</span>
          <strong data-order-total data-currency="₦">—</strong>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Place order</button>
        <p class="form-fineprint">Collection only. No card details are collected on this site.</p>
      </form>
    </div>
  <?php endif; ?>
</section>
