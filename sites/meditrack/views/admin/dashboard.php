<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Clinic overview</h1>
        <p><?= e(date('l, F j, Y')) ?> · live schedule and performance.</p>
      </div>
      <a class="btn btn-ghost" href="<?= e(url('/admin/outbox')) ?>">Email outbox</a>
    </div>

    <div class="grid grid-4" style="margin-bottom:22px">
      <div class="stat" style="margin:0"><strong><?= (int)$stats['today'] ?></strong><span>Appointments today</span></div>
      <div class="stat" style="margin:0"><strong><?= (int)$stats['pending'] ?></strong><span>Pending confirmation</span></div>
      <div class="stat" style="margin:0"><strong><?= (int)$stats['patients'] ?></strong><span>Active patients</span></div>
      <div class="stat" style="margin:0"><strong><?= money((float)$stats['revenue']) ?></strong><span>Completed consultation value</span></div>
    </div>

    <div class="grid grid-2" style="margin-bottom:22px">
      <div class="panel" style="margin:0">
        <div class="panel-head"><h2>Last 7 days</h2><span class="badge badge-info"><?= array_sum(array_column($series, 'count')) ?> appointments</span></div>
        <?php $max = max(1, ...array_column($series, 'count')); ?>
        <div class="chart">
          <?php foreach ($series as $day): ?>
            <div class="chart-bar">
              <i style="height:<?= max(6, (int)round($day['count'] / $max * 100)) ?>%"><span><?= (int)$day['count'] ?></span></i>
              <b><?= e($day['label']) ?></b>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="panel" style="margin:0">
        <div class="panel-head"><h2>Recent bookings</h2><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/appointments')) ?>">View all</a></div>
        <?php if (!$recent): ?>
          <div class="empty" style="padding:24px"><strong>No bookings yet</strong>Requests appear here as patients book.</div>
        <?php else: ?>
          <div style="display:grid;gap:12px">
            <?php foreach ($recent as $row): ?>
              <div style="display:flex;align-items:center;gap:12px;padding-bottom:12px;border-bottom:1px solid #eef3f1">
                <div style="flex:1;min-width:0">
                  <strong style="font-size:.88rem;display:block"><?= e($row['patient_name']) ?></strong>
                  <span style="font-size:.76rem;color:var(--muted)"><?= e($row['doctor_name']) ?> · <?= e(short_date($row['appointment_date'])) ?></span>
                </div>
                <?= status_badge($row['status']) ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Today's schedule</h2><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/appointments')) ?>">Manage appointments</a></div>
      <?php if (!$todayList): ?>
        <div class="empty"><strong>No appointments today</strong>The clinic schedule is clear.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Specialty</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($todayList as $appt): ?>
              <tr>
                <td><?= e(date('g:i A', strtotime($appt['appointment_time']))) ?></td>
                <td><?= e($appt['patient_name']) ?></td>
                <td><?= e($appt['doctor_name']) ?></td>
                <td><?= e($appt['specialty']) ?></td>
                <td><?= status_badge($appt['status']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
