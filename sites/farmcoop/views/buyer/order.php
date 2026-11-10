<?php
$steps = ['pending' => 0, 'accepted' => 1, 'ready' => 2, 'completed' => 3];
$stage = $steps[$order['status']] ?? -1;
$closed = in_array($order['status'], ['rejected', 'cancelled'], true);
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <span class="order-ref">Order <?= e($order['reference']) ?> · placed <?= e(short_datetime($order['created_at'])) ?></span>
        <h1 style="margin-top:6px"><?= e($order['produce_name']) ?></h1>
        <p><?= e(fc_qty((float)$order['quantity'], $order['unit'])) ?> from <?= e($order['farm_name'] ?: $order['farmer_name']) ?> · <?= e(money((float)$order['total_amount'])) ?></p>
      </div>
      <div style="text-align:right">
        <?= fc_badge($order['status']) ?>
        <div class="table-actions" style="justify-content:flex-end;margin-top:10px">
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/track?ref=' . urlencode((string)$order['reference']))) ?>">Public tracking</a>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/buyer/orders')) ?>">All orders</a>
        </div>
      </div>
    </div>

    <div class="detail-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h2>Progress</h2><?php if ($closed): ?><span class="badge badge-bad">Closed</span><?php endif; ?></div>
          <?php if ($closed): ?>
            <div class="note" style="border-left-color:var(--clay)">This order was <?= e($order['status']) ?> on <?= e(short_datetime($order['updated_at'])) ?>. The quantity was returned to the listing and nothing is owed.</div>
          <?php else: ?>
            <div class="timeline">
              <div class="tl-item <?= $stage >= 0 ? 'done' : '' ?>"><span class="tl-dot">1</span><div class="tl-body"><strong>Order placed</strong><span>Quantity held on the listing, farmer notified by email.</span><time><?= e(short_datetime($order['created_at'])) ?></time></div></div>
              <div class="tl-item <?= $stage >= 1 ? 'done' : '' ?>"><span class="tl-dot">2</span><div class="tl-body"><strong>Accepted by the farmer</strong><span>Your quantity is committed and being prepared.</span><time><?= $order['accepted_at'] ? e(short_datetime($order['accepted_at'])) : 'Waiting' ?></time></div></div>
              <div class="tl-item <?= $stage >= 2 ? 'done' : '' ?>"><span class="tl-dot">3</span><div class="tl-body"><strong>Packed and ready</strong><span>Collect within the pickup window, or await delivery.</span><time><?= $order['ready_at'] ? e(short_datetime($order['ready_at'])) : 'Not yet packed' ?></time></div></div>
              <div class="tl-item <?= $stage >= 3 ? 'done' : '' ?>"><span class="tl-dot">4</span><div class="tl-body"><strong>Completed</strong><span>Handed over and the order is closed.</span><time><?= $order['completed_at'] ? e(short_datetime($order['completed_at'])) : 'Pending handover' ?></time></div></div>
            </div>
          <?php endif; ?>
        </div>

        <div class="panel">
          <div class="panel-head"><h2>Order details</h2><span class="pill-note"><?= e(ucfirst($order['delivery_method'])) ?></span></div>
          <dl class="kv">
            <dt>Produce</dt><dd><?= e($order['produce_name']) ?> · <?= e(category_label($order['category'])) ?></dd>
            <dt>Quantity</dt><dd><?= e(fc_qty((float)$order['quantity'], $order['unit'])) ?></dd>
            <dt>Unit price</dt><dd><?= e(money((float)$order['unit_price'])) ?> per <?= e($order['unit']) ?></dd>
            <dt>Total</dt><dd><?= e(money((float)$order['total_amount'])) ?> (pay on handover)</dd>
            <dt>Handover</dt><dd><?= e(ucfirst($order['delivery_method'])) ?> · <?= e(short_date($order['requested_date'])) ?></dd>
            <?php if ($order['delivery_method'] === 'delivery'): ?>
              <dt>Delivery address</dt><dd><?= e($order['delivery_address']) ?></dd>
            <?php else: ?>
              <dt>Pickup point</dt><dd><?= e($order['listing_location']) ?></dd>
              <dt>Pickup window</dt><dd><?= e($order['pickup_window'] ?: 'Confirm with the farm') ?></dd>
            <?php endif; ?>
            <?php if ($order['notes'] !== ''): ?><dt>Your notes</dt><dd><?= e($order['notes']) ?></dd><?php endif; ?>
            <dt>Reference</dt><dd class="mono"><?= e($order['reference']) ?></dd>
          </dl>
        </div>

        <?php if ($events): ?>
          <div class="panel">
            <div class="panel-head"><h2>Activity trail</h2><span class="badge badge-neutral"><?= count($events) ?> events</span></div>
            <div class="stack">
              <?php foreach (array_reverse($events) as $event): ?>
                <div class="flex-between" style="border-bottom:1px dashed var(--line);padding-bottom:9px">
                  <span style="font-size:.86rem"><?= fc_badge($event['status']) ?> <span class="muted small"><?= e($event['note']) ?></span></span>
                  <span class="small muted nowrap"><?= e(short_datetime($event['created_at'])) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <aside>
        <div class="panel">
          <div class="panel-head"><h2>The farm</h2><?= (int)$order['is_verified'] === 1 ? '<span class="badge badge-ok">Verified</span>' : '<span class="badge badge-neutral">Unverified</span>' ?></div>
          <h3 style="margin-bottom:2px"><?= e($order['farm_name'] ?: $order['farmer_name']) ?></h3>
          <p class="small muted"><?= e($order['farm_lga'] ?: $order['listing_lga']) ?>, <?= e($order['farm_state'] ?: $order['listing_state']) ?></p>
          <p class="small muted">Contact: <?= e($order['farmer_name']) ?><?= $order['farmer_phone'] ? ' · ' . e($order['farmer_phone']) : '' ?></p>
          <a class="btn btn-ghost btn-block" href="<?= e(url('/listing/' . $order['listing_id'])) ?>">View the listing</a>
        </div>

        <div class="panel">
          <div class="panel-head"><h2>Actions</h2></div>
          <?php if ($order['status'] === 'pending'): ?>
            <form method="post" action="<?= e(url('/buyer/orders/' . $order['id'] . '/cancel')) ?>" data-confirm="Cancel this order? The produce returns to the marketplace.">
              <?= csrf_field() ?>
              <button class="btn btn-danger btn-block" type="submit">Cancel this order</button>
            </form>
            <p class="hint">Free cancellation is only possible while the order is pending.</p>
          <?php elseif ($order['status'] === 'accepted'): ?>
            <div class="note">The farmer has accepted. Contact them if your collection time changes.</div>
          <?php elseif ($order['status'] === 'ready'): ?>
            <div class="note note-ok">Your produce is packed. Collect within <?= e($order['pickup_window'] ?: 'the agreed window') ?>.</div>
          <?php else: ?>
            <p class="small muted" style="margin:0">Nothing left to do on this order.</p>
          <?php endif; ?>
          <a class="btn btn-gold btn-block" style="margin-top:10px" href="<?= e(url('/marketplace')) ?>">Order again</a>
        </div>
      </aside>
    </div>
  </div>
</div>
