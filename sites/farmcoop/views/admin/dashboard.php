<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Marketplace operations</h1>
        <p><?= e(date('l, F j, Y')) ?> · supply, demand and the order pipeline in one view.</p>
      </div>
      <div class="btn-row">
        <a class="btn btn-ghost" href="<?= e(url('/admin/outbox')) ?>">Email outbox</a>
        <a class="btn btn-primary" href="<?= e(url('/admin/orders')) ?>">All orders</a>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat stat-accent"><strong><?= e(money($stats['gmv'])) ?></strong><span>GMV — completed orders</span></div>
      <div class="stat"><strong><?= e(money($stats['pipeline'])) ?></strong><span>Open pipeline value</span></div>
      <div class="stat"><strong><?= (int)$stats['pending'] ?></strong><span>Orders awaiting farmers</span></div>
      <div class="stat"><strong><?= (int)$stats['listings'] ?></strong><span>Live listings</span></div>
    </div>
    <div class="stats-grid">
      <div class="stat"><strong><?= (int)$stats['farmers'] ?></strong><span>Active farmers</span></div>
      <div class="stat"><strong><?= (int)$stats['buyers'] ?></strong><span>Active buyers</span></div>
      <div class="stat"><strong><?= (int)$stats['orders'] ?></strong><span>Orders all time</span></div>
      <div class="stat"><strong><?= (int)$stats['unverified'] ?></strong><span>Farms awaiting verification</span></div>
    </div>

    <div class="grid grid-2">
      <div class="panel">
        <div class="panel-head">
          <div><h2>Orders, last 7 days</h2><p>Bar height is order volume; hover for value.</p></div>
          <span class="badge badge-info"><?= array_sum(array_column($series, 'count')) ?> orders</span>
        </div>
        <?php $max = max(1, ...array_column($series, 'count')); ?>
        <div class="chart">
          <?php foreach ($series as $day): ?>
            <div class="chart-bar" title="<?= e($day['label'] . ': ' . $day['count'] . ' orders · ' . money((float)$day['value'])) ?>">
              <i style="height:<?= max(6, (int)round($day['count'] / $max * 100)) ?>%"><span><?= (int)$day['count'] ?></span></i>
              <b><?= e($day['label']) ?></b>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <div><h2>Top farms by committed value</h2><p>Accepted, ready and completed orders.</p></div>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/users?role=farmer')) ?>">Farms</a>
        </div>
        <?php if (!$topFarms): ?>
          <div class="empty" style="padding:26px"><strong>No farms yet</strong>Farmer accounts appear here once they list produce.</div>
        <?php else: ?>
          <div class="stack">
            <?php foreach ($topFarms as $farm): ?>
              <div class="flex-between" style="border-bottom:1px dashed var(--line);padding-bottom:10px">
                <div>
                  <strong style="font-size:.9rem;display:block"><?= e($farm['farm_name']) ?></strong>
                  <span class="small muted"><?= e($farm['name']) ?> · <?= e($farm['state']) ?></span>
                </div>
                <div style="text-align:right">
                  <strong style="font-size:.9rem"><?= e(money((float)$farm['value'])) ?></strong><br>
                  <?= (int)$farm['is_verified'] === 1 ? '<span class="badge badge-ok">Verified</span>' : '<span class="badge badge-warn">Pending</span>' ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div><h2>Latest orders</h2><p>The most recent activity across every farm.</p></div>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/orders')) ?>">Manage orders</a>
      </div>
      <?php if (!$recent): ?>
        <div class="empty" style="padding:28px"><strong>No orders yet</strong>They appear the moment a buyer reserves produce.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Reference</th><th>Produce</th><th>Buyer</th><th>Farm</th><th>Value</th><th>Status</th><th>Placed</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $order): ?>
              <tr>
                <td class="mono"><?= e($order['reference']) ?></td>
                <td class="strong"><?= e($order['produce_name']) ?></td>
                <td><?= e($order['buyer_name']) ?></td>
                <td><?= e($order['farm_name'] ?: $order['farmer_name']) ?></td>
                <td class="nowrap"><?= e(money((float)$order['total_amount'])) ?></td>
                <td><?= fc_badge($order['status']) ?></td>
                <td><small class="muted"><?= e(short_datetime($order['created_at'])) ?></small></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><div><h2>Supply mix</h2><p>Live listings per category and the quantity behind them.</p></div></div>
      <?php if (!$categories): ?>
        <p class="small muted" style="margin:0">No live listings right now.</p>
      <?php else: ?>
        <div class="grid grid-3" style="gap:14px">
          <?php foreach ($categories as $row): ?>
            <div class="panel-sub" style="padding:14px 16px">
              <span class="small muted" style="text-transform:uppercase;letter-spacing:.1em;font-size:.66rem;font-weight:700"><?= e(category_label($row['category'])) ?></span>
              <strong style="display:block;font-family:var(--font-display);font-size:1.25rem;color:var(--forest-deep)"><?= (int)$row['listings'] ?> listing<?= (int)$row['listings'] === 1 ? '' : 's' ?></strong>
              <span class="small muted"><?= e(fc_qty((float)$row['quantity'])) ?> units available</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
