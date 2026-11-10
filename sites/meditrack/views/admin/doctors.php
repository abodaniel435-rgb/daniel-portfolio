<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Doctors</h1><p>Manage clinician profiles, fees and booking availability.</p></div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Clinician directory</h2><span class="badge badge-info"><?= count($doctors) ?> profiles</span></div>
      <?php if (!$doctors): ?>
        <div class="empty"><strong>No doctors yet</strong>Create the first clinician profile below.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Doctor</th><th>Specialty</th><th>Fee</th><th>Room</th><th>Appointments</th><th>Booking</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($doctors as $doctor): ?>
              <tr>
                <td><strong><?= e($doctor['name']) ?></strong><br><small><?= e($doctor['email']) ?><?= $doctor['is_active'] ? '' : ' · account disabled' ?></small></td>
                <td><?= e($doctor['specialty']) ?></td>
                <td><?= money((float)$doctor['consultation_fee']) ?></td>
                <td><?= e($doctor['room'] ?: '—') ?></td>
                <td><?= (int)$doctor['appointments'] ?></td>
                <td><?= $doctor['is_accepting'] ? '<span class="badge badge-ok">Accepting</span>' : '<span class="badge badge-bad">Paused</span>' ?></td>
                <td>
                  <form method="post" action="<?= e(url('/admin/doctors/' . $doctor['id'] . '/toggle')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$doctor['id'] ?>">
                    <button class="btn btn-ghost btn-sm" type="submit"><?= $doctor['is_accepting'] ? 'Pause' : 'Resume' ?></button>
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
      <div class="panel-head"><h2>Add a doctor</h2><span class="badge badge-warn">Admin only</span></div>
      <form method="post" action="<?= e(url('/admin/doctors')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-row">
          <div><label for="name">Full name</label><input id="name" name="name" required value="<?= e(old('name')) ?>" placeholder="Dr. Amara Okonkwo"><?= field_error('name') ?></div>
          <div><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= e(old('email')) ?>" placeholder="doctor@clinic.com"><?= field_error('email') ?></div>
        </div>
        <div class="form-row">
          <div><label for="password">Temporary password</label><input id="password" name="password" type="password" minlength="10" required autocomplete="new-password"><?= field_error('password') ?></div>
          <div><label for="phone">Phone</label><input id="phone" name="phone" value="<?= e(old('phone')) ?>" placeholder="+234 803 000 0000"></div>
        </div>
        <div class="form-row">
          <div>
            <label for="specialty">Specialty</label>
            <input id="specialty" name="specialty" list="specialty-list" required value="<?= e(old('specialty')) ?>" placeholder="General Practice">
            <datalist id="specialty-list"><?php foreach ($specialties as $specialty): ?><option value="<?= e($specialty) ?>"><?php endforeach; ?></datalist>
            <?= field_error('specialty') ?>
          </div>
          <div><label for="consultation_fee">Consultation fee (₦)</label><input id="consultation_fee" name="consultation_fee" type="number" min="0" step="100" required value="<?= e(old('consultation_fee')) ?>"><?= field_error('consultation_fee') ?></div>
        </div>
        <div class="form-row">
          <div><label for="qualifications">Qualifications</label><input id="qualifications" name="qualifications" value="<?= e(old('qualifications')) ?>" placeholder="MBBS, FMCP"></div>
          <div><label for="room">Room</label><input id="room" name="room" value="<?= e(old('room')) ?>" placeholder="Room 2A"></div>
        </div>
        <div><label for="photo_url">Photo URL (optional)</label><input id="photo_url" name="photo_url" type="url" value="<?= e(old('photo_url')) ?>" placeholder="https://…"></div>
        <div><label for="bio">Short bio</label><textarea id="bio" name="bio" style="min-height:90px" placeholder="Focus areas and approach to care."><?= e(old('bio')) ?></textarea></div>
        <button class="btn btn-primary" type="submit">Create doctor account</button>
        <p class="hint">Creates the login, profile and default Mon/Wed/Fri 09:00–15:00 availability. The doctor can change their password after signing in.</p>
      </form>
    </div>
  </div>
</div>
