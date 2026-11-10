<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Email outbox</h1>
        <p>Every notification RoutePilot queues is stored here — customer updates, ETA reminders and the admin digest.</p>
      </div>
      <form method="post" action="<?= e(url('/admin/automation')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <button class="btn btn-ghost btn-sm" type="submit">Run automation</button>
      </form>
    </div>

    <div class="kpi-row">
      <div class="kpi"><span>Total messages</span><strong><?= (int)$counts['total'] ?></strong><small>All time</small></div>
      <div class="kpi kpi-amber"><span>Queued</span><strong><?= (int)$counts['queued'] ?></strong><small>Waiting for mail delivery</small></div>
      <div class="kpi kpi-cyan"><span>Sent</span><strong><?= (int)$counts['sent'] ?></strong><small>Accepted by the mail server</small></div>
      <div class="kpi"><span>Failed</span><strong><?= (int)$counts['failed'] ?></strong><small>Check the error column</small></div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div><h2>Message log</h2><p class="sub">MAIL_ENABLED is <?= env('MAIL_ENABLED', false) ? 'on' : 'off' ?> — queued messages are recorded either way.</p></div>
        <div class="tab-links">
          <a class="<?= $type === '' ? 'active' : '' ?>" href="<?= e(url('/admin/outbox')) ?>">All</a>
          <?php foreach ($types as $option): ?>
            <a class="<?= $type === $option ? 'active' : '' ?>" href="<?= e(url('/admin/outbox?type=' . rawurlencode($option))) ?>"><?= e(str_replace('_', ' ', $option)) ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if (!$rows): ?>
        <div class="empty"><strong>No messages queued yet</strong>Create a shipment or run automation to generate notifications.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th><th>Queued</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= e($row['recipient']) ?></td>
                <td>
                  <details>
                    <summary style="cursor:pointer"><?= e($row['subject']) ?></summary>
                    <div class="tl-note" style="max-width:560px;white-space:pre-wrap"><?= e(mb_substr($row['body_text'], 0, 480)) ?></div>
                  </details>
                </td>
                <td><span class="tag"><?= e(str_replace('_', ' ', $row['message_type'])) ?></span></td>
                <td>
                  <?= match ($row['status']) {
                      'sent' => '<span class="pill pill-done">Sent</span>',
                      'failed' => '<span class="pill pill-fail">Failed</span>',
                      default => '<span class="pill pill-queue">Queued</span>',
                  } ?>
                  <?php if ($row['error']): ?><span class="cell-sub"><?= e($row['error']) ?></span><?php endif; ?>
                </td>
                <td><?= e(short_datetime($row['created_at'])) ?><span class="cell-sub"><?= e(rp_time_ago($row['created_at'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($page['pages'] > 1): ?>
          <nav class="pagination" aria-label="Outbox pages">
            <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
              <?php if ($i === $page['current']): ?>
                <span class="current"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= e(url('/admin/outbox?' . http_build_query(array_filter(['type' => $type, 'page' => $i])))) ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><div><h2>Automation schedule</h2><p class="sub">Run from cPanel cron every 15 minutes, or trigger manually above</p></div></div>
      <div class="grid grid-2">
        <div>
          <dl class="kv">
            <dt>CLI</dt><dd><code>php cron/run.php</code></dd>
            <dt>URL</dt><dd><code>cron/run.php?key=CRON_SECRET</code></dd>
          </dl>
        </div>
        <div class="stack small muted">
          <span>· Flags shipments whose pickup date has passed while still unassigned.</span>
          <span>· Flags in-transit parcels with no scan for more than 3 days (once).</span>
          <span>· Sends ETA reminders for parcels out for delivery.</span>
          <span>· Emails the administrator a daily dispatch digest.</span>
        </div>
      </div>
    </div>
  </div>
</div>
