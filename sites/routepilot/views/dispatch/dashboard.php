<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Dispatch board</h1>
        <p><?= e(date('l, F j, Y')) ?> · live parcel flow across Lagos.</p>
      </div>
      <div class="row">
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/dispatch/shipments')) ?>">All shipments</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/dispatch/shipments/new')) ?>">+ New shipment</a>
      </div>
    </div>

    <div class="kpi-row">
      <div class="kpi kpi-cyan">
        <span>Active shipments</span>
        <strong><?= (int)$stats['active'] ?></strong>
        <small><?= (int)$stats['unassigned'] ?> still waiting for a rider</small>
      </div>
      <div class="kpi">
        <span>Delivered today</span>
        <strong><?= (int)$stats['delivered_today'] ?></strong>
        <small><?= money((float)$stats['revenue_month']) ?> collected this month</small>
      </div>
      <div class="kpi kpi-amber">
        <span>Flagged by automation</span>
        <strong><?= (int)$stats['flagged'] ?></strong>
        <small>Late pickups &amp; delayed parcels</small>
      </div>
      <div class="kpi <?= (int)$stats['unassigned'] > 0 ? 'kpi-alert' : '' ?>">
        <span>Unassigned queue</span>
        <strong><?= (int)$stats['unassigned'] ?></strong>
        <small>Assign to keep the promise date</small>
      </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:20px">
      <div class="panel" style="margin:0">
        <div class="panel-head">
          <div><h2>Bookings this week</h2><p class="sub">New shipments created per day</p></div>
          <span class="tag"><?= array_sum(array_column($series, 'count')) ?> total</span>
        </div>
        <?php $max = max(1, ...array_column($series, 'count')); ?>
        <div class="chart">
          <?php foreach ($series as $day): ?>
            <div class="chart-bar">
              <i style="height:<?= max(6, (int)round($day['count'] / $max * 100)) ?>%"><span><?= (int)$day['count'] ?></span></i>
              <b><?= e($day['label']) ?></b>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel" style="margin:0">
        <div class="panel-head">
          <div><h2>Courier workload</h2><p class="sub">Open parcels vs. daily capacity</p></div>
          <a class="btn btn-ghost btn-xs" href="<?= e(url('/dispatch/couriers')) ?>">Fleet</a>
        </div>
        <?php if (!$workload): ?>
          <div class="empty"><strong>No couriers yet</strong>Add riders from the admin console to start assigning.</div>
        <?php else: ?>
          <div class="workload">
            <?php foreach ($workload as $courier):
                $capacity = max(1, (int)$courier['max_daily_shipments']);
                $load = min(100, (int)round((int)$courier['open_shipments'] / $capacity * 100));
            ?>
              <div class="workload-row">
                <div>
                  <strong><?= e($courier['name']) ?> <?= (int)$courier['is_available'] === 1 ? '' : '<span class="tag">Off duty</span>' ?></strong>
                  <span><?= e(ucfirst($courier['vehicle'])) ?> · <?= e($courier['zone']) ?> · <?= (int)$courier['delivered_today'] ?> delivered today</span>
                </div>
                <div>
                  <div class="meter"><i style="width:<?= $load ?>%"></i></div>
                  <span style="font-size:.7rem;color:var(--text-muted)"><?= (int)$courier['open_shipments'] ?>/<?= $capacity ?> parcels</span>
                </div>
                <span class="pill <?= $load >= 90 ? 'pill-near' : ($load > 0 ? 'pill-moving' : 'pill-draft') ?>"><?= $load ?>%</span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($alerts): ?>
      <div class="panel" style="border-color:rgba(245,165,36,.42)">
        <div class="panel-head">
          <div><h2>Needs attention</h2><p class="sub">Flagged automatically by RoutePilot automation</p></div>
          <span class="pill pill-near"><?= count($alerts) ?> flagged</span>
        </div>
        <div class="stack">
          <?php foreach ($alerts as $alert): ?>
            <div class="row-between" style="border-bottom:1px solid var(--border);padding-bottom:10px">
              <div>
                <a class="code-chip" href="<?= e(url('/dispatch/shipments/' . $alert['id'])) ?>"><?= e($alert['tracking_code']) ?></a>
                <span class="small muted" style="margin-left:8px"><?= e($alert['origin']) ?> → <?= e($alert['destination']) ?></span>
              </div>
              <div class="row">
                <?php if ($alert['flagged_late_pickup_at']): ?><span class="tag">Late pickup</span><?php endif; ?>
                <?php if ($alert['flagged_delayed_at']): ?><span class="tag">Delayed in transit</span><?php endif; ?>
                <?= rp_pill($alert['status']) ?>
                <a class="btn btn-ghost btn-xs" href="<?= e(url('/dispatch/shipments/' . $alert['id'])) ?>">Resolve</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="panel panel-flush">
      <div class="panel-head">
        <div><h2>Active shipments</h2><p class="sub">Ordered by urgency — final-leg parcels first</p></div>
        <a class="btn btn-ghost btn-xs" href="<?= e(url('/dispatch/shipments')) ?>">Filter all</a>
      </div>
      <?php if (!$board): ?>
        <div style="padding:0 24px 24px"><div class="empty"><strong>The board is clear</strong>No open shipments. Create one to get started.</div></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead>
              <tr><th>Tracking</th><th>Route</th><th>Service</th><th>Courier</th><th>Pickup</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($board as $row): ?>
              <tr>
                <td>
                  <a class="code-chip" href="<?= e(url('/dispatch/shipments/' . $row['id'])) ?>"><?= e($row['tracking_code']) ?></a>
                  <span class="cell-sub"><?= e(rp_time_ago($row['created_at'])) ?></span>
                </td>
                <td>
                  <span class="cell-strong"><?= e($row['origin']) ?></span>
                  <span class="cell-sub">→ <?= e($row['destination']) ?></span>
                </td>
                <td><?= e(rp_service_label($row['service'])) ?><span class="cell-sub"><?= money((float)$row['fee']) ?></span></td>
                <td>
                  <?php if ($row['courier_id']): ?>
                    <?= e(rp_courier_display_name($row)) ?>
                    <span class="cell-sub"><?= e((string)$row['courier_zone']) ?></span>
                  <?php else: ?>
                    <form method="post" action="<?= e(url('/dispatch/shipments/' . $row['id'] . '/assign')) ?>" class="table-actions">
                      <?= csrf_field() ?>
                      <select name="courier_id" aria-label="Assign courier" style="min-width:150px;padding:6px 10px;font-size:.8rem" required>
                        <option value="">Assign rider…</option>
                        <?php foreach ($workload as $courier): ?>
                          <?php if ((int)$courier['is_available'] === 1): ?>
                            <option value="<?= (int)$courier['id'] ?>"><?= e($courier['name']) ?> (<?= (int)$courier['open_shipments'] ?>)</option>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </select>
                      <button class="btn btn-primary btn-xs" type="submit">Assign</button>
                    </form>
                  <?php endif; ?>
                </td>
                <td><?= e(short_date($row['pickup_date'])) ?></td>
                <td><?= rp_pill($row['status'], rp_is_open($row['status']) ? 'pill-live' : '') ?></td>
                <td class="num"><a class="btn btn-ghost btn-xs" href="<?= e(url('/dispatch/shipments/' . $row['id'])) ?>">Open</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
