<?php
$counts = $counts ?: ['total' => 0, 'queued' => 0, 'sent' => 0, 'failed' => 0];
$buildQuery = static function (array $overrides = []) use ($type): string {
    $params = array_filter([
        'type' => $overrides['type'] ?? $type,
        'page' => $overrides['page'] ?? null,
    ], static fn ($v): bool => $v !== null && $v !== '');
    return url('/admin/outbox' . ($params ? '?' . http_build_query($params) : ''));
};
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Email outbox</h1>
        <p>Every notification is queued in the database before delivery — receipts, reminders, payout notices and digests.</p>
      </div>
      <span class="badge <?= env('MAIL_ENABLED', false) ? 'badge-ok' : 'badge-warn' ?>">
        <?= env('MAIL_ENABLED', false) ? 'Delivery enabled' : 'Queued only (MAIL_ENABLED=false)' ?>
      </span>
    </div>

    <div class="grid grid-4" style="margin-bottom:20px">
      <div class="stat"><strong><?= (int)$counts['total'] ?></strong><span>Total messages</span></div>
      <div class="stat stat-gold"><strong><?= (int)$counts['queued'] ?></strong><span>Queued</span></div>
      <div class="stat stat-accent"><strong><?= (int)$counts['sent'] ?></strong><span>Delivered</span></div>
      <div class="stat"><strong><?= (int)$counts['failed'] ?></strong><span>Failed</span></div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div class="tab-links">
          <a class="<?= $type === '' ? 'active' : '' ?>" href="<?= e($buildQuery(['type' => null])) ?>">All types</a>
          <?php foreach ($types as $item): ?>
            <a class="<?= $type === $item ? 'active' : '' ?>" href="<?= e($buildQuery(['type' => $item])) ?>"><?= e(str_replace('_', ' ', $item)) ?></a>
          <?php endforeach; ?>
        </div>
        <span class="badge badge-neutral"><?= (int)$pager['total'] ?> in view</span>
      </div>

      <?php if (!$rows): ?>
        <div class="empty"><strong>No messages</strong>Notifications appear here as members join, pay and receive payouts.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th><th>Queued</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= e($row['recipient']) ?></td>
                <td><?= e($row['subject']) ?></td>
                <td class="mono"><?= e($row['message_type']) ?></td>
                <td><span class="badge <?= $row['status'] === 'sent' ? 'badge-ok' : ($row['status'] === 'failed' ? 'badge-bad' : 'badge-warn') ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                <td class="nowrap"><?= e(short_datetime($row['created_at'])) ?></td>
                <td class="right">
                  <details>
                    <summary class="btn btn-ghost btn-sm" style="list-style:none;cursor:pointer">Preview</summary>
                    <div style="margin-top:10px;text-align:left;max-width:560px">
                      <pre style="white-space:pre-wrap;font:400 .8rem/1.55 var(--font-body);background:#fbfaff;border:1px solid var(--line);border-radius:10px;padding:12px;margin:0;max-height:260px;overflow:auto"><?= e($row['body_text']) ?></pre>
                    </div>
                  </details>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pager['pages'] > 1): ?>
          <div class="pagination">
            <?php for ($page = 1; $page <= $pager['pages']; $page++): ?>
              <?php if ($page === $pager['current']): ?>
                <span class="current"><?= $page ?></span>
              <?php else: ?>
                <a href="<?= e($buildQuery(['page' => $page])) ?>"><?= $page ?></a>
              <?php endif; ?>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
