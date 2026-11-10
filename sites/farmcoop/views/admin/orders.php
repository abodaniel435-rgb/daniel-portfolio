<?php
$qs = static function (array $overrides) use ($q, $status): string {
    $params = array_filter(array_merge(['q' => $q, 'status' => $status], $overrides), static fn ($v) => $v !== '' && $v !== null);
    return url('/admin/orders' . ($params ? '?' . http_build_query($params) : ''));
};
$tabs = ['' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'ready' => 'Ready', 'completed' => 'Completed', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>All orders</h1>
        <p>Every commitment made between a buyer and a farm, with the value each status represents.</p>
      </div>
      <span class="pill-note"><?= (int)$page['total'] ?> order<?= (int)$page['total'] === 1 ? '' : 's' ?></span>
    </div>

    <div class="stats-grid stats-grid-3">
      <div class="stat"><strong><?= (int)$totals['orders'] ?></strong><span>Orders recorded</span></div>
      <div class="stat"><strong><?= e(money((float)$totals['value'])) ?></strong><span>Total order value</span></div>
      <div class="stat stat-accent"><strong><?= e(money((float)$totals['settled'])) ?></strong><span>Completed (settled) value</span></div>
    </div>

    <div class="tab-links">
      <?php foreach ($tabs as $key => $label): ?>
        <a class="<?= $status === $key ? 'active' : '' ?>" href="<?= e($qs(['status' => $key, 'page' => null])) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>

    <form class="toolbar" method="get" action="<?= e(url('/admin/orders')) ?>">
      <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <input name="q" type="search" value="<?= e($q) ?>" placeholder="Search reference, buyer, farm or produce" aria-label="Search orders">
      <button class="btn btn-primary btn-sm" type="submit">Search</button>
      <?php if ($q !== '' || $status !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/orders')) ?>">Reset</a><?php endif; ?>
    </form>

    <?php if (!$rows): ?>
      <div class="empty"><strong>No orders match</strong>Clear the filters or search a different reference.</div>
    <?php else: ?>
      <div class="panel" style="padding:0;overflow:hidden">
        <div class="table-wrap" style="border:0">
          <table class="data">
            <thead><tr><th>Order</th><th>Buyer</th><th>Farm</th><th>Produce</th><th>Value</th><th>Fulfilment</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td>
                  <span class="order-ref"><?= e($row['reference']) ?></span><br>
                  <small class="muted"><?= e(short_datetime($row['created_at'])) ?></small>
                </td>
                <td><span class="small" style="font-weight:600"><?= e($row['buyer_name']) ?></span></td>
                <td>
                  <span class="small" style="font-weight:600"><?= e($row['farm_name'] ?: $row['farmer_name']) ?></span><br>
                  <small class="muted"><?= e($row['farmer_name']) ?></small>
                </td>
                <td>
                  <a class="small" style="font-weight:600" href="<?= e(url('/listing/' . $row['listing_id'])) ?>"><?= e($row['produce_name']) ?></a><br>
                  <small class="muted"><?= e(fc_qty((float)$row['quantity'], $row['unit'])) ?> at <?= e(money((float)$row['unit_price'])) ?></small>
                </td>
                <td class="nowrap"><strong><?= e(money((float)$row['total_amount'])) ?></strong></td>
                <td>
                  <small class="muted">
                    <?= e($row['delivery_method'] === 'delivery' ? 'Delivery' : 'Farm pickup') ?><br>
                    <?= e($row['requested_date'] ? short_date($row['requested_date']) : 'No date set') ?>
                  </small>
                </td>
                <td><?= fc_badge($row['status']) ?></td>
                <td>
                  <div class="table-actions">
                    <a class="btn btn-ghost btn-sm" href="<?= e(tracking_url($row)) ?>">Track</a>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/farm/' . $row['farmer_id'])) ?>">Farm</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if ($page['pages'] > 1): ?>
        <nav class="pagination" aria-label="Order pages">
          <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
            <?php if ($i === $page['current']): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= e($qs(['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head"><h2>Fulfilment reference</h2><span class="badge badge-gold">Operations</span></div>
      <div class="grid grid-3">
        <div><strong class="small" style="display:block">Stock is protected</strong><span class="small muted">Every order is placed inside a database transaction with a row lock, so two buyers cannot claim the same crate.</span></div>
        <div><strong class="small" style="display:block">Rejections restore stock</strong><span class="small muted">A farmer rejection or buyer cancellation returns the quantity to the listing automatically.</span></div>
        <div><strong class="small" style="display:block">Automation closes loops</strong><span class="small muted">Pending orders over 24h are chased by email; ready orders uncollected for 5 days are completed.</span></div>
        <div><strong class="small" style="display:block">Buyers can track publicly</strong><span class="small muted">Every reference resolves on the public tracking page without exposing buyer contact details.</span></div>
      </div>
    </div>
  </div>
</div>
