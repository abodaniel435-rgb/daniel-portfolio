<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <?php if (!$doctor): ?>
      <div class="page-head">
        <div>
          <h1>Book a visit</h1>
          <p>Step 1 of 2 — choose a doctor. You will pick a time on the next screen.</p>
        </div>
      </div>
      <div class="panel">
        <div class="panel-head"><h2>Available doctors</h2><span class="badge badge-ok"><?= count($doctors) ?> accepting patients</span></div>
        <?php if (!$doctors): ?>
          <div class="empty"><strong>No doctors are accepting appointments right now</strong>Please check back shortly or contact the clinic.</div>
        <?php else: ?>
          <div class="pick-grid">
            <?php foreach ($doctors as $item): ?>
              <a class="pick" href="<?= e(url('/patient/book?doctor=' . $item['id'])) ?>">
                <img src="<?= e($item['photo_url'] ?: 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=200&q=80') ?>" alt="">
                <div>
                  <strong><?= e($item['name']) ?></strong>
                  <span><?= e($item['specialty']) ?> · <?= e($item['room'] ?: 'Main clinic') ?></span>
                </div>
                <span class="fee"><?= money((float)$item['consultation_fee']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="page-head">
        <div>
          <h1>Choose a time</h1>
          <p>Step 2 of 2 — booking with <?= e($doctor['name']) ?>, <?= e($doctor['specialty']) ?>.</p>
        </div>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/patient/book')) ?>">Change doctor</a>
      </div>

      <div class="panel">
        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:18px">
          <img src="<?= e($doctor['photo_url'] ?: 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=200&q=80') ?>" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover">
          <div style="flex:1;min-width:180px">
            <strong><?= e($doctor['name']) ?></strong>
            <div style="font-size:.84rem;color:var(--muted)"><?= e($doctor['qualifications'] ?: 'Licensed clinician') ?> · <?= e($doctor['room'] ?: 'Main clinic') ?></div>
          </div>
          <span class="badge badge-info"><?= money((float)$doctor['consultation_fee']) ?> consultation</span>
        </div>

        <?php if (!$slotsByDate): ?>
          <div class="empty"><strong>No open slots in the next two weeks</strong>This doctor's schedule is full. Try another doctor or check back later.</div>
        <?php else:
          $firstDate = array_key_first($slotsByDate);
          $errors = errors();
        ?>
          <form method="post" action="<?= e(url('/patient/book')) ?>" class="form-grid" data-booking>
            <?= csrf_field() ?>
            <input type="hidden" name="doctor_id" value="<?= (int)$doctor['id'] ?>">
            <input type="hidden" name="appointment_date" value="<?= e($firstDate) ?>">
            <input type="hidden" name="appointment_time" value="">

            <div>
              <label>Available days</label>
              <div class="day-strip">
                <?php $i = 0; foreach ($slotsByDate as $date => $slots): ?>
                  <button class="day-chip <?= $i === 0 ? 'active' : '' ?>" type="button" data-date="<?= e($date) ?>">
                    <strong><?= e(date('j', strtotime($date))) ?></strong>
                    <span><?= e(date('D M', strtotime($date))) ?></span>
                  </button>
                <?php $i++; endforeach; ?>
              </div>
            </div>

            <div>
              <label>Available times</label>
              <?php $i = 0; foreach ($slotsByDate as $date => $slots): ?>
                <div class="slot-panel <?= $i === 0 ? 'active' : '' ?>" data-date="<?= e($date) ?>">
                  <div class="slot-grid">
                    <?php foreach ($slots as $slot): ?>
                      <button class="slot" type="button" data-time="<?= e($slot) ?>"><?= e(date('g:i A', strtotime($slot))) ?></button>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php $i++; endforeach; ?>
              <?= field_error('appointment_time') ?>
              <div class="selection-summary"><span>Selected: <b data-summary-date><?= e($firstDate) ?></b></span><span>at <b data-summary-time>Not selected</b></span></div>
            </div>

            <div>
              <label for="reason">Reason for visit</label>
              <textarea id="reason" name="reason" required maxlength="500" placeholder="Briefly describe your symptoms or what you would like to discuss."><?= e(old('reason')) ?></textarea>
              <?= field_error('reason') ?>
            </div>

            <button class="btn btn-primary" type="submit" disabled>Request appointment</button>
            <p class="hint">Your slot is held only after you submit. The doctor will confirm and you will receive an email.</p>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
