<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Consultation</h1>
        <p><?= e($appointment['patient_name']) ?> · <?= e(short_date($appointment['appointment_date'])) ?> at <?= e(date('g:i A', strtotime($appointment['appointment_time']))) ?></p>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/doctor/schedule')) ?>">Back to schedule</a>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Patient summary</h2><?= status_badge($appointment['status']) ?></div>
      <dl class="kv">
        <dt>Reference</dt><dd class="mono"><?= e($appointment['reference']) ?></dd>
        <dt>Patient</dt><dd><?= e($appointment['patient_name']) ?></dd>
        <dt>Phone</dt><dd><?= e($appointment['phone'] ?: '—') ?></dd>
        <dt>Reason for visit</dt><dd><?= e($appointment['reason']) ?></dd>
      </dl>
    </div>

    <form class="panel" method="post" action="<?= e(url('/doctor/consultation/' . $appointment['id'])) ?>">
      <?= csrf_field() ?>
      <div class="panel-head"><h2>Clinical notes</h2><span class="badge badge-info">Saved securely</span></div>
      <div class="form-grid">
        <div>
          <label for="diagnosis">Diagnosis / impression</label>
          <input id="diagnosis" name="diagnosis" maxlength="255" required value="<?= e($note['diagnosis'] ?? '') ?>" placeholder="e.g. Acute uncomplicated malaria">
          <?= field_error('diagnosis') ?>
        </div>
        <div>
          <label for="notes">Consultation notes</label>
          <textarea id="notes" name="notes" required placeholder="History, examination findings, plan and advice."><?= e($note['notes'] ?? '') ?></textarea>
          <?= field_error('notes') ?>
        </div>
        <div class="form-row">
          <div>
            <label for="prescription">Prescription (optional)</label>
            <textarea id="prescription" name="prescription" style="min-height:100px" placeholder="Drug, dose, frequency, duration."><?= e($note['prescription'] ?? '') ?></textarea>
          </div>
          <div>
            <label for="follow_up_date">Follow-up date (optional)</label>
            <input id="follow_up_date" name="follow_up_date" type="date" value="<?= e($note['follow_up_date'] ?? '') ?>">
            <p class="hint">Saving notes marks this visit as completed and emails the patient.</p>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Save notes & complete visit</button>
      </div>
    </form>
  </div>
</div>
