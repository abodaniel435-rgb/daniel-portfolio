<?php
$slotsLeft = max(0, (int)$circle['member_limit'] - count($members));
$pool = (float)$circle['contribution_amount'] * max(1, count($members));
$nextRound = null;
foreach ($rounds as $round) {
    if (in_array($round['state'], ['due', 'overdue', 'upcoming'], true) && !$round['payout']) {
        $nextRound = $round;
        break;
    }
}
$currentUser = Auth::user();
?>
<section class="section" style="padding-bottom:30px">
  <a href="<?= e(url('/circles')) ?>" style="font-size:.82rem;color:var(--muted);font-weight:700">← All circles</a>

  <div class="page-head" style="margin:18px 0 24px;align-items:flex-start">
    <div style="max-width:62ch">
      <p class="eyebrow" style="margin-bottom:10px"><?= e($circle['category']) ?> · <?= e(frequency_label($circle['frequency'])) ?> circle</p>
      <h1 style="font-size:clamp(1.8rem,3.4vw,2.6rem);margin:0 0 10px"><?= e($circle['name']) ?></h1>
      <p style="color:var(--muted);margin:0"><?= e($circle['description']) ?></p>
      <div class="meta-row" style="margin-top:16px">
        <?= pill($circle['status']) ?>
        <span>Fixed payout order</span>
        <span>Starts <?= e(short_date($circle['start_date'])) ?></span>
        <span><?= (int)$circle['member_limit'] ?> member limit</span>
      </div>
    </div>
  </div>

  <div class="stat-row">
    <div class="stat stat-accent"><strong><?= money((float)$circle['contribution_amount']) ?></strong><span>Per member, <?= e(strtolower(frequency_label($circle['frequency']))) ?></span></div>
    <div class="stat"><strong><?= count($members) ?>/<?= (int)$circle['member_limit'] ?></strong><span>Members joined</span></div>
    <div class="stat stat-gold"><strong><?= money($pool) ?></strong><span>Pool per round</span></div>
    <div class="stat"><strong><?= money($totals['paid']) ?></strong><span>Verified so far</span></div>
  </div>
</section>

