<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Email outbox</h1><p>Every automated notification is queued here for audit, even when SMTP is off.</p></div>
      <form method="post" action="<?= e(url('/admin/automation')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-primary" type="submit">Run automation now</button>
      </form>
    </div>

    <div class="grid grid-3" style="margin-bottom:22px">
      <div class="stat" style="margin:0"><strong><?= (int)$counts['queued'] ?></strong><span>Queued</span></div>
      <div class="stat" style="margin:0"><strong><?= (int)$counts['sent'] ?></strong><span>Sent</span></div>
      <div class="stat" style="margin:0"><strong><?= (int)$counts['failed'] ?></strong><span>Failed</span></div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Latest messages</h2><span class="badge badge-info">Last 100</span></div>
      <?php if (!$rows): ?>
        <div class="empty"><strong>No emails yet</strong>Bookings, confirmations and reminders will appear here.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Created</th><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><small><?= e(short_datetime($row['created_at'])) ?></small></td>
                <td><?= e($row['recipient']) ?></td>
                <td><small><?= e($row['subject']) ?></small></td>
                <td><span class="badge badge-neutral"><?= e($row['message_type']) ?></span></td>
                <td>
                  <?php if ($row['status'] === 'sent'): ?><span class="badge badge-ok">Sent</span>
                  <?php elseif ($row['status'] === 'failed'): ?><span class="badge badge-bad" title="<?= e($row['error'] ?? '') ?>">Failed</span>
                  <?php else: ?><span class="badge badge-warn">Queued</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      <p class="hint" style="margin-top:14px">Set <code>MAIL_ENABLED=true</code> and <code>MAIL_FROM</code> in <code>.env</code> to deliver through cPanel's local mail service. Run the cron URL every 15 minutes for reminders and the daily digest.</p>
    </div>
  </div>
</div>
