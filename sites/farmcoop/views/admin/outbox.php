<?php
$counts = $counts ?? ['queued' => 0, 'sent' => 0, 'failed' => 0];
$typeLabel = static function (string $type): string {
    $map = [
        'order_placed' => 'Order placed',
        'order_accepted' => 'Order accepted',
        'order_rejected' => 'Order rejected',
        'order_ready' => 'Order ready',
        'order_completed' => 'Order completed',
        'order_cancelled' => 'Order cancelled',
        'order_reminder' => 'Pending reminder',
        'farmer_verified' => 'Farm verified',
        'admin_digest' => 'Admin digest',
    ];
    return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
};
$total = (int)$counts['queued'] + (int)$counts['sent'] + (int)$counts['failed'];
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Email outbox</h1>
        <p>Every notification FarmCoop has queued. Nothing is sent silently — each message is stored first, then delivered by the mailer.</p>
      </div>
      <form method="post" action="<?= e(url('/admin/automation')) ?>" data-confirm="Run the automation suite now: expire stale listings, chase pending orders, close unconfirmed pickups and send the admin digest?">
        <?= csrf_field() ?>
        <button class="btn btn-gold" type="submit">Run automation now</button>
      </form>
    </div>

    <div class="stats-grid stats-grid-3">
      <div class="stat"><strong><?= (int)$counts['queued'] ?></strong><span>Queued · waiting for the mailer</span></div>
      <div class="stat"><strong><?= (int)$counts['sent'] ?></strong><span>Sent successfully</span></div>
      <div class="stat"><strong><?= (int)$counts['failed'] ?></strong><span>Failed · check the error column</span></div>
    </div>

    <?php if (!$rows): ?>
      <div class="empty">
        <strong>The outbox is empty</strong>
        <p style="margin:0">Place an order or verify a farm and the notification will appear here.</p>
      </div>
    <?php else: ?>
      <div class="panel" style="padding:0;overflow:hidden">
        <div class="table-wrap" style="border:0">
          <table class="data">
            <thead><tr><th>Recipient</th><th>Message</th><th>Type</th><th>Status</th><th>Queued</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><span class="small strong" style="font-weight:600"><?= e($row['recipient']) ?></span></td>
                <td style="max-width:420px">
                  <span class="small"><?= e($row['subject']) ?></span>
                  <?php if ($row['error']): ?>
                    <br><small style="color:var(--clay)"><?= e($row['error']) ?></small>
                  <?php endif; ?>
                </td>
                <td><small class="muted"><?= e($typeLabel((string)$row['message_type'])) ?></small></td>
                <td>
                  <?php if ($row['status'] === 'sent'): ?>
                    <span class="badge badge-ok">Sent</span>
                  <?php elseif ($row['status'] === 'failed'): ?>
                    <span class="badge badge-bad">Failed</span>
                  <?php else: ?>
                    <span class="badge badge-warn">Queued</span>
                  <?php endif; ?>
                  <?php if ($row['sent_at']): ?><br><small class="muted"><?= e(short_datetime($row['sent_at'])) ?></small><?php endif; ?>
                </td>
                <td class="nowrap"><small class="muted"><?= e(short_datetime($row['created_at'])) ?></small></td>
                <td>
                  <details>
                    <summary class="small" style="cursor:pointer;color:var(--forest)">Preview</summary>
                    <div class="small muted" style="max-width:460px;margin-top:8px;white-space:pre-line;line-height:1.6"><?= e(mb_substr((string)$row['body_text'], 0, 900)) ?><?= mb_strlen((string)$row['body_text']) > 900 ? '…' : '' ?></div>
                  </details>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <p class="small muted" style="margin-top:10px">Showing the <?= count($rows) ?> most recent of <?= $total ?> stored message<?= $total === 1 ? '' : 's' ?>. Delivery is controlled by <code>MAIL_ENABLED</code> in <code>.env</code>; queued mail is retried on the next cron run.</p>
    <?php endif; ?>

    <div class="panel" style="margin-top:22px">
      <div class="panel-head"><h2>What runs in the automation suite</h2><span class="badge badge-gold">Cron</span></div>
      <div class="grid grid-2">
        <div>
          <strong class="small" style="display:block">Expire stale listings</strong>
          <p class="small muted" style="margin:4px 0 14px">Listings past their available date with no pending, accepted or ready orders become expired. Empty open listings become sold.</p>
          <strong class="small" style="display:block">Chase pending orders</strong>
          <p class="small muted" style="margin:4px 0 0">Any order still pending after 24 hours emails the farmer a single reminder.</p>
        </div>
        <div>
          <strong class="small" style="display:block">Close unconfirmed pickups</strong>
          <p class="small muted" style="margin:4px 0 14px">Orders marked ready more than 5 days past the requested date are completed and both parties are notified.</p>
          <strong class="small" style="display:block">Daily admin digest</strong>
          <p class="small muted" style="margin:4px 0 0">One email to <?= e(env('ADMIN_EMAIL', 'the admin')) ?> summarising new orders, pending value, completed GMV and unverified farms.</p>
        </div>
      </div>
      <p class="hint" style="margin-top:16px">Schedule with cPanel cron: <code>php /home/USER/farmcoop/cron/run.php</code> every 15 minutes, or call <code>/cron/run.php?key=CRON_SECRET</code>. The run log lives in <code>storage/automation.log</code>.</p>
    </div>
  </div>
</div>
