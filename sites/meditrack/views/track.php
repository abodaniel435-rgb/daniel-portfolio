<section class="section section-narrow">
  <p class="eyebrow">Appointment status</p>
  <h1 style="font-size:clamp(1.9rem,4vw,2.8rem)">Track an appointment.</h1>
  <p class="lede">Enter the reference from your confirmation email to see its latest status.</p>

  <form class="panel" method="get" action="<?= e(url('/track')) ?>" style="margin-top:24px">
    <div class="form-row" style="align-items:end">
      <div>
        <label for="ref">Appointment reference</label>
        <input id="ref" name="ref" value="<?= e($reference) ?>" placeholder="MT-260107-A1B2" required autocomplete="off">
      </div>
      <div><button class="btn btn-primary btn-block" type="submit">Check status</button></div>
    </div>
  </form>

  <?php if ($appointment):
      $steps = ['pending' => 0, 'confirmed' => 1, 'completed' => 2];
      $current = $steps[$appointment['status']] ?? -1;
      $cancelled = in_array($appointment['status'], ['cancelled', 'no_show'], true);
  ?>
    <div class="panel" style="margin-top:22px">
      <div class="panel-head">
        <div>
          <span class="eyebrow" style="margin-bottom:6px">Reference <?= e($appointment['reference']) ?></span>
          <h2 style="margin:0"><?= e($appointment['doctor_name']) ?></h2>
          <p style="color:var(--muted);font-size:.9rem;margin:4px 0 0"><?= e($appointment['specialty']) ?> · <?= e(short_date($appointment['appointment_date'])) ?> at <?= e(date('g:i A', strtotime($appointment['appointment_time']))) ?></p>
        </div>
        <?= status_badge($appointment['status']) ?>
      </div>

      <?php if ($cancelled): ?>
        <div class="note">This appointment is <?= e(str_replace('_', ' ', $appointment['status'])) ?>. Please book a new slot or contact the clinic.</div>
      <?php else: ?>
        <div class="timeline">
          <div class="tl-item <?= $current >= 0 ? 'done' : '' ?>"><span class="tl-dot">1</span><div class="tl-body"><strong>Request received</strong><span>Your booking was submitted and is waiting for the doctor.</span></div></div>
          <div class="tl-item <?= $current >= 1 ? 'done' : '' ?>"><span class="tl-dot">2</span><div class="tl-body"><strong>Confirmed</strong><span>The clinic has confirmed your slot. A reminder is sent the day before.</span></div></div>
          <div class="tl-item <?= $current >= 2 ? 'done' : '' ?>"><span class="tl-dot">3</span><div class="tl-body"><strong>Visit completed</strong><span>Consultation notes appear in your patient portal.</span></div></div>
        </div>
      <?php endif; ?>

      <dl class="kv" style="margin-top:18px">
        <dt>Reason</dt><dd><?= e($appointment['reason']) ?></dd>
        <dt>Location</dt><dd><?= e($appointment['room'] ?: 'Main clinic') ?></dd>
      </dl>
      <p style="margin-top:20px"><a class="btn btn-ghost btn-sm" href="<?= e(url('/login')) ?>">Sign in for full details</a></p>
    </div>
  <?php endif; ?>
</section>
