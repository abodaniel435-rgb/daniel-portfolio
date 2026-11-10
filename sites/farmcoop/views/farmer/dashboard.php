<?php
$user = Auth::user();
$firstName = explode(' ', trim((string)$user['name']))[0];
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1><?= e($greeting) ?>, <?= e($firstName) ?>.</h1>
        <p><?= e(date('l, F j, Y')) ?> · <?= $profile ? e($profile['farm_name']) . ' · ' . e($profile['state']) : 'Your farm profile is not complete yet' ?></p>
      </div>
      <div class="btn-row">
        <a class="btn btn-ghost" href="<?= e(url('/farmer/orders')) ?>">Incoming orders</a>
        <a class="btn btn-primary" href="<?= e(url('/farmer/listings/new')) ?>">+ New listing</a>
      </div>
    </div>

    <?php if (!$profile): ?>
      <div class="panel" style="border-left:4px solid var(--gold)">
        <div class="panel-head" style="margin-bottom:8px">
          <div>
            <h2>Finish your farm profile</h2>
            <p>Buyers need a farm name, location and produce focus before they can trust your listings.</p>
          </div>
          <a class="btn btn-gold" href="<?= e(url('/farmer/profile')) ?>">Complete profile</a>
        </div>
      </div>
    <?php elseif ((int)$profile['is_verified'] === 0): ?>
      <div class="panel" style="border-left:4px solid var(--gold);padding:18px 22px">
        <div class="flex-between">
          <p style="margin:0;font-size:.9rem"><strong>Verification pending.</strong> Our team reviews new farms within two working days. Verified farms get a badge on every listing.</p>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/farmer/profile')) ?>">Review profile</a>
        </div>
      </div>
    <?php endif; ?>

    <div class="stats-grid">
      <div class="stat"><strong><?= (int)$stats['listings'] ?></strong><span>Live listings</span></div>
      <div class="stat stat-accent"><strong><?= (int)$stats['pending'] ?></strong><span>Orders awaiting your decision</span></div>
      <div class="stat"><strong><?= (int)$stats['ready'] ?></strong><span>Packed &amp; ready for pickup</span></div>
      <div class="stat"><strong><?= (int)$stats['completed'] ?></strong><span>Completed orders</span></div>
    </div>

    <div class="grid grid-2">
      <div class="panel" style="margin-bottom:0">
        <div class="panel-head">
          <div><h2>Orders to action</h2><p>Buyers whose stock is currently held.</p></div>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/farmer/orders')) ?>">All orders</a>
        </div>
        <?php if (!$pending): ?>
          <div class="empty" style="padding:30px"><strong>Nothing waiting on you</strong>New orders appear here the moment a buyer reserves stock.</div>
        <?php else: ?>
          <?php foreach ($pending as $order): ?>
            <div class="order-card no-thumb">
              <div class="order-info">
                <span class="order-ref"><?= e($order['reference']) ?></span>
                <h3><?= e($order['produce_name']) ?></h3>
                <p><?= e($order['buyer_name']) ?> · <?= e(fc_qty((float)$order['quantity'], $order['unit'])) ?> · <?= e(ucfirst($order['delivery_method'])) ?> <?= e(short_date($order['requested_date'])) ?></p>
              </div>
              <div class="order-side">
                <span class="order-amount"><?= e(money((float)$order['total_amount'])) ?></span>
                <?= fc_badge($order['status']) ?>
              </div>
            </div>
          <?php endforeach; ?>
          <p class="hint" style="margin-top:14px">Accept fast — buyers can cancel while an order is pending.</p>
        <?php endif; ?>
      </div>

      <div class="panel" style="margin-bottom:0">
        <div class="panel-head">
          <div><h2>Earnings</h2><p>Cash settled on completed handovers.</p></div>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/farmer/earnings')) ?>">Detail</a>
        </div>
        <div class="stats-grid stats-grid-3" style="margin-bottom:16px">
          <div class="stat"><strong style="font-size:1.35rem"><?= e(money($earnings['settled'])) ?></strong><span>Completed</span></div>
          <div class="stat"><strong style="font-size:1.35rem"><?= e(money($earnings['committed'])) ?></strong><span>Accepted</span></div>
          <div class="stat"><strong style="font-size:1.35rem"><?= e(money($earnings['pipeline'])) ?></strong><span>Pending</span></div>
        </div>
        <h3 style="font-size:1rem;margin:0 0 10px">Latest activity</h3>
        <?php if (!$recentEvents): ?>
          <p class="small muted" style="margin:0">No order activity yet. Publish a harvest to get moving.</p>
        <?php else: ?>
          <div class="stack">
            <?php foreach ($recentEvents as $event): ?>
              <div class="flex-between" style="border-bottom:1px dashed var(--line);padding-bottom:8px">
                <span class="small"><strong><?= e($event['produce_name']) ?></strong><br><span class="muted"><?= e($event['buyer_name']) ?> · <?= e(short_date($event['updated_at'])) ?></span></span>
                <?= fc_badge($event['status']) ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:22px">
      <div class="panel-head">
        <div><h2>Your recent listings</h2><p>Pause, reserve or edit any harvest from the listings page.</p></div>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/farmer/listings')) ?>">Manage listings</a>
      </div>
      <?php if (!$listings): ?>
        <div class="empty" style="padding:30px">
          <strong>No listings yet</strong>
          <p style="margin:0">Publish your first harvest — it takes about two minutes.</p>
          <a class="btn btn-primary" href="<?= e(url('/farmer/listings/new')) ?>">Create a listing</a>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Produce</th><th>Price</th><th>Remaining</th><th>Active orders</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($listings as $row): ?>
              <tr>
                <td class="strong"><?= e($row['produce_name']) ?><br><small class="muted"><?= e(category_label($row['category'])) ?> · <?= e($row['lga']) ?></small></td>
                <td><?= e(money((float)$row['unit_price'])) ?><br><small class="muted">per <?= e($row['unit']) ?></small></td>
                <td><?= e(fc_qty((float)$row['quantity'], $row['unit'])) ?></td>
                <td><?= (int)$row['active_orders'] ?></td>
                <td><?= fc_badge($row['status']) ?></td>
                <td><a class="btn btn-ghost btn-sm" href="<?= e(url('/farmer/listings/' . $row['id'] . '/edit')) ?>">Edit</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
