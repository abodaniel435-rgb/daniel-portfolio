<?php
$available = (float)$listing['quantity'];
$minDate = max(date('Y-m-d'), (string)$listing['available_from']);
$defaultQty = old('quantity', $available >= 10 ? 10 : $available);
$methods = delivery_methods();
$selectedMethod = old('delivery_method', 'pickup');
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <span class="order-ref">Checkout</span>
        <h1 style="margin-top:6px">Reserve <?= e($listing['produce_name']) ?></h1>
        <p><?= e($listing['farm_name'] ?: $listing['farmer_name']) ?> · <?= e($listing['lga']) ?>, <?= e($listing['state']) ?></p>
      </div>
      <a class="btn btn-ghost" href="<?= e(url('/listing/' . $listing['id'])) ?>">← Back to listing</a>
    </div>

    <div class="detail-grid" style="grid-template-columns:1.35fr .65fr">
      <div class="form-card">
        <form method="post" action="<?= e(url('/buyer/checkout/' . $listing['id'])) ?>" class="form-grid"
              data-checkout data-price="<?= e((string)$listing['unit_price']) ?>" data-unit="<?= e($listing['unit']) ?>" data-available="<?= e((string)$available) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="listing_id" value="<?= (int)$listing['id'] ?>">

          <div class="form-row">
            <div>
              <label for="quantity">Quantity (<?= e($listing['unit']) ?>)</label>
              <input id="quantity" name="quantity" type="number" step="0.5" min="0.5" max="<?= e((string)$available) ?>" value="<?= e($defaultQty) ?>" required>
              <?= field_error('quantity') ?>
              <p class="hint" data-order-helper>Available: <?= e(fc_qty($available, $listing['unit'])) ?></p>
            </div>
            <div>
              <label for="requested_date">Collection / delivery date</label>
              <input id="requested_date" name="requested_date" type="date" value="<?= e(old('requested_date', $minDate)) ?>" min="<?= e($minDate) ?>" required>
              <?= field_error('requested_date') ?>
              <p class="hint">Available from <?= e(short_date($listing['available_from'])) ?></p>
            </div>
          </div>

          <div>
            <label for="delivery_method">Handover method</label>
            <select id="delivery_method" name="delivery_method" required>
              <?php foreach ($methods as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $selectedMethod === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <?= field_error('delivery_method') ?>
          </div>

          <div data-address-wrap class="<?= $selectedMethod === 'delivery' ? '' : 'hidden' ?>">
            <label for="delivery_address">Delivery address</label>
            <input id="delivery_address" name="delivery_address" value="<?= e(old('delivery_address')) ?>" maxlength="255" placeholder="Kitchen entrance, street, area, city">
            <?= field_error('delivery_address') ?>
            <p class="hint">Delivery logistics and any cost are agreed directly with the farmer before handover.</p>
          </div>

          <div>
            <label for="notes">Notes for the farmer</label>
            <textarea id="notes" name="notes" maxlength="500" style="min-height:100px" placeholder="Packaging, crate returns, arrival time, gate instructions…"><?= e(old('notes')) ?></textarea>
            <?= field_error('notes') ?>
          </div>

          <div class="buy-box" style="margin:0">
            <div class="total"><span>Order value</span><b data-order-total>—</b></div>
            <p class="hint" style="margin:0">You pay the farmer on collection or delivery. FarmCoop records the agreed value and the full order trail.</p>
          </div>

          <div class="btn-row">
            <button class="btn btn-gold btn-lg" type="submit">Place order</button>
            <a class="btn btn-ghost btn-lg" href="<?= e(url('/listing/' . $listing['id'])) ?>">Cancel</a>
          </div>
          <p class="hint" style="margin:0">Placing an order holds the stock immediately. You can cancel free of charge while the order is pending.</p>
        </form>
      </div>

      <aside>
        <div class="panel" style="padding:0;overflow:hidden">
          <img src="<?= e(listing_image($listing)) ?>" alt="<?= e($listing['produce_name']) ?>" style="width:100%;height:170px;object-fit:cover">
          <div style="padding:20px">
            <span class="badge badge-gold"><?= e(category_label($listing['category'])) ?></span>
            <h3 style="margin:10px 0 4px"><?= e($listing['produce_name']) ?></h3>
            <p class="small muted" style="margin:0 0 12px"><?= e($listing['farm_name'] ?: $listing['farmer_name']) ?><?= (int)$listing['is_verified'] === 1 ? ' · Verified' : '' ?></p>
            <dl class="kv" style="grid-template-columns:110px 1fr;font-size:.82rem">
              <dt>Unit price</dt><dd><?= e(money((float)$listing['unit_price'])) ?> / <?= e($listing['unit']) ?></dd>
              <dt>Available</dt><dd><?= e(fc_qty($available, $listing['unit'])) ?></dd>
              <dt>Pickup</dt><dd><?= e($listing['pickup_window'] ?: 'Arrange with farm') ?></dd>
              <dt>Location</dt><dd><?= e($listing['location']) ?></dd>
            </dl>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head"><h2>Buyer protection</h2></div>
          <ul class="small muted" style="padding-left:18px;margin:0">
            <li style="margin-bottom:7px">Stock is reserved the moment you order — it cannot be sold twice.</li>
            <li style="margin-bottom:7px">Cancel free while the farmer has not accepted.</li>
            <li style="margin-bottom:7px">Weighed in front of you at pickup.</li>
            <li>Everything is logged against reference <span class="mono">FC-…</span>.</li>
          </ul>
        </div>
      </aside>
    </div>
  </div>
</div>
