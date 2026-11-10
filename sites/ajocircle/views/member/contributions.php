<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Contributions</h1>
        <p>Record a payment reference for each round. Your organiser verifies it and it counts towards the pool.</p>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/member/statement')) ?>">Download statement</a>
    </div>

    <div class="stat-row">
      <div class="stat stat-accent"><strong><?= money($totals['saved']) ?></strong><span>Verified contributions</span></div>
      <div class="stat"><strong><?= (int)$totals['paid_count'] ?></strong><span>Rounds settled</span></div>
      <div class="stat stat-gold"><strong><?= money($totals['outstanding']) ?></strong><span>Outstanding</span></div>
      <div class="stat"><strong><?= (int)$totals['late_count'] ?></strong><span>Late rounds</span></div>
    </div>

    <div class="panel" id="pay">
      <div class="panel-head">
        <h2>Record a payment</h2>
        <span class="badge badge-neutral"><?= count($outstanding) ?> round(s) awaiting payment</span>
      </div>

      <?php if (!$outstanding): ?>
        <div class="empty"><strong>Nothing to record</strong>Every open round already has a payment reference on file.</div>
      <?php else: ?>
        <form method="post" action="<?= e(url('/member/contributions/pay')) ?>" class="form-grid">
          <?= csrf_field() ?>
          <div>
            <label for="contribution_id">Which round are you paying?</label>
            <select id="contribution_id" name="id" required data-preselect="<?= (int)$preselect ?>">
              <option value="">Select a round…</option>
              <?php foreach ($outstanding as $item): ?>
                <option value="<?= (int)$item['id'] ?>" <?= (int)$preselect === (int)$item['id'] ? 'selected' : '' ?>>
                  <?= e($item['circle_name']) ?> · round <?= (int)$item['round_no'] ?> · <?= money((float)$item['amount']) ?> · due <?= e(short_date($item['due_date'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-row-3">
            <div>
              <label for="payment_reference">Payment reference</label>
              <input id="payment_reference" name="payment_reference" type="text" required minlength="4" maxlength="80" placeholder="e.g. GTB-260311-8841" autocomplete="off">
              <p class="hint">The transfer narration or bank reference. This is what your organiser checks.</p>
            </div>
            <div>
              <label for="method">Method</label>
              <select id="method" name="method" required>
                <option value="bank_transfer">Bank transfer</option>
                <option value="mobile_money">Mobile money</option>
                <option value="cash">Cash to organiser</option>
              </select>
            </div>
            <div>
              <label for="paid_on">Date paid</label>
              <input id="paid_on" name="paid_on" type="date" value="<?= e(date('Y-m-d')) ?>" max="<?= e(date('Y-m-d')) ?>" required>
            </div>
          </div>
          <div>
            <label for="note">Note for your organiser (optional)</label>
            <input id="note" name="note" type="text" maxlength="255" placeholder="Sent from my GTBank account ending 4412">
          </div>
          <div>
            <button class="btn btn-primary" type="submit">Submit for verification</button>
          </div>
        </form>
        <p class="hint" style="margin-top:14px">Duplicate payments are blocked: one payment reference per round.</p>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Contribution history</h2>
        <form class="toolbar" method="get" action="<?= e(url('/member/contributions')) ?>" style="margin:0">
          <select name="circle_id" aria-label="Circle">
            <option value="">All circles</option>
            <?php foreach ($circles as $circle): ?>
              <option value="<?= (int)$circle['id'] ?>" <?= $circleId === (int)$circle['id'] ? 'selected' : '' ?>><?= e($circle['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="status" aria-label="Status">
            <option value="">Any status</option>
            <?php foreach (['pending' => 'Pending', 'paid' => 'Paid', 'late' => 'Late'] as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-dark btn-sm" type="submit">Filter</button>
        </form>
      </div>

      <?php if (!$contributions): ?>
        <div class="empty"><strong>No contributions match</strong>Adjust the filters or record a payment above.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Circle</th><th>Round</th><th>Due date</th><th class="right">Amount</th><th>Method</th><th>Reference</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($contributions as $row): ?>
              <tr>
                <td><?= e($row['circle_name']) ?><span class="sub"><?= e(frequency_label($row['frequency'])) ?></span></td>
                <td>#<?= (int)$row['round_no'] ?></td>
                <td class="nowrap"><?= e(short_date($row['due_date'])) ?></td>
                <td class="right num"><?= money((float)$row['amount']) ?></td>
                <td><?= e(ucwords(str_replace('_', ' ', $row['method']))) ?></td>
                <td class="mono"><?= $row['payment_reference'] ? e($row['payment_reference']) : '<span style="color:var(--muted)">—</span>' ?></td>
                <td><?= pill($row['status']) ?></td>
                <td class="right">
                  <?php if ($row['status'] !== 'paid' && empty($row['payment_reference'])): ?>
                    <a class="btn btn-soft btn-sm" href="<?= e(url('/member/contributions?pay=' . $row['id'] . '#pay')) ?>">Record</a>
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
</div>
