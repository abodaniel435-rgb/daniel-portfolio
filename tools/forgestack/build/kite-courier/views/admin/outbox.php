<?php $site = site_config(); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/admin_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Email outbox</h1><p>Every notification is written here before delivery is attempted. MAIL_ENABLED=false keeps messages queued.</p></div>
    </div>
    <?php if (!$rows): ?>
      <div class="empty-state"><strong>The outbox is empty.</strong><p>Creating a booking or an enquiry writes the first message.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>#</th><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th><th>Created</th><th>Sent</th></tr></thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= e($row['recipient']) ?></td>
                <td><?= e($row['subject']) ?></td>
                <td><span class="mono"><?= e($row['message_type']) ?></span></td>
                <td><span class="badge badge-<?= $row['status'] === 'sent' ? 'ok' : ($row['status'] === 'failed' ? 'bad' : 'warn') ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                <td><?= e(short_datetime($row['created_at'])) ?></td>
                <td><?= $row['sent_at'] ? e(short_datetime($row['sent_at'])) : '—' ?><?= $row['error'] ? '<br><span class="cell-muted">' . e($row['error']) . '</span>' : '' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
