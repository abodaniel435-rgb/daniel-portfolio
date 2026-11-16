<?php
declare(strict_types=1);
/** @var array $rows @var array $counts @var bool $mailing */
require BASE_PATH . '/views/admin/nav.php';
$byStatus = [];
foreach ($counts as $row) {
    $byStatus[(string)$row['status']] = (int)$row['n'];
}
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Messages</p>
      <h1>What PayRite tried to send</h1>
      <p class="lede">Payslip notices, and anything else the application queues. Each row keeps the exact subject and
        body that was generated, so a complaint of "I never got it" has something to check against.</p>
    </div>
    <div class="verdict <?= $mailing ? 'ok' : 'info' ?>" style="min-width:250px">
      <h4><?= $mailing ? 'Sending is on' : 'Sending is off' ?></h4>
      <p><?= $mailing
          ? 'Messages go out through the server\'s own mail transport, as set in <span class="mono">.env</span>.'
          : 'Mail is written to this table and logged, never sent — which is the safe default for a portfolio build.' ?></p>
    </div>
  </div>

  <div class="grid-4" style="margin-bottom:24px">
    <div class="stat"><b><?= $byStatus['queued'] ?? 0 ?></b><span>queued</span></div>
    <div class="stat stat-mint"><b><?= $byStatus['sent'] ?? 0 ?></b><span>sent</span></div>
    <div class="stat stat-red"><b><?= $byStatus['failed'] ?? 0 ?></b><span>failed</span></div>
    <div class="stat"><b><?= count($rows) ?></b><span>shown, newest first</span></div>
  </div>

  <?php if (!$rows): ?>
    <p class="tiny">Nothing has been queued yet. Locking a run and pressing <em>Queue payslip notices</em> writes one
      row per employee who has a linked, active login.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th><th>Created</th><th>Sent</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td class="tiny mono"><?= e((string)$row['recipient']) ?></td>
            <td><?= e((string)$row['subject']) ?>
              <details style="margin-top:6px">
                <summary class="tiny muted" style="cursor:pointer">show body</summary>
                <p class="tiny" style="margin-top:8px"><?= e(mb_strimwidth(strip_tags((string)$row['body_html']), 0, 700, '…')) ?></p>
              </details></td>
            <td class="tiny mono"><?= e((string)$row['message_type']) ?></td>
            <td><span class="badge badge-<?= $row['status'] === 'sent' ? 'ok' : ($row['status'] === 'failed' ? 'bad' : 'warn') ?>"><?= e((string)$row['status']) ?></span>
              <?php if (!empty($row['error'])): ?><div class="tiny" style="color:var(--red)"><?= e((string)$row['error']) ?></div><?php endif; ?></td>
            <td class="tiny"><?= e(short_datetime((string)$row['created_at'])) ?></td>
            <td class="tiny"><?= !empty($row['sent_at']) ? e(short_datetime((string)$row['sent_at'])) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="grid-2" style="margin-top:26px">
    <div class="card">
      <h3>Turn sending on</h3>
      <p class="tiny">Set <span class="mono">MAIL_ENABLED=true</span> with <span class="mono">MAIL_FROM</span> and
        <span class="mono">MAIL_FROM_NAME</span> in <span class="mono">.env</span>. Delivery uses PHP's
        <span class="mono">mail()</span> function, which on cPanel is the server's own transport — no SMTP host, port
        or password to store anywhere. Address the <span class="mono">MAIL_FROM</span> mailbox on the same account and
        leave SPF and DKIM as cPanel wrote them, or the notices will land in spam.</p>
    </div>
    <div class="card">
      <h3>Queued is not lost</h3>
      <p class="tiny">A message is inserted as <span class="mono">queued</span> before the transport is attempted, then
        marked <span class="mono">sent</span> or <span class="mono">failed</span> with the error. So an SMTP outage
        leaves a record to retry from instead of silence.</p>
    </div>
  </div>
</section>
