<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Good day, Dr. <?= e(explode(' ', trim(str_replace('Dr.', '', $doctor['name'])))[0]) ?>.</h1>
        <p><?= e(date('l, F j')) ?> · <?= e($doctor['specialty']) ?> · <?= e($doctor['room'] ?: 'Main clinic') ?></p>
      </div>
    </div>

    <div class="grid grid-4" style="margin-bottom:22px">
      <div class="stat" style="margin:0"><strong><?= (int)$stats['today'] ?></strong><span>Today's appointments</span></div>
      <div class="stat" style="margin:0"><strong><?= (int)$stats['pending'] ?></strong><span>Awaiting confirmation</span></div>
      <div class="stat" style="margin:0"><strong><?= (int)$stats['completed'] ?></strong><span>Completed visits</span></div>
      <div class="stat" style="margin:0"><strong><?= (int)$stats['patients'] ?></strong><span>Patients seen</span></div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Today's schedule</h2><span class="badge badge-info"><?= count($today) ?> booked</span></div>
      <?php if (!$today): ?>
        <div class="empty"><strong>Nothing booked today</strong>New patient requests will appear here as soon as they are submitted.</div>
      <?php else: foreach ($today as $appt): ?>
        <div class="appt-card">
          <div class="appt-date">
            <strong><?= e(date('g', strtotime($appt['appointment_time']))) ?></strong>
            <span><?= e(date('A', strtotime($appt['appointment_time']))) ?></span>
          </div>
          <div class="appt-info">
            <h3><?= e($appt['patient_name']) ?></h3>
            <p><?= e($appt['reason']) ?></p>
            <p style="margin-top:5px"><?= e($appt['phone'] ?: 'No phone on file') ?> · <span class="mono"><?= e($appt['reference']) ?></span></p>
          </div>
          <div class="appt-side">
            <?= status_badge($appt['status']) ?>
            <div class="table-actions">
              <?php if ($appt['status'] === 'pending'): ?>
                <form method="post" action="<?= e(url('/doctor/appointment/' . $appt['id'] . '/status')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$appt['id'] ?>"><input type="hidden" name="status" value="confirmed"><button class="btn btn-primary btn-sm">Confirm</button></form>
                <form method="post" action="<?= e(url('/doctor/appointment/' . $appt['id'] . '/status')) ?>" data-confirm="Decline this appointment?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$appt['id'] ?>"><input type="hidden" name="status" value="cancelled"><button class="btn btn-ghost btn-sm">Decline</button></form>
              <?php elseif ($appt['status'] === 'confirmed'): ?>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/doctor/consultation/' . $appt['id'])) ?>">Start consult</a>
                <form method="post" action="<?= e(url('/doctor/appointment/' . $appt['id'] . '/status')) ?>" data-confirm="Mark this patient as a no-show?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$appt['id'] ?>"><input type="hidden" name="status" value="no_show"><button class="btn btn-ghost btn-sm">No-show</button></form>
              <?php else: ?>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('/doctor/consultation/' . $appt['id'])) ?>">View notes</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Upcoming</h2></div>
      <?php if (!$upcoming): ?>
        <div class="empty"><strong>No upcoming bookings</strong>Your confirmed and pending appointments will be listed here.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Reason</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($upcoming as $appt): ?>
              <tr>
                <td><?= e(short_date($appt['appointment_date'])) ?></td>
                <td><?= e(date('g:i A', strtotime($appt['appointment_time']))) ?></td>
                <td><?= e($appt['patient_name']) ?></td>
                <td><small><?= e(mb_strimwidth($appt['reason'], 0, 52, '…')) ?></small></td>
                <td><?= status_badge($appt['status']) ?></td>
                <td>
                  <?php if ($appt['status'] === 'pending'): ?>
                    <form method="post" action="<?= e(url('/doctor/appointment/' . $appt['id'] . '/status')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$appt['id'] ?>"><input type="hidden" name="status" value="confirmed"><button class="btn btn-soft btn-sm">Confirm</button></form>
                  <?php else: ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/doctor/consultation/' . $appt['id'])) ?>">Open</a>
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
