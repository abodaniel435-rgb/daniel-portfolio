<?php
$steps = ['pending' => 0, 'accepted' => 1, 'ready' => 2, 'completed' => 3];
$stage = $order ? ($steps[$order['status']] ?? -1) : -1;
$closed = $order && in_array($order['status'], ['rejected', 'cancelled'], true);
$nameParts = $order ? preg_split('/\s+/', trim((string)$order['buyer_name'])) : [];
$buyerShort = $order
    ? ($nameParts[0] ?? 'Buyer') . (isset($nameParts[1]) ? ' ' . mb_substr($nameParts[1], 0, 1) . '.' : '')
    : '';
?>
<section class="section section-narrow">
  <div class="track-shell">
    <p class="eyebrow">Order tracking</p>
    <h1 style="font-size:clamp(1.9rem,4vw,2.8rem)">Where is my produce?</h1>
    <p class="lede">Enter the reference from your order confirmation — it looks like <span class="mono">FC-260107-A1B2</span>. No account needed.</p>

    <form class="track-form" method="post" action="<?= e(url('/track')) ?>">
      <?= csrf_field() ?>
      <div class="form-row" style="align-items:end">
        <div>
          <label for="reference">Order reference</label>
          <input id="reference" name="reference" value="<?= e($reference) ?>" placeholder="FC-260107-A1B2" required autocomplete="off" spellcheck="false">
        </div>
        <div><button class="btn btn-primary btn-block" type="submit">Track this order</button></div>
      </div>
      <?= field_error('reference') ?>
    </form>

    <?php if ($reference !== '' && !$order): ?>
      <div class="panel" style="border-color:#efcdc0">
        <div class="panel-head" style="margin-bottom:8px"><h2>No order found</h2><span class="badge badge-bad">Not found</span></div>
        <p class="muted" style="margin:0">We could not find an order with reference <strong class="mono"><?= e($reference) ?></strong>. Check for typos, or ask the buyer who placed the order to share the exact reference.</p>
      </div>
    <?php endif; ?>

    <?php if ($order): ?>
      <div class="panel" style="margin-top:24px">
        <div class="panel-head">
          <div>
            <span class="eyebrow" style="margin-bottom:6px">Reference <?= e($order['reference']) ?></span>
            <h2 style="margin:0"><?= e($order['produce_name']) ?></h2>
            <p><?= e($order['farm_name'] ?: $order['farmer_name']) ?> · <?= e(fc_qty((float)$order['quantity'], $order['unit'])) ?> · <?= e(money((float)$order['total_amount'])) ?></p>
          </div>
          <?= fc_badge($order['status']) ?>
        </div>

        <?php if ($closed): ?>
          <div class="note" style="border-left-color:var(--clay)">
            This order was <strong><?= e($order['status']) ?></strong> on <?= e(short_datetime($order['updated_at'])) ?>. The produce was returned to the marketplace and no payment is due.
          </div>
        <?php else: ?>
          <div class="timeline">
            <div class="tl-item <?= $stage >= 0 ? 'done' : '' ?>">
              <span class="tl-dot">1</span>
              <div class="tl-body"><strong>Order placed</strong><span>Quantity held on the listing, farmer notified.</span>
                <time><?= e(short_datetime($order['created_at'])) ?></time>
              </div>
            </div>
            <div class="tl-item <?= $stage >= 1 ? 'done' : '' ?>">
              <span class="tl-dot">2</span>
              <div class="tl-body"><strong>Accepted by the farmer</strong><span>The farm committed to your quantity.</span>
                <time><?= $order['accepted_at'] ? e(short_datetime($order['accepted_at'])) : 'Waiting on the farmer' ?></time>
              </div>
            </div>
            <div class="tl-item <?= $stage >= 2 ? 'done' : '' ?>">
              <span class="tl-dot">3</span>
              <div class="tl-body"><strong>Packed and ready</strong><span>Collect within the pickup window or await delivery.</span>
                <time><?= $order['ready_at'] ? e(short_datetime($order['ready_at'])) : 'Not yet packed' ?></time>
              </div>
            </div>
            <div class="tl-item <?= $stage >= 3 ? 'done' : '' ?>">
              <span class="tl-dot">4</span>
              <div class="tl-body"><strong>Completed</strong><span>Produce handed over and the order closed.</span>
                <time><?= $order['completed_at'] ? e(short_datetime($order['completed_at'])) : 'Handover pending' ?></time>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <dl class="kv" style="margin-top:22px">
          <dt>Buyer</dt><dd><?= e($buyerShort) ?></dd>
          <dt>Handover</dt><dd><?= e(ucfirst($order['delivery_method'])) ?> · <?= e(short_date($order['requested_date'])) ?></dd>
          <?php if ($order['delivery_method'] === 'delivery'): ?>
            <dt>Delivery address</dt><dd><?= e($order['delivery_address']) ?></dd>
          <?php else: ?>
            <dt>Pickup point</dt><dd><?= e($order['listing_location'] ?: 'Farm gate') ?></dd>
            <dt>Pickup window</dt><dd><?= e($order['pickup_window'] ?: 'Confirm with the farm') ?></dd>
          <?php endif; ?>
          <dt>Farmer</dt><dd><?= e($order['farmer_name']) ?></dd>
        </dl>

        <?php if ($events): ?>
          <h3 style="font-size:1rem;margin:26px 0 10px">Activity</h3>
          <div class="stack">
            <?php foreach (array_reverse($events) as $event): ?>
              <div class="flex-between" style="border-bottom:1px dashed var(--line);padding-bottom:9px">
                <span style="font-size:.86rem"><?= fc_badge($event['status']) ?> <span class="muted small"><?= e($event['note']) ?></span></span>
                <span class="small muted nowrap"><?= e(short_datetime($event['created_at'])) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="btn-row" style="margin-top:24px">
          <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Sign in for full details</a>
          <a class="btn btn-ghost" href="<?= e(url('/marketplace')) ?>">Browse the market</a>
        </div>
      </div>
    <?php elseif ($reference === ''): ?>
      <div class="card" style="margin-top:24px;display:grid;gap:8px">
        <strong style="font-family:var(--font-display);font-size:1.05rem;color:var(--forest-deep)">What the statuses mean</strong>
        <p class="small muted" style="margin:0"><strong>Pending</strong> — the farmer has your order and is deciding. You can still cancel free of charge.</p>
        <p class="small muted" style="margin:0"><strong>Accepted</strong> — your quantity is committed and being prepared.</p>
        <p class="small muted" style="margin:0"><strong>Ready</strong> — packed and waiting for your pickup window.</p>
        <p class="small muted" style="margin:0"><strong>Completed</strong> — handed over and closed.</p>
      </div>
    <?php endif; ?>
  </div>
</section>
