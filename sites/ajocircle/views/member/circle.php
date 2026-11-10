<?php
$paidCount = 0;
$lateCount = 0;
foreach ($myContributions as $row) {
    if ($row['status'] === 'paid') {
        $paidCount++;
    } elseif ($row['status'] === 'late') {
        $lateCount++;
    }
}
$savedHere = array_sum(array_map(static fn (array $r): float => $r['status'] === 'paid' ? (float)$r['amount'] : 0, $myContributions));
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <a href="<?= e(url('/member/circles')) ?>" style="font-size:.8rem;color:var(--muted);font-weight:700">← My circles</a>
        <h1 style="margin-top:8px"><?= e($circle['name']) ?></h1>
        <p><?= e($circle['category']) ?> · <?= money((float)$circle['contribution_amount']) ?> <?= e(strtolower(frequency_label($circle['frequency']))) ?> · <?= count($members) ?> members</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <?= pill($circle['status']) ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/circles/' . $circle['id'])) ?>">Public view</a>
      </div>
    </div>

    <div class="stat-row">
      <div class="stat stat-accent"><strong><?= money($savedHere) ?></strong><span>Your verified total</span></div>
      <div class="stat"><strong>#<?= (int)$membership['position'] ?></strong><span>Your payout position</span></div>
      <div class="stat stat-gold"><strong><?= money($position['amount']) ?></strong><span>Expected payout</span></div>
      <div class="stat"><strong><?= e(short_date($position['expected_date'])) ?></strong><span>Payout round date</span></div>
    </div>

    <?php if ($position['payout']): ?>
      <div class="due-card" style="margin-bottom:20px;border-color:#bfe7d3;background:linear-gradient(180deg,#f1fdf8,#fff)">
        <div>
          <span class="badge badge-ok">Your turn</span>
          <div class="when" style="margin-top:8px"><?= money((float)$position['payout']['amount']) ?></div>
          <p class="what">
            Round <?= (int)$position['payout']['round_no'] ?> payout
            <?= $position['payout']['status'] === 'paid'
                ? 'paid on ' . e(short_date($position['payout']['paid_on']))
                : 'scheduled for ' . e(short_date($position['payout']['scheduled_date'])) ?>
            · ref <?= e($position['payout']['reference']) ?>
          </p>
        </div>
        <?= pill($position['payout']['status']) ?>
      </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head">
        <h2>My contributions</h2>
        <span class="badge badge-neutral"><?= $paidCount ?> paid · <?= $lateCount ?> late</span>
      </div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Round</th><th>Due date</th><th class="right">Amount</th><th>Status</th><th>Reference</th><th>Recorded</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($myContributions as $row): ?>
            <tr>
              <td><strong>#<?= (int)$row['round_no'] ?></strong></td>
              <td class="nowrap"><?= e(short_date($row['due_date'])) ?></td>
              <td class="right num"><?= money((float)$row['amount']) ?></td>
              <td><?= pill($row['status']) ?></td>
              <td class="mono"><?= $row['payment_reference'] ? e($row['payment_reference']) : '<span style="color:var(--muted)">—</span>' ?></td>
              <td class="nowrap"><?= e(short_date($row['submitted_at'])) ?></td>
              <td class="right">
                <?php if ($row['status'] !== 'paid' && empty($row['payment_reference'])): ?>
                  <a class="btn btn-soft btn-sm" href="<?= e(url('/member/contributions?circle_id=' . $circle['id'] . '&pay=' . $row['id'] . '#pay')) ?>">Record</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Rotation ledger</h2>
        <span class="badge badge-neutral">Pool per round <?= money((float)$circle['contribution_amount'] * max(1, count($members))) ?></span>
      </div>
      <div class="ledger">
        <?php foreach ($rounds as $round): ?>
          <div class="ledger-row <?= $round['state'] === 'paid_out' || $round['state'] === 'collected' ? 'done' : ($round['state'] === 'overdue' ? 'overdue' : '') ?>">
            <span class="round"><?= (int)$round['round_no'] ?></span>
            <div class="who">
              <strong><?= $round['recipient'] ? e($round['recipient']['name']) : 'Slot open' ?></strong>
              <span>Due <?= e(short_date($round['due_date'])) ?> · <?= (int)$round['paid_count'] ?>/<?= (int)$round['member_count'] ?> contributions verified</span>
            </div>
            <div class="bar"><i style="width:<?= (int)$round['percent'] ?>%"></i></div>
            <div class="amt"><?= money($round['expected']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Members</h2><span class="badge badge-info"><?= count($members) ?> on the roster</span></div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Position</th><th>Member</th><th>Payout round</th><th>Phone</th><th>Joined</th></tr></thead>
          <tbody>
          <?php foreach ($members as $member): ?>
            <tr>
              <td><strong>#<?= (int)$member['position'] ?></strong><?= (int)$member['position'] === (int)$membership['position'] ? ' <span class="badge badge-ok">You</span>' : '' ?></td>
              <td><?= e($member['name']) ?></td>
              <td>Round <?= (int)$member['position'] ?> · <?= e(short_date(round_due_date($circle['start_date'], $circle['frequency'], (int)$member['position']))) ?></td>
              <td class="mono"><?= e($member['phone'] ?: '—') ?></td>
              <td class="nowrap"><?= e(short_date($member['joined_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