<section class="section" style="padding-top:12px">
  <div class="split split-ledger">
    <div>
      <div class="panel">
        <div class="panel-head">
          <h2>Rotation ledger</h2>
          <span class="badge badge-neutral"><?= (int)$circle['member_limit'] ?> rounds</span>
        </div>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Round</th><th>Due</th><th>Recipient</th><th>Collected</th><th class="right">Pool</th><th>State</th></tr></thead>
            <tbody>
            <?php foreach ($rounds as $round): ?>
              <tr>
                <td><strong>#<?= (int)$round['round_no'] ?></strong></td>
                <td class="nowrap"><?= e(short_date($round['due_date'])) ?></td>
                <td>
                  <?php if ($round['recipient']): ?>
                    <?= e($round['recipient']['name']) ?>
                    <span class="sub">Position <?= (int)$round['recipient']['position'] ?></span>
                  <?php else: ?>
                    <span style="color:var(--muted)">Slot open</span>
                  <?php endif; ?>
                </td>
                <td style="min-width:130px">
                  <div class="bar-label" style="margin-bottom:4px"><span><?= (int)$round['paid_count'] ?>/<?= (int)$round['member_count'] ?></span><span><?= money($round['paid_amount']) ?></span></div>
                  <div class="bar"><i style="width:<?= (int)$round['percent'] ?>%"></i></div>
                </td>
                <td class="right num"><?= money($round['expected']) ?></td>
                <td><?= pill($round['state'] === 'collected' ? 'paid' : $round['state']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="hint" style="margin-top:12px">Each round pays the member holding that position. Contribution due dates are generated from the circle start date and frequency — they cannot be edited after the fact.</p>
      </div>

      <div class="panel">
        <div class="panel-head"><h2>Members and positions</h2><span class="badge badge-info"><?= count($members) ?> on the roster</span></div>
        <?php if (!$members): ?>
          <div class="empty"><strong>No members yet</strong>Be the first to take a position in this circle.</div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data">
              <thead><tr><th>Position</th><th>Member</th><th>Payout round</th><th>Expected payout date</th><th>Joined</th></tr></thead>
              <tbody>
              <?php foreach ($members as $member): ?>
                <tr>
                  <td><strong>#<?= (int)$member['position'] ?></strong></td>
                  <td><?= e($member['name']) ?><span class="sub"><?= e($member['email']) ?></span></td>
                  <td>Round <?= (int)$member['position'] ?></td>
                  <td><?= e(short_date(round_due_date($circle['start_date'], $circle['frequency'], (int)$member['position']))) ?></td>
                  <td class="nowrap"><?= e(short_date($member['joined_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <aside>
      <div class="card" style="position:sticky;top:88px">
        <p class="eyebrow" style="margin-bottom:8px">Membership</p>
        <h3 style="font-size:1.35rem;margin-bottom:2px"><?= money((float)$circle['contribution_amount']) ?></h3>
        <p style="font-size:.85rem;color:var(--muted);margin:0 0 16px"><?= e(ucfirst($circle['frequency'])) ?> contribution · <?= (int)$circle['member_limit'] ?> rounds</p>

        <dl class="kv" style="margin-bottom:18px">
          <dt>Status</dt><dd><?= pill($circle['status']) ?></dd>
          <dt>Slots left</dt><dd><?= $slotsLeft ?></dd>
          <dt>Next due</dt><dd><?= $nextRound ? e(short_date($nextRound['due_date'])) : '—' ?></dd>
          <dt>Payout mode</dt><dd>Fixed order</dd>
        </dl>

        <?php if ($membership && $membership['status'] === 'active'): ?>
          <div class="note" style="margin-bottom:14px">
            You hold position <strong>#<?= (int)$membership['position'] ?></strong> here.
            Your payout is expected <?= e(short_date(round_due_date($circle['start_date'], $circle['frequency'], (int)$membership['position']))) ?>.
          </div>
          <a class="btn btn-primary btn-block" href="<?= e(url('/member/circles/' . $circle['id'])) ?>">Open my ledger</a>
        <?php elseif ($circle['status'] === 'completed'): ?>
          <button class="btn btn-ghost btn-block" type="button" disabled>Circle completed</button>
        <?php elseif ($slotsLeft === 0): ?>
          <button class="btn btn-ghost btn-block" type="button" disabled>Circle is full</button>
        <?php elseif (!$currentUser): ?>
          <a class="btn btn-primary btn-block" href="<?= e(url('/login')) ?>">Sign in to join</a>
          <p class="hint" style="text-align:center;margin-top:10px">New here? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
        <?php elseif ($currentUser['role'] === 'admin'): ?>
          <a class="btn btn-dark btn-block" href="<?= e(url('/admin/circles/' . $circle['id'])) ?>">Manage as organiser</a>
          <p class="hint" style="text-align:center;margin-top:10px">Administrator accounts manage circles rather than join them.</p>
        <?php else: ?>
          <form method="post" action="<?= e(url('/circles/' . $circle['id'] . '/join')) ?>" data-confirm="Join <?= e($circle['name']) ?>? Your position will be #<?= count($members) + 1 ?> and cannot be changed.">
            <?= csrf_field() ?>
            <button class="btn btn-primary btn-block" type="submit">Join at position #<?= count($members) + 1 ?></button>
          </form>
          <p class="hint" style="text-align:center;margin-top:10px">Joining generates your full contribution schedule.</p>
        <?php endif; ?>

        <div class="divider"></div>
        <div class="list-row" style="border:0;padding:0">
          <div class="grow">
            <strong>Verified pool</strong>
            <span><?= money($totals['paid']) ?> recorded · <?= money($totals['outstanding']) ?> outstanding</span>
          </div>
        </div>
      </div>
    </aside>
  </div>
</section>
