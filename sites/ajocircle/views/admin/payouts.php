<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Payouts</h1>
        <p>One payout per round, in the fixed order members joined. Confirm a payout once the transfer has left your account.</p>
      </div>
      <span class="badge <?= $dueCount > 0 ? 'badge-bad' : 'badge-ok' ?>"><?= (int)$dueCount ?> due now</span>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px">
      <div class="stat stat-gold"><strong><?= money($scheduledTotal) ?></strong><span>Scheduled to be paid out</span></div>
      <div class="stat stat-accent"><strong><?= money($paidTotal) ?></strong><span>Already paid out</span></div>
      <div class="stat"><strong><?= count($payouts) ?></strong><span>Payout records</span></div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div class="tab-links">
          <a class="<?= $status === '' ? 'active' : '' ?>" href="<?= e(url('/admin/payouts')) ?>">All</a>
          <a class="<?= $status === 'scheduled' ? 'active' : '' ?>" href="<?= e(url('/admin/payouts?status=scheduled')) ?>">Scheduled</a>
          <a class="<?= $status === 'paid' ? 'active' : '' ?>" href="<?= e(url('/admin/payouts?status=paid')) ?>">Paid</a>
        </div>
        <span class="badge badge-neutral"><?= count($payouts) ?> shown</span>
      </div>

      <?php if (!$payouts): ?>
        <div class="empty"><strong>No payouts yet</strong>Open an active circle to schedule its next payout.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Reference</th><th>Circle</th><th>Round</th><th>Recipient</th><th>Scheduled</th><th class="right">Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($payouts as $payout): ?>
              <?php $overduePayout = $payout['status'] === 'scheduled' && $payout['scheduled_date'] <= date('Y-m-d'); ?>
              <tr>
                <td class="mono"><?= e($payout['reference']) ?></td>
                <td><?= e($payout['circle_name']) ?></td>
                <td>#<?= (int)$payout['round_no'] ?></td>
                <td><?= e($payout['recipient_name']) ?><span class="sub"><?= e($payout['recipient_email']) ?></span></td>
                <td class="nowrap">
                  <?= e(short_date($payout['scheduled_date'])) ?>
                  <?php if ($overduePayout): ?><span class="sub" style="color:var(--rose)">flagged due</span><?php endif; ?>
                </td>
                <td class="right num"><?= money((float)$payout['amount']) ?></td>
                <td><?= pill($payout['status']) ?></td>
                <td class="right">
                  <?php if ($payout['status'] === 'scheduled'): ?>
                    <form method="post" action="<?= e(url('/admin/payouts/' . $payout['id'] . '/paid')) ?>" data-confirm="Confirm <?= e($payout['reference']) ?> as paid?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="paid_on" value="<?= e(date('Y-m-d')) ?>">
                      <button class="btn btn-primary btn-sm" type="submit">Confirm paid</button>
                    </form>
                  <?php else: ?>
                    <span class="sub nowrap"><?= e(short_date($payout['paid_on'])) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Schedule a payout</h2>
        <span class="badge badge-neutral">Fixed order</span>
      </div>
      <?php if (!$circles): ?>
        <div class="empty" style="padding:24px"><strong>No active circle</strong>Activate a circle first, then schedule its payouts.</div>
      <?php else: ?>
        <?php foreach ($circles as $circle): ?>
          <div class="list-row">
            <div class="grow">
              <strong><?= e($circle['name']) ?></strong>
              <span><?= pill($circle['status']) ?> · recipients are paid by position, one round at a time</span>
            </div>
            <a class="btn btn-soft btn-sm" href="<?= e(url('/admin/circles/' . $circle['id'])) ?>">Open circle</a>
          </div>
        <?php endforeach; ?>
        <p class="hint" style="margin-top:12px">Payouts are scheduled from the circle page, where the recipient at each position and the pool amount are shown before you confirm.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
