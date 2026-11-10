<?php $site = site_config(); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/admin_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1><?= e($site['labels']['admin']) ?></h1>
        <p><?= e(date('l, F j, Y')) ?> · live numbers from the database, no cached dashboards.</p>
      </div>
      <a class="btn btn-quiet" href="<?= e(url('/admin/outbox')) ?>">Email outbox<?= $stats['outbox_failed'] ? ' · ' . (int)$stats['outbox_failed'] . ' failed' : '' ?></a>
    </div>

    <div class="admin-stats">
      <div class="stat"><strong><?= (int)$stats['total'] ?></strong><span>Total <?= e($site['labels']['txn_plural']) ?></span></div>
      <div class="stat"><strong><?= (int)$stats['open'] ?></strong><span>Open / awaiting action</span></div>
      <div class="stat"><strong><?= (int)$stats['today'] ?></strong><span>Scheduled today</span></div>
      <?php if (cfg('modules.payments', false)): ?>
        <div class="stat"><strong><?= money_fmt($stats['revenue'] ?? 0) ?></strong><span>Payments recorded</span></div>
      <?php else: ?>
        <div class="stat"><strong><?= (int)$stats['customers'] ?></strong><span>Registered <?= e($site['labels']['customer_plural']) ?></span></div>
      <?php endif; ?>
    </div>

    <?php if (cfg('modules.orders', false) || cfg('modules.messaging', false)): ?>
      <div class="module-strip">
        <?php if (cfg('modules.orders', false)): ?>
          <a class="module-card" href="<?= e(url('/admin/orders')) ?>">
            <strong><?= (int)($stats['orders'] ?? 0) ?></strong>
            <span>orders</span>
            <?php if (($stats['low_stock'] ?? 0) > 0): ?><em><?= (int)$stats['low_stock'] ?> low stock</em><?php endif; ?>
          </a>
        <?php endif; ?>
        <?php if (cfg('modules.messaging', false)): ?>
          <a class="module-card" href="<?= e(url('/admin/messages')) ?>">
            <strong><?= (int)($stats['new_messages'] ?? 0) ?></strong>
            <span>new enquiries</span>
          </a>
        <?php endif; ?>
        <a class="module-card" href="<?= e(url('/admin/users')) ?>">
          <strong><?= (int)$stats['customers'] ?></strong>
          <span><?= e($site['labels']['customer_plural']) ?></span>
        </a>
      </div>
    <?php endif; ?>

    <div class="grid-2">
      <div class="panel">
        <div class="panel-head"><h2>Last 7 days</h2><span class="badge badge-neutral"><?= array_sum(array_column($series, 'count')) ?> requests</span></div>
        <?php $max = max(1, ...array_column($series, 'count')); ?>
        <div class="chart">
          <?php foreach ($series as $day): ?>
            <div class="chart-bar">
              <i style="height:<?= max(5, (int)round($day['count'] / $max * 100)) ?>%"><span><?= (int)$day['count'] ?></span></i>
              <b><?= e($day['label']) ?></b>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h2>Automation</h2><span class="badge badge-neutral">cPanel cron</span></div>
        <?php if (!$log): ?>
          <div class="empty-state"><strong>No runs logged yet.</strong><p>“Run automation now” writes to storage/automation.log.</p></div>
        <?php else: ?>
          <ul class="log-list">
            <?php foreach ($log as $line): ?>
              <li><?= e($line) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Today's schedule</h2>
        <a class="btn btn-quiet btn-sm" href="<?= e(url('/admin/records')) ?>">Manage all</a>
      </div>
      <?php if (!$todays): ?>
        <div class="empty-state"><strong>Nothing booked for today.</strong><p>The team can still action open requests from the full list.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Time</th><th>Reference</th><th>Customer</th><th><?= e($site['labels']['choice_a']) ?></th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($todays as $row): ?>
                <tr>
                  <td><?= e(formatted_time($row['request_time'])) ?></td>
                  <td class="mono"><?= e($row['reference']) ?></td>
                  <td><?= e($row['customer_name']) ?></td>
                  <td><?= e($row['choice_a']) ?></td>
                  <td><?= status_badge_html((string)$row['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Recent requests</h2></div>
      <div class="recent-list">
        <?php foreach ($recent as $row): ?>
          <a class="recent-row" href="<?= e(url('/admin/records?q=' . urlencode((string)$row['reference']))) ?>">
            <span class="mono"><?= e($row['reference']) ?></span>
            <strong><?= e($row['customer_name']) ?></strong>
            <span><?= e(short_date($row['request_date'])) ?></span>
            <?= status_badge_html((string)$row['status']) ?>
          </a>
        <?php endforeach; ?>
        <?php if (!$recent): ?>
          <div class="empty-state"><strong>No requests yet.</strong><p>Public bookings appear here immediately.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
