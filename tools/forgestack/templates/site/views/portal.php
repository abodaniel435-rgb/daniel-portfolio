<?php
$site = site_config();
$user = Auth::user();
?>
<section class="page-head page-head-compact">
  <div class="page-head-inner">
    <p class="eyebrow"><?= e($site['labels']['portal']) ?></p>
    <h1>Hello, <?= e(explode(' ', (string)$user['name'])[0]) ?>.</h1>
    <p class="page-lede">Every request attached to <?= e($user['email']) ?>, in one place.</p>
  </div>
</section>

<section class="section section-form">
  <div class="portal-grid">
    <aside class="card profile-card">
      <span class="profile-avatar" aria-hidden="true"><?= e(mb_substr((string)$user['name'], 0, 1)) ?></span>
      <h3><?= e($user['name']) ?></h3>
      <p><?= e($user['email']) ?><br><?= e($user['phone']) ?></p>
      <span class="badge badge-neutral"><?= e($user['role'] === 'admin' ? 'Administrator' : ucfirst($site['labels']['customer'])) ?></span>
      <div class="profile-actions">
        <a class="btn btn-primary btn-block" href="<?= e(url('/book')) ?>"><?= e($site['labels']['book']) ?></a>
        <?php if (cfg('modules.messaging', false)): ?><a class="btn btn-quiet btn-block" href="<?= e(url('/contact')) ?>">Contact the team</a><?php endif; ?>
      </div>
    </aside>

    <div class="portal-main">
      <div class="panel">
        <div class="panel-head">
          <h2>Your <?= e($site['labels']['txn_plural']) ?></h2>
          <span class="badge badge-neutral"><?= count($records) ?> record<?= count($records) === 1 ? '' : 's' ?></span>
        </div>
        <?php if (!$records): ?>
          <div class="empty-state"><strong>Nothing here yet.</strong><p>When you <?= e(strtolower($site['labels']['book'])) ?>, it appears here with a reference and a live status.</p></div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data">
              <thead><tr><th>Reference</th><th>When</th><th><?= e($site['labels']['choice_a']) ?></th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($records as $record): ?>
                  <tr>
                    <td class="mono"><?= e($record['reference']) ?></td>
                    <td><?= e(short_date($record['request_date'])) ?><br><span class="cell-muted"><?= e(formatted_time($record['request_time'])) ?></span></td>
                    <td><?= e($record['choice_a']) ?><br><span class="cell-muted"><?= e($record['choice_b']) ?></span></td>
                    <td><?= status_badge_html((string)$record['status']) ?></td>
                    <td><a class="text-link" href="<?= e(url('/track?ref=' . urlencode((string)$record['reference']))) ?>">View</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <?php if (cfg('modules.orders', false)): ?>
        <div class="panel">
          <div class="panel-head"><h2>Your orders</h2><a class="btn btn-quiet btn-sm" href="<?= e(url('/shop')) ?>"><?= e($site['labels']['shop_title']) ?></a></div>
          <?php if (!$orders): ?>
            <div class="empty-state"><strong>No orders yet.</strong><p>The catalogue is open for collection orders.</p></div>
          <?php else: ?>
            <div class="table-wrap">
              <table class="data">
                <thead><tr><th>Reference</th><th>Placed</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($orders as $order): ?>
                    <tr>
                      <td class="mono"><?= e($order['reference']) ?></td>
                      <td><?= e(short_date(substr((string)$order['created_at'], 0, 10))) ?></td>
                      <td><?= money_fmt((float)$order['total']) ?></td>
                      <td><span class="badge badge-neutral"><?= e(ucfirst($order['status'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
