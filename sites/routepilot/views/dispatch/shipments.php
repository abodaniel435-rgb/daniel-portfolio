<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Shipments</h1>
        <p><?= (int)$page['total'] ?> parcel<?= (int)$page['total'] === 1 ? '' : 's' ?> match this view · newest first</p>
      </div>
      <a class="btn btn-primary btn-sm" href="<?= e(url('/dispatch/shipments/new')) ?>">+ New shipment</a>
    </div>

    <div class="panel">
      <form class="toolbar" method="get" action="<?= e(url('/dispatch/shipments')) ?>">
        <input class="grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Search code, sender, recipient or destination">
        <select name="status" data-autosubmit aria-label="Filter by status">
          <option value="">All statuses</option>
          <?php foreach (rp_statuses() as $option): ?>
            <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(rp_status_label($option)) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="service" data-autosubmit aria-label="Filter by service">
          <option value="">All services</option>
          <?php foreach (rp_services() as $key => $svc): ?>
            <option value="<?= e($key) ?>" <?= ($_GET['service'] ?? '') === $key ? 'selected' : '' ?>><?= e($svc['label']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="courier" data-autosubmit aria-label="Filter by courier">
          <option value="">All couriers</option>
          <option value="unassigned" <?= $courierFilter === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
          <?php foreach ($couriers as $courier): ?>
            <option value="<?= (int)$courier['id'] ?>" <?= $courierFilter === (string)$courier['id'] ? 'selected' : '' ?>><?= e($courier['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm" type="submit">Apply</button>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/dispatch/shipments')) ?>">Reset</a>
      </form>

      <?php if (!$rows): ?>
        <div class="empty"><strong>No shipments match those filters</strong>Try a different status or clear the search.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead>
              <tr><th>Tracking</th><th>Recipient</th><th>Route</th><th>Service</th><th>Courier</th><th>Pickup</th><th>Status</th><th class="num">Fee</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td>
                  <a class="code-chip" href="<?= e(url('/dispatch/shipments/' . $row['id'])) ?>"><?= e($row['tracking_code']) ?></a>
                  <span class="cell-sub"><?= e(rp_time_ago($row['created_at'])) ?></span>
                </td>
                <td>
                  <span class="cell-strong"><?= e($row['recipient_name']) ?></span>
                  <span class="cell-sub"><?= e($row['recipient_phone']) ?></span>
                </td>
                <td><?= e($row['origin']) ?><span class="cell-sub">→ <?= e($row['destination']) ?></span></td>
                <td><?= e(rp_service_label($row['service'])) ?><span class="cell-sub"><?= e((string)$row['weight_kg']) ?> kg</span></td>
                <td>
                  <?php if ($row['courier_id']): ?>
                    <?= e(rp_courier_display_name($row)) ?>
                    <span class="cell-sub"><?= e((string)$row['courier_zone']) ?></span>
                  <?php else: ?>
                    <span class="tag">Unassigned</span>
                  <?php endif; ?>
                </td>
                <td><?= e(short_date($row['pickup_date'])) ?></td>
                <td><?= rp_pill($row['status'], rp_is_open($row['status']) ? 'pill-live' : '') ?></td>
                <td class="num"><?= money((float)$row['fee']) ?></td>
                <td class="num"><a class="btn btn-ghost btn-xs" href="<?= e(url('/dispatch/shipments/' . $row['id'])) ?>">Open</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($page['pages'] > 1): ?>
          <nav class="pagination" aria-label="Shipment pages">
            <?php
            $query = array_filter(['q' => $q, 'status' => $status, 'service' => $_GET['service'] ?? '', 'courier' => $courierFilter], static fn ($v) => (string)$v !== '');
            for ($i = 1; $i <= $page['pages']; $i++):
                $query['page'] = $i;
            ?>
              <?php if ($i === $page['current']): ?>
                <span class="current"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= e(url('/dispatch/shipments?' . http_build_query($query))) ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
