<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Earnings</h1>
        <p>What has been agreed, what is waiting on a handover, and what is settled.</p>
      </div>
      <a class="btn btn-ghost" href="<?= e(url('/farmer/orders')) ?>">View orders</a>
    </div>

    <div class="stats-grid stats-grid-3">
      <div class="stat stat-accent"><strong><?= e(money($earnings['settled'])) ?></strong><span>Settled on completed orders</span></div>
      <div class="stat"><strong><?= e(money($earnings['committed'])) ?></strong><span>Accepted or ready (incl. completed)</span></div>
      <div class="stat"><strong><?= e(money($earnings['pipeline'])) ?></strong><span>Awaiting your decision</span></div>
    </div>

    <div class="grid grid-2">
      <div class="panel">
        <div class="panel-head"><h2>Completed by produce</h2><span class="badge badge-gold">Settled</span></div>
        <?php if (!$byListing): ?>
          <div class="empty" style="padding:26px"><strong>No settled sales yet</strong>Complete your first handover to see it here.</div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data" style="min-width:420px">
              <thead><tr><th>Produce</th><th>Quantity</th><th>Gross</th></tr></thead>
              <tbody>
                <?php foreach ($byListing as $row): ?>
                  <tr>
                    <td class="strong"><?= e($row['produce_name']) ?></td>
                    <td><?= e(fc_qty((float)$row['quantity'], $row['unit'])) ?></td>
                    <td><?= e(money((float)$row['gross'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="panel">
        <div class="panel-head"><h2>How payouts work</h2></div>
        <div class="prose" style="font-size:.9rem">
          <p>FarmCoop does not hold your money. Buyers pay the agreed amount <strong>on collection or delivery</strong>, and the platform records the value so both sides have the same number.</p>
          <p>An order counts towards <strong>settled earnings</strong> once you mark it completed. Accepted and ready orders are shown separately so you always know what is committed but not yet handed over.</p>
        </div>
        <div class="note" style="margin-top:6px">Rejected or cancelled orders return the quantity to your listing and never appear in earnings.</div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Order ledger</h2><span class="pill-note">Latest 40</span></div>
      <?php if (!$payouts): ?>
        <div class="empty" style="padding:28px"><strong>Nothing to show yet</strong>Accepted orders will appear here with their value and handover date.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Reference</th><th>Produce</th><th>Buyer</th><th>Quantity</th><th>Value</th><th>Status</th><th>Dates</th></tr></thead>
            <tbody>
            <?php foreach ($payouts as $row): ?>
              <tr>
                <td class="mono"><?= e($row['reference']) ?></td>
                <td class="strong"><?= e($row['produce_name']) ?></td>
                <td><?= e($row['buyer_name']) ?></td>
                <td><?= e(fc_qty((float)$row['quantity'], $row['unit'])) ?></td>
                <td class="nowrap"><?= e(money((float)$row['total_amount'])) ?></td>
                <td><?= fc_badge($row['status']) ?></td>
                <td><small class="muted">Ordered <?= e(short_date($row['created_at'])) ?><br><?= $row['completed_at'] ? 'Completed ' . e(short_date($row['completed_at'])) : 'Handover pending' ?></small></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
