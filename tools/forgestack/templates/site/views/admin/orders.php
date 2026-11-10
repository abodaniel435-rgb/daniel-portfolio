<?php $site = site_config(); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/admin_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Orders</h1><p>Every order was stock-checked inside a transaction. Status changes email the customer.</p></div>
    </div>
    <?php if (!$orders): ?>
      <div class="empty-state"><strong>No orders yet.</strong><p>Public orders appear here immediately.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Reference</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Placed</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td class="mono"><?= e($order['reference']) ?></td>
                <td><strong><?= e($order['customer_name']) ?></strong><br><span class="cell-muted"><?= e($order['customer_email']) ?><br><?= e($order['customer_phone']) ?></span></td>
                <td><?= e($order['items'] ?? '—') ?></td>
                <td><?= money_fmt((float)$order['total']) ?></td>
                <td><span class="badge badge-<?= in_array($order['status'], ['pending','ready'], true) ? 'warn' : ($order['status'] === 'cancelled' ? 'bad' : 'ok') ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                <td><?= e(short_datetime($order['created_at'])) ?></td>
                <td>
                  <?php
                    $next = match ($order['status']) {
                        'pending' => ['ready', 'cancelled'],
                        'ready' => ['collected', 'cancelled'],
                        default => [],
                    };
                  ?>
                  <?php if ($next): ?>
                    <form class="inline-form" method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/status')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                      <select name="status" aria-label="Order status">
                        <?php foreach ($next as $option): ?><option value="<?= e($option) ?>"><?= e(ucfirst($option)) ?></option><?php endforeach; ?>
                      </select>
                      <button class="btn btn-primary btn-sm" type="submit">Update</button>
                    </form>
                  <?php else: ?>
                    <span class="cell-muted">Closed</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
