<?php $site = site_config(); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/admin_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Payments</h1><p>Manual capture only — no card data touches this application. Each capture writes a ledger row and an email.</p></div>
      <div class="stat stat-inline"><strong><?= money_fmt($total) ?></strong><span>Collected</span></div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Awaiting payment</h2><span class="badge badge-warn"><?= count($unpaid) ?></span></div>
      <?php if (!$unpaid): ?>
        <div class="empty-state"><strong>Nothing outstanding.</strong><p>Unpaid records appear here after the requested date passes or once booked.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Reference</th><th>Customer</th><th>When</th><th>Amount</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($unpaid as $row): ?>
                <tr>
                  <td class="mono"><?= e($row['reference']) ?></td>
                  <td><?= e($row['customer_name']) ?><br><span class="cell-muted"><?= e($row['customer_email']) ?></span></td>
                  <td><?= e(short_date($row['request_date'])) ?> · <?= e(formatted_time($row['request_time'])) ?></td>
                  <td><?= money_fmt((float)$row['amount']) ?></td>
                  <td>
                    <form method="post" action="<?= e(url('/admin/records/' . $row['id'] . '/paid')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <button class="btn btn-primary btn-sm" type="submit">Mark paid</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Ledger</h2></div>
      <?php if (!$payments): ?>
        <div class="empty-state"><strong>No payments captured yet.</strong><p>Capture a payment above to create the first ledger row.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Receipt</th><th>Amount</th><th>Method</th><th>Status</th><th>Note</th><th>Recorded</th></tr></thead>
            <tbody>
              <?php foreach ($payments as $payment): ?>
                <tr>
                  <td class="mono"><?= e($payment['reference']) ?></td>
                  <td><?= money_fmt((float)$payment['amount']) ?></td>
                  <td><?= e(ucfirst($payment['method'])) ?></td>
                  <td><span class="badge badge-ok"><?= e(ucfirst($payment['status'])) ?></span></td>
                  <td><?= e($payment['note']) ?></td>
                  <td><?= e(short_datetime($payment['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
