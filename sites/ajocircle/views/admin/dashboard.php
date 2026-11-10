<?php $max = max(1, ...array_map(static fn (array $d): float => $d['value'], $series)); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Organiser overview</h1>
        <p><?= e(date('l, F j, Y')) ?> · pooled funds, overdue rounds and payouts waiting on you.</p>
      </div>
      <div style="display:flex;gap:9px;flex-wrap:wrap">
        <form method="post" action="<?= e(url('/admin/automation')) ?>" data-confirm="Run all automation tasks now (late sweep, reminders, payout flags, statements, digest)?">
          <?= csrf_field() ?>
          <button class="btn btn-ghost btn-sm" type="submit">Run automation</button>
        </form>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/circles')) ?>">Manage circles</a>
      </div>
    </div>

    <div class="grid grid-4" style="margin-bottom:20px">
      <div class="stat stat-accent"><strong><?= money($stats['pooled']) ?></strong><span>Total pooled (verified)</span></div>
      <div class="stat"><strong><?= (int)$stats['active_circles'] ?></strong><span>Active circles · <?= (int)$stats['open_circles'] ?> open</span></div>
      <div class="stat stat-gold"><strong><?= (int)$stats['overdue'] ?></strong><span>Overdue contributions · <?= money($stats['overdue_value']) ?></span></div>
      <div class="stat"><strong><?= (int)$stats['payouts_due'] ?></strong><span>Payouts due now</span></div>
    </div>

    <div class="grid grid-2">
      <div class="panel">
        <div class="panel-head">
          <h2>Collections · last 7 days</h2>
          <span class="badge badge-ok"><?= money(array_sum(array_column($series, 'value'))) ?></span>
        </div>
        <div class="chart">
          <?php foreach ($series as $day): ?>
            <div class="chart-bar">
              <i style="height:<?= max(5, (int)round($day['value'] / $max * 100)) ?>%"><b><?= $day['value'] > 0 ? money($day['value']) : '' ?></b></i>
              <span><?= e($day['label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="divider"></div>
        <div class="list-row" style="border:0;padding:0">
          <div class="grow">
            <strong><?= (int)$stats['pending_verification'] ?> payment(s) awaiting verification</strong>
            <span>Members submitted a reference; verify to add it to the pool.</span>
          </div>
          <a class="btn btn-soft btn-sm" href="<?= e(url('/admin/contributions?status=submitted')) ?>">Review</a>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h2>Payouts in flight</h2>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/payouts')) ?>">All payouts</a>
        </div>
        <?php if (!$payoutsDue): ?>
          <div class="empty" style="padding:24px"><strong>No payouts scheduled</strong>Schedule a payout from any active circle.</div>
        <?php else: ?>
          <?php foreach ($payoutsDue as $payout): ?>
            <div class="list-row">
              <div class="grow">
                <strong><?= money((float)$payout['amount']) ?> → <?= e($payout['recipient_name']) ?></strong>
                <span><?= e($payout['circle_name']) ?> · round <?= (int)$payout['round_no'] ?> · <?= e(short_date($payout['scheduled_date'])) ?></span>
              </div>
              <form method="post" action="<?= e(url('/admin/payouts/' . $payout['id'] . '/paid')) ?>" data-confirm="Mark this payout as paid?">
                <?= csrf_field() ?>
                <button class="btn btn-soft btn-sm" type="submit">Mark paid</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Verification queue</h2>
        <span class="badge badge-warn"><?= (int)$stats['pending_verification'] ?> waiting</span>
      </div>
      <?php if (!$queue): ?>
        <div class="empty" style="padding:24px"><strong>Queue is clear</strong>Every recorded payment has been verified.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Member</th><th>Circle</th><th>Round</th><th class="right">Amount</th><th>Reference</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($queue as $row): ?>
              <tr>
                <td><?= e($row['member_name']) ?></td>
                <td><?= e($row['circle_name']) ?></td>
                <td>#<?= (int)$row['round_no'] ?></td>
                <td class="right num"><?= money((float)$row['amount']) ?></td>
                <td class="mono"><?= e((string)$row['payment_reference']) ?></td>
                <td class="nowrap"><?= e(short_datetime($row['submitted_at'])) ?></td>
                <td class="right">
                  <div class="table-actions">
                    <form method="post" action="<?= e(url('/admin/contributions/' . $row['id'] . '/verify')) ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-primary btn-sm" type="submit">Verify</button>
                    </form>
                    <form method="post" action="<?= e(url('/admin/contributions/' . $row['id'] . '/late')) ?>" data-confirm="Mark this contribution late instead?">
                      <?= csrf_field() ?>
                      <button class="btn btn-danger btn-sm" type="submit">Late</button>
                    </form>
                  </div>
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
        <h2>Overdue contributions</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/contributions?status=late')) ?>">See all</a>
      </div>
      <?php if (!$overdue): ?>
        <div class="empty" style="padding:24px"><strong>Nothing overdue</strong>Every circle is up to date.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Member</th><th>Circle</th><th>Round</th><th>Due date</th><th class="right">Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($overdue as $row): ?>
              <tr>
                <td><?= e($row['member_name']) ?></td>
                <td><?= e($row['circle_name']) ?></td>
                <td>#<?= (int)$row['round_no'] ?></td>
                <td class="nowrap"><?= e(short_date($row['due_date'])) ?></td>
                <td class="right num"><?= money((float)$row['amount']) ?></td>
                <td><?= pill($row['status']) ?></td>
                <td class="right">
                  <?php if (!empty($row['payment_reference'])): ?>
                    <form method="post" action="<?= e(url('/admin/contributions/' . $row['id'] . '/verify')) ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-soft btn-sm" type="submit">Verify</button>
                    </form>
                  <?php else: ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/contributions?circle_id=' . $row['circle_id'] . '&q=' . urlencode($row['member_name']))) ?>">Open</a>
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
        <h2>Circles at a glance</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/circles')) ?>">Manage all</a>
      </div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Circle</th><th>Status</th><th>Members</th><th class="right">Collected</th><th class="right">Paid out</th><th>Starts</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($circles as $circle): ?>
            <tr>
              <td><strong><?= e($circle['name']) ?></strong><span class="sub"><?= money((float)$circle['contribution_amount']) ?> · <?= e(frequency_label($circle['frequency'])) ?></span></td>
              <td><?= pill($circle['status']) ?></td>
              <td><?= (int)$circle['member_count'] ?>/<?= (int)$circle['member_limit'] ?></td>
              <td class="right num"><?= money((float)$circle['collected']) ?></td>
              <td class="right num"><?= money((float)$circle['paid_out']) ?></td>
              <td class="nowrap"><?= e(short_date($circle['start_date'])) ?></td>
              <td class="right"><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/circles/' . $circle['id'])) ?>">Open</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
