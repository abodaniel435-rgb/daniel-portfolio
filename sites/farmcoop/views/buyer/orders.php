<?php
$tabs = ['' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'ready' => 'Ready', 'completed' => 'Completed', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>My orders</h1>
        <p>Cancel free of charge while an order is pending. After that the farmer is preparing your produce.</p>
      </div>
      <a class="btn btn-primary" href="<?= e(url('/marketplace')) ?>">Reserve more produce</a>
    </div>

    <div class="tab-links">
      <?php foreach ($tabs as $key => $label): ?>
        <a class="<?= $status === $key ? 'active' : '' ?>" href="<?= e(url('/buyer/orders' . ($key !== '' ? '?status=' . $key : ''))) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$orders): ?>
      <div class="empty">
        <strong>No orders in this view</strong>
        <p style="margin:0">Live harvests are published daily — reserve what your kitchen or stall needs.</p>
        <a class="btn btn-primary" href="<?= e(url('/marketplace')) ?>">Open the marketplace</a>
      </div>
    <?php else: ?>
      <div class="order-list-lg">
        <?php foreach ($orders as $order): ?>
          <div class="order-card">
            <img class="order-thumb" src="<?= e($order['image_url'] ?: category_image((string)$order['category'])) ?>" alt="" loading="lazy">
            <div class="order-info">
              <span class="order-ref"><?= e($order['reference']) ?></span>
              <h3><a href="<?= e(url('/buyer/orders/' . $order['id'])) ?>"><?= e($order['produce_name']) ?></a></h3>
              <p><?= e($order['farm_name'] ?: $order['farmer_name']) ?> · <?= e(fc_qty((float)$order['quantity'], $order['unit'])) ?> · <?= e(ucfirst($order['delivery_method'])) ?> <?= e(short_date($order['requested_date'])) ?> · ordered <?= e(short_date($order['created_at'])) ?></p>
            </div>
            <div class="order-side">
              <span class="order-amount"><?= e(money((float)$order['total_amount'])) ?></span>
              <?= fc_badge($order['status']) ?>
              <div class="table-actions">
                <a class="btn btn-ghost btn-sm" href="<?= e(url('/buyer/orders/' . $order['id'])) ?>">Details</a>
                <?php if ($order['status'] === 'pending'): ?>
                  <form method="post" action="<?= e(url('/buyer/orders/' . $order['id'] . '/cancel')) ?>" data-confirm="Cancel this order? The produce returns to the marketplace.">
                    <?= csrf_field() ?>
                    <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head"><h2>What each status means</h2></div>
      <div class="grid grid-4" style="gap:16px">
        <div><strong class="small" style="display:block">Pending</strong><span class="small muted">The farmer is deciding. You can cancel.</span></div>
        <div><strong class="small" style="display:block">Accepted</strong><span class="small muted">Quantity committed and being prepared.</span></div>
        <div><strong class="small" style="display:block">Ready</strong><span class="small muted">Packed and waiting for your handover window.</span></div>
        <div><strong class="small" style="display:block">Completed</strong><span class="small muted">Collected or delivered. Order closed.</span></div>
      </div>
    </div>
  </div>
</div>
