<?php $site = site_config(); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/admin_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Enquiries</h1><p>Contact-form submissions are validated, stored and emailed to the desk. Reply from your mailbox.</p></div>
      <?php $open = count(array_filter($rows, static fn ($r) => $r['status'] === 'new')); ?>
      <span class="badge badge-warn"><?= $open ?> new</span>
    </div>

    <?php if (!$rows): ?>
      <div class="empty-state"><strong>No enquiries yet.</strong><p>The contact form writes here.</p></div>
    <?php else: ?>
      <div class="message-list">
        <?php foreach ($rows as $row): ?>
          <article class="message-card <?= $row['status'] === 'new' ? 'is-new' : '' ?>">
            <header>
              <div>
                <strong><?= e($row['subject']) ?></strong>
                <span><?= e($row['name']) ?> · <a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a><?= $row['phone'] ? ' · ' . e($row['phone']) : '' ?></span>
              </div>
              <span class="badge badge-<?= $row['status'] === 'new' ? 'warn' : 'ok' ?>"><?= e(ucfirst($row['status'])) ?></span>
            </header>
            <p><?= nl2br(e($row['body'])) ?></p>
            <footer>
              <span><?= e(short_datetime($row['created_at'])) ?></span>
              <?php if ($row['status'] === 'new'): ?>
                <form method="post" action="<?= e(url('/admin/messages/' . $row['id'] . '/handled')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button class="btn btn-quiet btn-sm" type="submit">Mark handled</button>
                </form>
              <?php endif; ?>
            </footer>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
