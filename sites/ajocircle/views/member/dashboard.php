<?php
$scheduled = $totals['saved'] + $totals['outstanding'];
$percent = $scheduled > 0 ? (int)round($totals['saved'] / $scheduled * 100) : 0;
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', trim($user['name']))[0];
$dueIn = $nextDue ? (int)floor((strtotime($nextDue['due_date']) - strtotime(date('Y-m-d'))) / 86400) : null;
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1><?= e($greeting) ?>, <?= e($firstName) ?></h1>
        <p><?= e(date('l, F j, Y')) ?> · your circle position, dues and payouts at a glance.</p>
      </div>
      <div style="display:flex;gap:9px;flex-wrap:wrap">
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/member/statement')) ?>">Statement</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/member/circles')) ?>">Find a circle</a>
      </div>
    </div>

    <div class="balance-card">
      <div>
        <span class="lbl">Total saved across circles</span>
        <div class="big"><?= money($totals['saved']) ?></div>
        <div class="split-stats">
          <div><span>This month</span><strong><?= money($monthSaved) ?></strong></div>
          <div><span>Outstanding</span><strong><?= money($totals['outstanding']) ?></strong></div>
          <div><span>Received in payouts</span><strong><?= money($totals['received']) ?></strong></div>
        </div>
      </div>
      <div class="ring" style="--p:<?= $percent ?>">
        <span><?= $percent ?>%</span>
      </div>
    </div>

    <?php if ($nextDue): ?>
      <div class="due-card" style="margin-bottom:20px">
        <div>
          <span class="badge <?= $nextDue['status'] === 'late' || $dueIn < 0 ? 'badge-bad' : 'badge-warn' ?>">
            <?= $nextDue['status'] === 'late' || $dueIn < 0 ? 'Overdue' : ($dueIn === 0 ? 'Due today' : 'Due in ' . $dueIn . ' days') ?>
          </span>
          <div class="when" style="margin-top:8px"><?= money((float)$nextDue['amount']) ?></div>
          <p class="what"><?= e($nextDue['circle_name']) ?> · round <?= (int)$nextDue['round_no'] ?> · <?= e(short_date($nextDue['due_date'])) ?></p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('/member/contributions?circle_id=' . $nextDue['circle_id'])) ?>">
          <?= empty($nextDue['payment_reference']) ? 'Record payment' : 'Awaiting verification' ?>
        </a>
      </div>
    <?php else: ?>
      <div class="due-card" style="margin-bottom:20px;border-color:var(--emerald-soft);background:linear-gradient(180deg,#f1fdf8,#fff)">
        <div>
          <span class="badge badge-ok">All clear</span>
          <div class="when" style="margin-top:8px;font-size:1.1rem">No contribution is due right now.</div>
          <p class="what">Join another circle, or review your statement for the full history.</p>
        </div>
        <a class="btn btn-ghost" href="<?= e(url('/member/statement')) ?>">Open statement</a>
      </div>
    <?php endif; ?>

    <div class="grid grid-2">
      <div class="panel">
        <div class="panel-head">
          <h2>My circles</h2>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/member/circles')) ?>">View all</a>
        </div>
        <?php if (!$circles): ?>
          <div class="empty"><strong>No circles yet</strong>Join an open circle to start your ledger.</div>
        <?php else: ?>
          <?php foreach (array_slice($circles, 0, 4) as $circle): ?>
            <?php
            $count = max(1, (int)$circle['member_count']);
            $share = (int)round((float)$circle['my_saved'] / max(1, (float)$circle['contribution_amount'] * (int)$circle['member_limit']) * 100);
            ?>
            <div class="list-row">
              <div class="grow">
                <strong><a href="<?= e(url('/member/circles/' . $circle['id'])) ?>" style="color:inherit"><?= e($circle['name']) ?></a></strong>
                <span><?= money((float)$circle['contribution_amount']) ?> <?= e(strtolower(frequency_label($circle['frequency']))) ?> · position #<?= (int)$circle['position'] ?> · <?= (int)$circle['member_count'] ?> members</span>
                <div class="bar" style="margin-top:7px"><i style="width:<?= min(100, $share) ?>%"></i></div>
              </div>
              <?= pill($circle['status']) ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="panel">
        <div class="panel-head"><h2>Payouts</h2></div>
        <?php if ($nextPayout): ?>
          <div class="note" style="margin-bottom:14px">
            Next payout of <strong><?= money((float)$nextPayout['amount']) ?></strong> from
            <strong><?= e($nextPayout['circle_name']) ?></strong> is scheduled for
            <strong><?= e(short_date($nextPayout['scheduled_date'])) ?></strong>.
          </div>
        <?php endif; ?>

        <?php if (!$payouts): ?>
          <div class="empty" style="padding:24px"><strong>No payout yet</strong>When your position comes up, the payout appears here.</div>
        <?php else: ?>
          <?php foreach ($payouts as $payout): ?>
            <div class="list-row">
              <div class="grow">
                <strong><?= money((float)$payout['amount']) ?> · <?= e($payout['circle_name']) ?></strong>
                <span>Round <?= (int)$payout['round_no'] ?> · <?= $payout['status'] === 'paid' ? 'paid ' . e(short_date($payout['paid_on'])) : 'scheduled ' . e(short_date($payout['scheduled_date'])) ?></span>
              </div>
              <?= pill($payout['status']) ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($turns): ?>
          <div class="divider"></div>
          <p style="font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin:0 0 8px">Upcoming turns</p>
          <?php foreach ($turns as $circle): ?>
            <div class="list-row">
              <div class="grow">
                <strong><?= e($circle['name']) ?></strong>
                <span>Position #<?= (int)$circle['turn']['position'] ?> · expected <?= e(short_date($circle['turn']['expected_date'])) ?></span>
              </div>
              <span class="num" style="font-size:.86rem"><?= money((float)$circle['turn']['amount']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Recent contributions</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/member/contributions')) ?>">Full history</a>
      </div>
      <?php if (!$recent): ?>
        <div class="empty"><strong>Nothing recorded yet</strong>Your contributions will appear here as you pay.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Circle</th><th>Round</th><th>Due date</th><th class="right">Amount</th><th>Reference</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $row): ?>
              <tr>
                <td><?= e($row['circle_name']) ?></td>
                <td>#<?= (int)$row['round_no'] ?></td>
                <td class="nowrap"><?= e(short_date($row['due_date'])) ?></td>
                <td class="right num"><?= money((float)$row['amount']) ?></td>
                <td class="mono"><?= $row['payment_reference'] ? e($row['payment_reference']) : '<span style="color:var(--muted)">—</span>' ?></td>
                <td><?= pill($row['status']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
