<?php $site = site_config(); $max = max(1, ...array_column($series, 'count')); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/admin_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Reports</h1><p>Aggregates over real rows, calculated on every request — not a cached snapshot.</p></div>
      <a class="btn btn-quiet" href="<?= e(url('/admin/outbox')) ?>">Delivery log</a>
    </div>

    <div class="admin-stats">
      <div class="stat"><strong><?= (int)array_sum(array_column($statusCounts, 'total')) ?></strong><span>Total requests</span></div>
      <div class="stat"><strong><?= (int)$completed ?></strong><span>Closed / completed</span></div>
      <div class="stat"><strong><?= money_fmt($revenue) ?></strong><span>Collected</span></div>
      <?php if (isset($extras['order_value'])): ?>
        <div class="stat"><strong><?= money_fmt($extras['order_value']) ?></strong><span>Order value</span></div>
      <?php else: ?>
        <div class="stat"><strong><?= (int)($extras['messages'] ?? 0) ?></strong><span>Enquiries</span></div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Volume, last 7 days</h2></div>
      <div class="chart">
        <?php foreach ($series as $day): ?>
          <div class="chart-bar">
            <i style="height:<?= max(5, (int)round($day['count'] / $max * 100)) ?>%"><span><?= (int)$day['count'] ?></span></i>
            <b><?= e($day['label']) ?></b>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="grid-2">
      <div class="panel">
        <div class="panel-head"><h2>Status mix</h2></div>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Status</th><th>Count</th><th>Share</th></tr></thead>
            <tbody>
              <?php $total = max(1, (int)array_sum(array_column($statusCounts, 'total'))); ?>
              <?php foreach ($statusCounts as $row): ?>
                <tr>
                  <td><?= status_badge_html((string)$row['status']) ?></td>
                  <td><?= (int)$row['total'] ?></td>
                  <td><?= number_format((int)$row['total'] / $total * 100, 1) ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="panel">
        <div class="panel-head"><h2>Demand by <?= e(strtolower($site['labels']['choice_a'])) ?></h2></div>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th><?= e($site['labels']['choice_a']) ?></th><th>Requests</th><th>Value</th></tr></thead>
            <tbody>
              <?php foreach ($demand as $row): ?>
                <tr>
                  <td><?= e($row['choice_a']) ?></td>
                  <td><?= (int)$row['total'] ?></td>
                  <td><?= money_fmt((float)$row['value']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$demand): ?><tr><td colspan="3">No data yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($extras): ?>
      <div class="panel">
        <div class="panel-head"><h2>Operations snapshot</h2></div>
        <dl class="detail-grid">
          <?php foreach ($extras as $key => $value): ?>
            <div><dt><?= e(ucwords(str_replace('_', ' ', $key))) ?></dt><dd><?= is_float($value) ? money_fmt($value) : (int)$value ?></dd></div>
          <?php endforeach; ?>
        </dl>
      </div>
    <?php endif; ?>
  </div>
</div>
