<?php
$site = site_config();
$qs = static function (array $overrides = []) use ($status, $q): string {
    $params = array_merge(['status' => $status, 'q' => $q], $overrides);
    $params = array_filter($params, static fn ($v) => $v !== '');
    return $params ? '?' . http_build_query($params) : '';
};
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/admin_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>All <?= e($site['labels']['txn_plural']) ?></h1>
        <p><?= (int)$page['total'] ?> matching record<?= (int)$page['total'] === 1 ? '' : 's' ?> · status changes are written to the timeline and emailed.</p>
      </div>
      <a class="btn btn-quiet" href="<?= e(url('/track')) ?>">Public tracking ↗</a>
    </div>

    <form class="toolbar" method="get" action="<?= e(url('/admin/records')) ?>">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Reference, name, email or choice">
      <select name="status">
        <option value="">Every status</option>
        <?php foreach ((array)cfg('status_meta', []) as $key => $meta): ?>
          <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary btn-sm" type="submit">Filter</button>
      <?php if ($q !== '' || $status !== ''): ?><a class="btn btn-quiet btn-sm" href="<?= e(url('/admin/records')) ?>">Reset</a><?php endif; ?>
    </form>

    <?php if (!$rows): ?>
      <div class="empty-state"><strong>No records match.</strong><p>Try a different filter, or clear the search.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr>
              <th>Reference</th><th>Customer</th><th>When</th><th><?= e($site['labels']['choice_a']) ?></th>
              <th><?= e($site['labels']['quantity']) ?></th><th>Status</th>
              <?php if (cfg('modules.payments', false)): ?><th>Payment</th><?php endif; ?>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td class="mono"><?= e($row['reference']) ?></td>
                <td>
                  <strong><?= e($row['customer_name']) ?></strong><br>
                  <span class="cell-muted"><?= e($row['customer_email']) ?><br><?= e($row['customer_phone']) ?></span>
                </td>
                <td><?= e(short_date($row['request_date'])) ?><br><span class="cell-muted"><?= e(formatted_time($row['request_time'])) ?></span></td>
                <td><?= e($row['choice_a']) ?><br><span class="cell-muted"><?= e($row['choice_b'] ?: '—') ?></span></td>
                <td><?= (int)$row['quantity'] ?> <?= e(quantity_unit()) ?></td>
                <td><?= status_badge_html((string)$row['status']) ?></td>
                <?php if (cfg('modules.payments', false)): ?>
                  <td>
                    <?= $row['payment_status'] === 'paid' ? '<span class="badge badge-ok">Paid</span>' : '<span class="badge badge-warn">' . e(ucfirst((string)$row['payment_status'])) . '</span>' ?>
                    <br><span class="cell-muted"><?= money_fmt((float)$row['amount']) ?></span>
                  </td>
                <?php endif; ?>
                <td>
                  <?php $next = next_statuses((string)$row['status']); ?>
                  <?php if ($next): ?>
                    <form class="inline-form" method="post" action="<?= e(url('/admin/records/' . $row['id'] . '/status')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <select name="status" aria-label="New status for <?= e($row['reference']) ?>">
                        <?php foreach ($next as $option): ?>
                          <option value="<?= e($option) ?>"><?= e(status_label($option)) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <input class="note-input" name="note" placeholder="Optional note" maxlength="500">
                      <button class="btn btn-primary btn-sm" type="submit">Update</button>
                    </form>
                  <?php endif; ?>
                  <?php if (cfg('modules.payments', false) && $row['payment_status'] === 'unpaid' && (float)$row['amount'] > 0): ?>
                    <form class="inline-form" method="post" action="<?= e(url('/admin/records/' . $row['id'] . '/paid')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <button class="btn btn-quiet btn-sm" type="submit">Mark paid</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ((int)$page['pages'] > 1): ?>
        <nav class="pagination" aria-label="Pagination">
          <?php for ($i = 1; $i <= (int)$page['pages']; $i++): ?>
            <?php if ($i === (int)$page['current']): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= e(url('/admin/records' . $qs(['page' => $i]))) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
