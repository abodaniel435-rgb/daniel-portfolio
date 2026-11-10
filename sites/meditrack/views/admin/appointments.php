<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Appointments</h1><p>Every booking across the clinic, newest first.</p></div>
    </div>

    <div class="panel">
      <form class="toolbar" method="get" action="<?= e(url('/admin/appointments')) ?>">
        <input name="q" value="<?= e($q) ?>" placeholder="Search reference, patient or doctor">
        <select name="status">
          <option value="">All statuses</option>
          <?php foreach (['pending', 'confirmed', 'completed', 'cancelled', 'no_show'] as $option): ?>
            <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($option))) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Filter</button>
        <?php if ($q !== '' || $status !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/appointments')) ?>">Reset</a><?php endif; ?>
        <span style="margin-left:auto;font-size:.82rem;color:var(--muted)"><?= (int)$page['total'] ?> result<?= $page['total'] === 1 ? '' : 's' ?></span>
      </form>

      <?php if (!$rows): ?>
        <div class="empty"><strong>No appointments match this filter</strong>Try clearing the search or choosing another status.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Reference</th><th>Date</th><th>Patient</th><th>Doctor</th><th>Reason</th><th>Fee</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td class="mono"><?= e($row['reference']) ?></td>
                <td><?= e(short_date($row['appointment_date'])) ?><br><small><?= e(date('g:i A', strtotime($row['appointment_time']))) ?></small></td>
                <td><?= e($row['patient_name']) ?><br><small><?= e($row['phone']) ?></small></td>
                <td><?= e($row['doctor_name']) ?><br><small><?= e($row['specialty']) ?></small></td>
                <td><small><?= e(mb_strimwidth($row['reason'], 0, 44, '…')) ?></small></td>
                <td><?= money((float)$row['fee']) ?></td>
                <td><?= status_badge($row['status']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($page['pages'] > 1): ?>
          <div class="pagination">
            <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
              <?php if ($i === $page['current']): ?>
                <span class="current"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= e(url('/admin/appointments?page=' . $i . '&status=' . urlencode($status) . '&q=' . urlencode($q))) ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
