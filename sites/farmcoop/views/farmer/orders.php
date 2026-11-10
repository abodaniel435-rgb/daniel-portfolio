<?php
$tabs = ['' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'ready' => 'Ready', 'completed' => 'Completed', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Incoming orders</h1>
        <p>Accept quickly, pack carefully and mark ready when the produce is weighed and waiting.</p>
      </div>
      <div class="btn-row">
        <span class="pill-note"><?= (int)$counts['pending'] ?> pending</span>
        <span class="pill-note"><?= (int)$counts['ready'] ?> ready</span>
      </div>
    </div>

    <div class="tab-links">
      <?php foreach ($tabs as $key => $label): ?>
        <a class="<?= $status === $key ? 'active' : '' ?>" href="<?= e(url('/farmer/orders' . ($key !== '' ? '?status=' . $key : ''))) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$orders): ?>
      <div class="empty">
        <strong>No orders in this view</strong>
        <p style="margin:0">When a buyer reserves one of your listings it lands here with their quantity already held.</p>
        <a class="btn btn-primary" href="<?= e(url('/farmer/listings')) ?>">Check your listings</a>
      </div>
    <?php else: ?>
      <?php foreach ($orders as $order): ?>
        <article class="panel" style="padding:20px 22px">
          <div class="flex-between" style="align-items:flex-start;margin-bottom:14px">
            <div>
              <span class="order-ref"><?= e($order['reference']) ?> · <?= e(short_datetime($order['created_at'])) ?></span>
              <h2 style="font-size:1.1rem;margin:4px 0 2px"><?= e($order['produce_name']) ?></h2>
              <p class="small muted" style="margin:0"><?= e(fc_qty((float)$order['quantity'], $order['unit'])) ?> at <?= e(money((float)$order['unit_price'])) ?> per <?= e($order['unit']) ?> · <?= e(ucfirst($order['delivery_method'])) ?> for <?= e(short_date($order['requested_date'])) ?></p>
            </div>
            <div style="text-align:right">
              <span class="order-amount" style="font-size:1.25rem"><?= e(money((float)$order['total_amount'])) ?></span><br>
              <?= fc_badge($order['status']) ?>
            </div>
          </div>

          <div class="grid grid-3" style="gap:16px;margin-bottom:14px">
            <div>
              <span class="small muted" style="display:block;text-transform:uppercase;letter-spacing:.1em;font-size:.67rem;font-weight:700">Buyer</span>
              <strong style="font-size:.9rem"><?= e($order['buyer_name']) ?></strong>
              <span class="small muted" style="display:block"><?= e($order['buyer_phone'] ?: 'No phone on file') ?></span>
            </div>
            <div>
              <span class="small muted" style="display:block;text-transform:uppercase;letter-spacing:.1em;font-size:.67rem;font-weight:700">Handover</span>
              <strong style="font-size:.9rem"><?= e(ucfirst($order['delivery_method'])) ?></strong>
              <span class="small muted" style="display:block"><?= e($order['delivery_method'] === 'delivery' ? $order['delivery_address'] : ($order['pickup_window'] ?: 'Farm pickup')) ?></span>
            </div>
            <div>
              <span class="small muted" style="display:block;text-transform:uppercase;letter-spacing:.1em;font-size:.67rem;font-weight:700">Requested</span>
              <strong style="font-size:.9rem"><?= e(short_date($order['requested_date'])) ?></strong>
              <span class="small muted" style="display:block"><?= $order['reminder_sent_at'] ? 'Reminder sent ' . e(short_date($order['reminder_sent_at'])) : 'No reminder sent' ?></span>
            </div>
          </div>

          <?php if ($order['notes'] !== ''): ?>
            <div class="note" style="margin-bottom:14px"><strong>Buyer notes:</strong> <?= e($order['notes']) ?></div>
          <?php endif; ?>

          <?php if (in_array($order['status'], ['pending', 'accepted', 'ready'], true)): ?>
            <form method="post" action="<?= e(url('/farmer/orders/' . $order['id'] . '/status')) ?>" class="flex-between" style="gap:10px;align-items:flex-end;border-top:1px dashed var(--line);padding-top:14px">
              <?= csrf_field() ?>
              <div style="flex:1;min-width:200px">
                <label for="note-<?= (int)$order['id'] ?>" style="font-size:.74rem">Note to the buyer (optional)</label>
                <input id="note-<?= (int)$order['id'] ?>" name="note" maxlength="250" placeholder="e.g. Packed in two crates, call when you arrive">
              </div>
              <div class="table-actions">
                <?php if ($order['status'] === 'pending'): ?>
                  <button class="btn btn-primary btn-sm" type="submit" name="action" value="accept">Accept order</button>
                  <button class="btn btn-danger btn-sm" type="submit" name="action" value="reject" data-confirm="Reject this order? The stock will be returned to your listing.">Reject</button>
                <?php elseif ($order['status'] === 'accepted'): ?>
                  <button class="btn btn-gold btn-sm" type="submit" name="action" value="ready">Mark ready</button>
                <?php elseif ($order['status'] === 'ready'): ?>
                  <button class="btn btn-primary btn-sm" type="submit" name="action" value="complete">Mark completed</button>
                <?php endif; ?>
              </div>
            </form>
          <?php elseif ($order['status'] === 'completed'): ?>
            <p class="small muted" style="margin:0">Completed <?= e(short_datetime($order['completed_at'])) ?>. Value counted in your settled earnings.</p>
          <?php else: ?>
            <p class="small muted" style="margin:0">This order was <?= e($order['status']) ?>. The quantity was returned to your listing automatically.</p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
