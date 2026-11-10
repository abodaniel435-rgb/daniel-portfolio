<?php
$user = Auth::user();
$firstName = explode(' ', trim((string)$user['name']))[0];
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Buyer dashboard</h1>
        <p>Welcome back, <?= e($firstName) ?>. Orders, handover dates and live supply in one place.</p>
      </div>
      <a class="btn btn-primary" href="<?= e(url('/marketplace')) ?>">Shop the market</a>
    </div>

    <div class="stats-grid">
      <div class="stat stat-accent"><strong><?= (int)$stats['active'] ?></strong><span>Active orders</span></div>
      <div class="stat"><strong><?= (int)$stats['pending'] ?></strong><span>Awaiting farmer confirmation</span></div>
      <div class="stat"><strong><?= (int)$stats['completed'] ?></strong><span>Completed handovers</span></div>
      <div class="stat"><strong><?= e(money($stats['spend'])) ?></strong><span>Confirmed order value</span></div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div><h2>Your recent orders</h2><p>Every order keeps a reference and a status trail.</p></div>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/buyer/orders')) ?>">All orders</a>
      </div>
      <?php if (!$orders): ?>
        <div class="empty" style="padding:32px">
          <strong>No orders yet</strong>
          <p style="margin:0">Reserve a harvest and the farmer will confirm within a day.</p>
          <a class="btn btn-primary" href="<?= e(url('/marketplace')) ?>">Browse live harvests</a>
        </div>
      <?php else: ?>
        <div class="order-list-lg">
          <?php foreach ($orders as $order): ?>
            <div class="order-card">
              <img class="order-thumb" src="<?= e($order['image_url'] ?: category_image((string)$order['category'])) ?>" alt="" loading="lazy">
              <div class="order-info">
                <span class="order-ref"><?= e($order['reference']) ?> · <?= e(short_date($order['created_at'])) ?></span>
                <h3><a href="<?= e(url('/buyer/orders/' . $order['id'])) ?>"><?= e($order['produce_name']) ?></a></h3>
                <p><?= e($order['farm_name'] ?: $order['farmer_name']) ?> · <?= e(fc_qty((float)$order['quantity'], $order['unit'])) ?> · <?= e(ucfirst($order['delivery_method'])) ?> <?= e(short_date($order['requested_date'])) ?></p>
              </div>
              <div class="order-side">
                <span class="order-amount"><?= e(money((float)$order['total_amount'])) ?></span>
                <?= fc_badge($order['status']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div><h2>Fresh in the market</h2><p>Newly listed harvests with live quantities.</p></div>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/marketplace')) ?>">View all</a>
      </div>
      <div class="grid grid-3">
        <?php foreach ($recommended as $item): ?>
          <article class="produce-card">
            <a class="produce-link" href="<?= e(url('/listing/' . $item['id'])) ?>" aria-label="View <?= e($item['produce_name']) ?>"></a>
            <div class="produce-media">
              <img src="<?= e(listing_image($item)) ?>" alt="<?= e($item['produce_name']) ?>" loading="lazy">
              <div class="produce-tags"><span class="badge badge-gold"><?= e(category_label($item['category'])) ?></span></div>
              <span class="produce-qty"><?= e(fc_qty((float)$item['quantity'], $item['unit'])) ?> available</span>
            </div>
            <div class="produce-body">
              <span class="produce-farm"><?= e($item['farm_name'] ?: 'FarmCoop farm') ?> · <?= e($item['state']) ?></span>
              <h3 class="produce-title"><a href="<?= e(url('/listing/' . $item['id'])) ?>"><?= e($item['produce_name']) ?></a></h3>
              <div class="produce-foot">
                <span class="produce-price"><?= e(money((float)$item['unit_price'])) ?><small>per <?= e($item['unit']) ?></small></span>
                <span class="produce-meta">From <?= e(short_date($item['available_from'])) ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
