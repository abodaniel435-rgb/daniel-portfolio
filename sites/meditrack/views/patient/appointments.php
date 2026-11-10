<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>My appointments</h1>
        <p>Everything you have booked, in one place.</p>
      </div>
      <a class="btn btn-primary" href="<?= e(url('/patient/book')) ?>">Book a new visit</a>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Upcoming</h2><span class="badge badge-info"><?= count($upcoming) ?> scheduled</span></div>
      <?php if (!$upcoming): ?>
        <div class="empty"><strong>No upcoming appointments</strong>Choose a doctor and pick a slot that fits your day.<br><br><a class="btn btn-soft btn-sm" href="<?= e(url('/doctors')) ?>">Find a doctor</a></div>
      <?php else: foreach ($upcoming as $appt): ?>
        <div class="appt-card">
          <div class="appt-date">
            <strong><?= e(date('j', strtotime($appt['appointment_date']))) ?></strong>
            <span><?= e(date('M', strtotime($appt['appointment_date']))) ?></span>
          </div>
          <div class="appt-info">
            <h3><?= e($appt['doctor_name']) ?> · <?= e($appt['specialty']) ?></h3>
            <p><?= e(date('l, M j', strtotime($appt['appointment_date']))) ?> at <?= e(date('g:i A', strtotime($appt['appointment_time']))) ?> · <?= e($appt['room'] ?: 'Main clinic') ?></p>
            <p style="margin-top:6px">Ref <span class="mono"><?= e($appt['reference']) ?></span></p>
          </div>
          <div class="appt-side">
            <?= status_badge($appt['status']) ?>
            <form method="post" action="<?= e(url('/patient/cancel/' . $appt['id'])) ?>" data-confirm="Cancel this appointment?">
              <?= csrf_field() ?>
              <button class="btn btn-ghost btn-sm" type="submit">Cancel</button>
            </form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>History</h2></div>
      <?php if (!$past): ?>
        <div class="empty"><strong>No past visits yet</strong>Your completed and cancelled appointments will appear here.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Date</th><th>Doctor</th><th>Specialty</th><th>Reference</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($past as $appt): ?>
              <tr>
                <td><?= e(short_date($appt['appointment_date'])) ?><br><small><?= e(date('g:i A', strtotime($appt['appointment_time']))) ?></small></td>
                <td><?= e($appt['doctor_name']) ?></td>
                <td><?= e($appt['specialty']) ?></td>
                <td class="mono"><?= e($appt['reference']) ?></td>
                <td><?= status_badge($appt['status']) ?></td>
                <td><?php if ($appt['status'] === 'completed'): ?><a class="btn btn-soft btn-sm" href="<?= e(url('/patient/notes')) ?>">Notes</a><?php endif; ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
