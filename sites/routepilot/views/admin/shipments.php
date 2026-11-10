<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Shipment ledger</h1>
        <p><?= (int)$page['total'] ?> shipment<?= (int)$page['total'] === 1 ? '' : 's' ?> in this view · full commercial history.</p>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/dispatch/shipments')) ?>">Dispatch view</a>
    </div>

    <div class="kpi-row">
      <div class="kpi kpi-amber"><span>Booked value</span><strong><?= money((float)$totals['fees']) ?></strong><small>All shipments in this view</small></div>
      <div class="kpi kpi-cyan"><span>Collected value</span><strong><?= money((float)$totals['delivered_fees']) ?></strong><small>Only delivered parcels</small></div>
      <div class="kpi"><span>Shipments in view</span><strong><?= (int)$page['total'] ?></strong><small>Filtered ledger</small></div>
      <div class="kpi"><span>Services used</span><strong><?= count($byService) ?></strong><small><?= e(implode(' · ', array_map(static fn (array $s): string => rp_service_label($s['service']) . ' ' . (int)$s['total'], $byService))) ?: 'No bookings in view' ?></small></div>
    </div>

    <div class="panel">
      <form class="toolbar" method="get" action="<?= e(url('/admin/shipments')) ?>">
        <input class="grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Search code, recipient or destination">
        <select name="status" data-autosubmit aria-label="Filter by status">
          <option value="">All statuses</option>
          <?php foreach (rp_statuses() as $option): ?>
            <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(rp_status_label($option)) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="service" data-autosubmit aria-label="Filter by service">
          <option value="">All services</option>
          <?php foreach (rp_services() as $key => $svc): ?>
            <option value="<?= e($key) ?>" <?= $service === $key ? 'selected' : '' ?>><?= e($svc['label']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm" type="submit">Apply</button>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/shipments')) ?>">Reset</a>
      </form>

      <?php if (!$rows): ?>
        <div class="empty"><strong>No shipments match</strong>Adjust the filters to widen the ledger.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Tracking</th><th>Recipient</th><th>Route</th><th>Service</th><th>Courier</th><th class="num">Fee</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><a class="code-chip" href="<?= e(url('/dispatch/shipments/' . $row['id'])) ?>"><?= e($row['tracking_code']) ?></a></td>
                <td><?= e($row['recipient_name']) ?><span class="cell-sub"><?= e($row['recipient_email']) ?></span></td>
                <td><?= e($row['origin']) ?><span class="cell-sub">→ <?= e($row['destination']) ?></span></td>
                <td><?= e(rp_service_label($row['service'])) ?><span class="cell-sub"><?= e((string)$row['weight_kg']) ?> kg</span></td>
                <td><?= e(rp_courier_display_name($row)) ?></td>
                <td class="num"><?= money((float)$row['fee']) ?></td>
                <td><?= rp_pill($row['status']) ?></td>
                <td><?= e(short_date($row['created_at'])) ?><span class="cell-sub"><?= e(rp_time_ago($row['created_at'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($page['pages'] > 1): ?>
          <nav class="pagination" aria-label="Shipment pages">
            <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
              <?php if ($i === $page['current']): ?>
                <span class="current"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= e(url('/admin/shipments?' . http_build_query(array_filter(['q' => $q, 'status' => $status, 'service' => $service, 'page' => $i])))) ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
