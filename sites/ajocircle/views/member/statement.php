<?php
$grouped = [];
foreach ($rows as $row) {
    $grouped[$row['circle_name']][] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
  <header class="site-header no-print">
    <div class="nav">
      <a class="brand" href="<?= e(url('/')) ?>">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 12a8 8 0 1 1-2.34-5.66"/><path d="M20 4v4h-4"/></svg>
        </span>
        Ajo<em>Circle</em>
      </a>
      <nav class="nav-links" style="margin-left:auto">
        <a href="<?= e(url('/dashboard')) ?>">Dashboard</a>
        <a class="active" href="<?= e(url('/member/statement')) ?>">Statement</a>
        <a href="<?= e(url('/member/contributions')) ?>">Contributions</a>
      </nav>
      <div class="nav-actions">
        <button class="btn btn-primary btn-sm" type="button" data-print>Print / save PDF</button>
      </div>
    </div>
  </header>

  <div class="statement">
    <div class="no-print" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;align-items:center">
      <form method="get" action="<?= e(url('/member/statement')) ?>" class="toolbar" style="margin:0">
        <select name="circle_id" aria-label="Circle">
          <option value="">All circles</option>
          <?php foreach ($circles as $circle): ?>
            <option value="<?= (int)$circle['id'] ?>" <?= $circleId === (int)$circle['id'] ? 'selected' : '' ?>><?= e($circle['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm" type="submit">Apply</button>
      </form>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/dashboard')) ?>">← Back to dashboard</a>
    </div>

    <div class="statement-sheet">
      <div class="statement-head">
        <div>
          <div class="brand" style="margin-bottom:10px">
            <span class="brand-mark" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 12a8 8 0 1 1-2.34-5.66"/><path d="M20 4v4h-4"/></svg>
            </span>
            Ajo<em>Circle</em>
          </div>
          <h1>Member statement</h1>
          <p style="margin:0;color:var(--muted);font-size:.88rem">
            <?= e($user['name']) ?> · <?= e($user['email']) ?>
            <?= $circleId ? ' · ' . e($grouped ? array_key_first($grouped) : '') : ' · all circles' ?>
          </p>
        </div>
        <div class="meta">
          <strong>Generated</strong><br><?= e(short_datetime($generated)) ?><br><br>
          <strong>Statement ID</strong><br>ST-<?= (int)$user['id'] ?>-<?= e(date('ymd')) ?>
        </div>
      </div>

      <div class="statement-totals">
        <div><span>Verified contributions</span><strong><?= money($paidTotal) ?></strong></div>
        <div><span>Outstanding</span><strong><?= money($outstandingTotal) ?></strong></div>
        <div><span>Payouts received</span><strong><?= money($payoutTotal) ?></strong></div>
      </div>

      <?php if (!$rows): ?>
        <div class="empty"><strong>No entries on this statement</strong>Record a contribution to start your ledger.</div>
      <?php else: ?>
        <?php foreach ($grouped as $circleName => $items): ?>
          <h3 style="margin:26px 0 10px;font-size:1rem"><?= e($circleName) ?></h3>
          <div class="table-wrap">
            <table class="data">
              <thead><tr><th>Round</th><th>Due</th><th>Paid on</th><th class="right">Amount</th><th>Method</th><th>Reference</th><th>Status</th></tr></thead>
              <tbody>
              <?php foreach ($items as $row): ?>
                <tr>
                  <td>#<?= (int)$row['round_no'] ?></td>
                  <td class="nowrap"><?= e(short_date($row['due_date'])) ?></td>
                  <td class="nowrap"><?= e(short_date($row['paid_on'])) ?></td>
                  <td class="right num"><?= money((float)$row['amount']) ?></td>
                  <td><?= e(ucwords(str_replace('_', ' ', $row['method']))) ?></td>
                  <td class="mono"><?= $row['payment_reference'] ? e($row['payment_reference']) : '—' ?></td>
                  <td><?= e(ucfirst($row['status'])) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p style="font-size:.78rem;color:var(--muted);margin:8px 0 0">
            <?= count($items) ?> round(s) · verified <?= money(array_sum(array_map(static fn (array $r): float => $r['status'] === 'paid' ? (float)$r['amount'] : 0, $items))) ?>
          </p>
        <?php endforeach; ?>
      <?php endif; ?>

      <h3 style="margin:28px 0 10px;font-size:1rem">Payouts</h3>
      <?php if (!$payouts): ?>
        <p style="font-size:.84rem;color:var(--muted);margin:0">No payout has been scheduled for this member yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Reference</th><th>Circle</th><th>Round</th><th>Scheduled</th><th>Paid</th><th class="right">Amount</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($payouts as $payout): ?>
              <tr>
                <td class="mono"><?= e($payout['reference']) ?></td>
                <td><?= e($payout['circle_name']) ?></td>
                <td>#<?= (int)$payout['round_no'] ?></td>
                <td class="nowrap"><?= e(short_date($payout['scheduled_date'])) ?></td>
                <td class="nowrap"><?= e(short_date($payout['paid_on'])) ?></td>
                <td class="right num"><?= money((float)$payout['amount']) ?></td>
                <td><?= e(ucfirst($payout['status'])) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div class="statement-foot">
        <p style="margin:0 0 6px"><strong>How to read this statement:</strong> “Verified” means your circle organiser confirmed the payment reference. “Late” means the round was still unpaid after its due date. Positions and payout order are fixed at the moment a member joins.</p>
        <p style="margin:0">AjoCircle records contributions; it does not hold or move funds. Questions? hello@ajocircle.test · +234 703 808 2491.</p>
      </div>
    </div>
  </div>
</body>
</html>
