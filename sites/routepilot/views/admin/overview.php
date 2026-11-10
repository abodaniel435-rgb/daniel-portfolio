<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Admin overview</h1>
        <p><?= e(date('l, F j, Y')) ?> · revenue, people and automation health.</p>
      </div>
      <form method="post" action="<?= e(url('/admin/automation')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <button class="btn btn-primary btn-sm" type="submit">Run automation now</button>
      </form>
    </div>

    <div class="kpi-row">
      <div class="kpi kpi-amber">
        <span>Revenue collected</span>
        <strong><?= money((float)$stats['revenue']) ?></strong>
        <small><?= money((float)$stats['revenue_month']) ?> this month · avg <?= money((float)$stats['avg_fee']) ?></small>
      </div>
      <div class="kpi">
        <span>Shipments</span>
        <strong><?= (int)$stats['shipments'] ?></strong>
        <small><?= (int)$stats['delivered'] ?> delivered · <?= (int)$stats['open'] ?> open · <?= (int)$stats['failed'] ?> failed</small>
      </div>
      <div class="kpi kpi-cyan">
        <span>Active couriers</span>
        <strong><?= (int)$stats['couriers'] ?></strong>
        <small><?= (int)$stats['dispatchers'] ?> dispatcher<?= (int)$stats['dispatchers'] === 1 ? '' : 's' ?> on the desk</small>
      </div>
      <div class="kpi">
        <span>Email outbox</span>
        <strong><?= (int)$stats['queued_mail'] ?></strong>
        <small>Queued messages awaiting delivery</small>
      </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:20px">
      <div class="panel" style="margin:0">
        <div class="panel-head">
          <div><h2>Delivered value · last 7 days</h2><p class="sub">Fees on parcels delivered each day</p></div>
          <a class="btn btn-ghost btn-xs" href="<?= e(url('/admin/shipments')) ?>">Ledger</a>
        </div>
        <div class="chart">
          <?php foreach ($series as $day): ?>
            <div class="chart-bar">
              <i style="height:<?= (int)$day['height'] ?>%"><span><?= $day['value'] > 0 ? '₦' . number_format($day['value'] / 1000, 1) . 'k' : '0' ?></span></i>
              <b><?= e($day['label']) ?></b>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel" style="margin:0">
        <div class="panel-head"><div><h2>Courier leaderboard</h2><p class="sub">Delivered parcels and value handled</p></div></div>
        <?php if (!$leaderboard): ?>
          <div class="empty"><strong>No couriers yet</strong>Create rider accounts from the Users page.</div>
        <?php else: ?>
          <div class="workload">
            <?php foreach ($leaderboard as $index => $courier): ?>
              <div class="workload-row" style="grid-template-columns:28px 1fr auto">
                <strong style="font-family:var(--font-display);color:var(--amber)"><?= $index + 1 ?></strong>
                <div>
                  <strong><?= e($courier['name']) ?></strong>
                  <span><?= e(ucfirst($courier['vehicle'])) ?> · <?= e($courier['zone']) ?></span>
                </div>
                <div style="text-align:right">
                  <strong><?= (int)$courier['delivered'] ?> delivered</strong>
                  <span><?= money((float)$courier['value']) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="grid grid-2">
      <div class="panel" style="margin:0">
        <div class="panel-head"><div><h2>Accounts by role</h2><p class="sub">Access is enforced server-side</p></div></div>
        <div class="stack">
          <?php foreach ($roles as $role): ?>
            <div class="row-between" style="border-bottom:1px solid var(--border);padding-bottom:9px">
              <span class="role-chip"><?= e($role['role']) ?></span>
              <strong><?= (int)$role['total'] ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="hint" style="margin-top:12px">Couriers only ever see parcels assigned to them. Dispatchers and admins share the operational desk.</p>
      </div>

      <div class="panel" style="margin:0">
        <div class="panel-head"><div><h2>Automation log</h2><p class="sub">storage/automation.log · latest entries first</p></div></div>
        <?php if (!$logTail): ?>
          <div class="empty"><strong>No automation runs yet</strong>Use “Run automation now” to execute the scheduled tasks.</div>
        <?php else: ?>
          <div class="log-tail">
            <?php foreach ($logTail as $line): ?><div><?= e($line) ?></div><?php endforeach; ?>
          </div>
          <p class="hint" style="margin-top:10px">Scheduled tasks: late pickups, delayed transit, ETA reminders, daily digest.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
