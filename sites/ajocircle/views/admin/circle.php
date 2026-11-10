<?php
$memberCount = count(array_filter($members, static fn (array $m): bool => $m['status'] === 'active'));
$pool = (float)$circle['contribution_amount'] * max(1, $memberCount);
$eligibleRounds = array_values(array_filter($rounds, static fn (array $r): bool => !$r['payout'] && $r['recipient'] !== null));
$pendingVerify = array_values(array_filter($contributions, static fn (array $r): bool => $r['status'] !== 'paid' && !empty($r['payment_reference'])));
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <a href="<?= e(url('/admin/circles')) ?>" style="font-size:.8rem;color:var(--muted);font-weight:700">← All circles</a>
        <h1 style="margin-top:8px"><?= e($circle['name']) ?></h1>
        <p><?= e($circle['category']) ?> · <?= money((float)$circle['contribution_amount']) ?> <?= e(strtolower(frequency_label($circle['frequency']))) ?> · <?= $memberCount ?> of <?= (int)$circle['member_limit'] ?> members · starts <?= e(short_date($circle['start_date'])) ?></p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <?= pill($circle['status']) ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/circles/' . $circle['id'])) ?>">Public view</a>
        <?php if ($circle['status'] === 'open'): ?>
          <form method="post" action="<?= e(url('/admin/circles/' . $circle['id'] . '/status')) ?>" data-confirm="Activate this circle and start the rotation?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="activate">
            <button class="btn btn-primary btn-sm" type="submit">Activate rotation</button>
          </form>
        <?php elseif ($circle['status'] === 'active'): ?>
          <form method="post" action="<?= e(url('/admin/circles/' . $circle['id'] . '/status')) ?>" data-confirm="Mark this circle completed? Members will no longer be able to join.">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="complete">
            <button class="btn btn-dark btn-sm" type="submit">Close circle</button>
          </form>
        <?php else: ?>
          <form method="post" action="<?= e(url('/admin/circles/' . $circle['id'] . '/status')) ?>" data-confirm="Reopen this circle?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="open">
            <button class="btn btn-ghost btn-sm" type="submit">Reopen</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="grid grid-4" style="margin-bottom:20px">
      <div class="stat stat-accent"><strong><?= money($totals['paid']) ?></strong><span>Verified contributions</span></div>
      <div class="stat"><strong><?= money($totals['outstanding']) ?></strong><span>Outstanding</span></div>
      <div class="stat stat-gold"><strong><?= money($totals['paid_out']) ?></strong><span>Paid out to members</span></div>
      <div class="stat"><strong><?= money($pool) ?></strong><span>Pool per round</span></div>
    </div>

    <div class="grid grid-2">
      <div class="panel">
        <div class="panel-head">
          <h2>Schedule a payout</h2>
          <span class="badge badge-neutral">One payout per round</span>
        </div>
        <?php if ($circle['status'] !== 'active'): ?>
          <div class="empty" style="padding:24px"><strong>Rotation not running</strong>Activate the circle before scheduling payouts.</div>
        <?php elseif (!$eligibleRounds): ?>
          <div class="empty" style="padding:24px"><strong>Every round has a payout</strong>Nothing left to schedule in this circle.</div>
        <?php else: ?>
          <form method="post" action="<?= e(url('/admin/circles/' . $circle['id'] . '/payout')) ?>" class="form-grid">
            <?= csrf_field() ?>
            <div>
              <label for="round_no">Round / position</label>
              <select id="round_no" name="round_no" required>
                <?php foreach ($eligibleRounds as $round): ?>
                  <option value="<?= (int)$round['round_no'] ?>" <?= (int)$round['round_no'] === $currentRound ? 'selected' : '' ?>>
                    Round <?= (int)$round['round_no'] ?> → <?= e($round['recipient']['name']) ?> · <?= money($round['expected']) ?> · due <?= e(short_date($round['due_date'])) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-row">
              <div>
                <label for="scheduled_date">Scheduled date</label>
                <input id="scheduled_date" name="scheduled_date" type="date" value="<?= e(date('Y-m-d')) ?>">
              </div>
              <div>
                <label for="payout_note">Note</label>
                <input id="payout_note" name="note" type="text" maxlength="255" placeholder="Paid by transfer to GTBank">
              </div>
            </div>
            <div>
              <button class="btn btn-primary" type="submit">Schedule payout</button>
            </div>
          </form>
          <p class="hint" style="margin-top:12px">Amount is calculated as contribution × active members (<?= money($pool) ?>). The recipient is the member at that position.</p>
        <?php endif; ?>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h2>Awaiting verification</h2>
          <span class="badge badge-warn"><?= count($pendingVerify) ?></span>
        </div>
        <?php if (!$pendingVerify): ?>
          <div class="empty" style="padding:24px"><strong>Queue is clear</strong>No payment is waiting on you.</div>
        <?php else: ?>
          <?php foreach (array_slice($pendingVerify, 0, 5) as $row): ?>
            <div class="list-row">
              <div class="grow">
                <strong><?= e($row['member_name']) ?> · <?= money((float)$row['amount']) ?></strong>
                <span>Round <?= (int)$row['round_no'] ?> · <?= e((string)$row['payment_reference']) ?> · <?= e(short_datetime($row['submitted_at'])) ?></span>
              </div>
              <form method="post" action="<?= e(url('/admin/contributions/' . $row['id'] . '/verify')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-primary btn-sm" type="submit">Verify</button>
              </form>
              <form method="post" action="<?= e(url('/admin/contributions/' . $row['id'] . '/late')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-danger btn-sm" type="submit">Late</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Rotation ledger</h2>
        <span class="badge badge-info">Current round #<?= (int)$currentRound ?></span>
      </div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Round</th><th>Due</th><th>Recipient</th><th>Collected</th><th class="right">Pool</th><th>State</th><th>Payout</th></tr></thead>
          <tbody>
          <?php foreach ($rounds as $round): ?>
            <tr>
              <td><strong>#<?= (int)$round['round_no'] ?></strong></td>
              <td class="nowrap"><?= e(short_date($round['due_date'])) ?></td>
              <td><?= $round['recipient'] ? e($round['recipient']['name']) : '<span style="color:var(--muted)">Slot open</span>' ?></td>
              <td style="min-width:150px">
                <div class="bar-label" style="margin-bottom:4px"><span><?= (int)$round['paid_count'] ?>/<?= (int)$round['member_count'] ?></span><span><?= money($round['paid_amount']) ?></span></div>
                <div class="bar"><i style="width:<?= (int)$round['percent'] ?>%"></i></div>
              </td>
              <td class="right num"><?= money($round['expected']) ?></td>
              <td><?= pill($round['state'] === 'collected' ? 'paid' : $round['state']) ?></td>
              <td>
                <?php if ($round['payout']): ?>
                  <?= pill($round['payout']['status']) ?>
                  <span class="sub"><?= e($round['payout']['reference']) ?> · <?= e(short_date($round['payout']['status'] === 'paid' ? $round['payout']['paid_on'] : $round['payout']['scheduled_date'])) ?></span>
                <?php else: ?>
                  <span style="color:var(--muted);font-size:.8rem">Not scheduled</span>
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
        <h2>Payouts</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/payouts')) ?>">All circles</a>
      </div>
      <?php if (!$payouts): ?>
        <div class="empty" style="padding:24px"><strong>No payouts yet</strong>Schedule round <?= (int)$currentRound ?> when the pool is collected.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Reference</th><th>Round</th><th>Recipient</th><th>Scheduled</th><th>Paid</th><th class="right">Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($payouts as $payout): ?>
              <tr>
                <td class="mono"><?= e($payout['reference']) ?></td>
                <td>#<?= (int)$payout['round_no'] ?></td>
                <td><?= e($payout['recipient_name']) ?></td>
                <td class="nowrap"><?= e(short_date($payout['scheduled_date'])) ?></td>
                <td class="nowrap"><?= e(short_date($payout['paid_on'])) ?></td>
                <td class="right num"><?= money((float)$payout['amount']) ?></td>
                <td><?= pill($payout['status']) ?></td>
                <td class="right">
                  <?php if ($payout['status'] === 'scheduled'): ?>
                    <form method="post" action="<?= e(url('/admin/payouts/' . $payout['id'] . '/paid')) ?>" data-confirm="Confirm this payout as paid?">
                      <?= csrf_field() ?>
                      <button class="btn btn-primary btn-sm" type="submit">Confirm paid</button>
                    </form>
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
        <h2>Contribution records</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/contributions?circle_id=' . $circle['id'])) ?>">Filter in ledger</a>
      </div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Member</th><th>Pos</th><th>Round</th><th>Due</th><th class="right">Amount</th><th>Reference</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($contributions as $row): ?>
            <tr>
              <td><?= e($row['member_name']) ?></td>
              <td>#<?= (int)$row['position'] ?></td>
              <td>#<?= (int)$row['round_no'] ?></td>
              <td class="nowrap"><?= e(short_date($row['due_date'])) ?></td>
              <td class="right num"><?= money((float)$row['amount']) ?></td>
              <td class="mono"><?= $row['payment_reference'] ? e($row['payment_reference']) : '—' ?></td>
              <td><?= pill($row['status']) ?></td>
              <td class="right">
                <div class="table-actions">
                  <?php if ($row['status'] !== 'paid' && !empty($row['payment_reference'])): ?>
                    <form method="post" action="<?= e(url('/admin/contributions/' . $row['id'] . '/verify')) ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-soft btn-sm" type="submit">Verify</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($row['status'] === 'pending'): ?>
                    <form method="post" action="<?= e(url('/admin/contributions/' . $row['id'] . '/late')) ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-danger btn-sm" type="submit">Late</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Members</h2><span class="badge badge-info"><?= count($members) ?> total</span></div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Position</th><th>Member</th><th>Email</th><th>Phone</th><th>Joined</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($members as $member): ?>
            <tr>
              <td><strong>#<?= (int)$member['position'] ?></strong></td>
              <td><?= e($member['name']) ?></td>
              <td><?= e($member['email']) ?></td>
              <td class="mono"><?= e($member['phone'] ?: '—') ?></td>
              <td class="nowrap"><?= e(short_date($member['joined_at'])) ?></td>
              <td><?= pill($member['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
