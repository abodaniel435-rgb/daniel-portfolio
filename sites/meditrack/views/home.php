<?php $nextSlots = array_values(array_filter(array_column($doctors, 'next_slot'))); ?>
<section class="hero">
  <div class="hero-inner">
    <div class="reveal">
      <p class="eyebrow"><span class="pulse"></span> Clinic care, coordinated</p>
      <h1>See a doctor<br>without the <em>waiting room.</em></h1>
      <p>Book a verified doctor in under a minute, follow your appointment status in real time, and keep every consultation note in one secure place.</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('/doctors')) ?>">Find a doctor</a>
        <a class="btn btn-ghost" href="<?= e(url('/track')) ?>">Track an appointment</a>
      </div>
      <div class="hero-note"><span class="pulse"></span> Live availability · <?= (int)$stats['doctors'] ?> doctors accepting patients</div>
    </div>
    <div class="hero-panel reveal">
      <h3>Next available appointments</h3>
      <?php if ($doctors): foreach (array_slice($doctors, 0, 3) as $doctor): ?>
        <div class="mini-appt">
          <span class="time"><?= $doctor['next_slot'] ? e(date('D g:i', strtotime($doctor['next_slot']['date'] . ' ' . $doctor['next_slot']['time']))) : '—' ?></span>
          <div>
            <div class="who"><?= e($doctor['name']) ?></div>
            <div class="what"><?= e($doctor['specialty']) ?> · <?= money((float)$doctor['consultation_fee']) ?></div>
          </div>
          <span class="mini-pill"><?= $doctor['next_slot'] ? 'Open' : 'Full' ?></span>
        </div>
      <?php endforeach; else: ?>
        <p style="color:#b7d2ca;font-size:.9rem">Doctor schedules are being set up. Please check back shortly.</p>
      <?php endif; ?>
      <a class="btn btn-ghost btn-block" style="margin-top:18px" href="<?= e(url('/doctors')) ?>">Browse all doctors</a>
    </div>
  </div>
</section>

<div class="stats">
  <div class="stat"><strong><?= (int)$stats['doctors'] ?></strong><span>Doctors accepting patients</span></div>
  <div class="stat"><strong><?= (int)$stats['specialties'] ?></strong><span>Specialties available</span></div>
  <div class="stat"><strong><?= (int)$stats['completed'] ?></strong><span>Completed consultations</span></div>
  <div class="stat"><strong>&lt; 60s</strong><span>Average booking time</span></div>
</div>

<section class="section">
  <div class="split">
    <div class="reveal">
      <p class="eyebrow">Why MediTrack</p>
      <h2>Care that respects your time and your privacy.</h2>
      <p class="lede">Most clinics lose patients in the gap between “I need a doctor” and an actual appointment. MediTrack closes that gap with transparent schedules, automatic updates and a clean record of every visit.</p>
      <div class="grid grid-2" style="margin-top:26px">
        <div class="card card-hover"><h3>Real availability</h3><p style="font-size:.9rem;color:var(--muted);margin:0">Slots are calculated from each doctor's weekly schedule and confirmed bookings — no double-booking.</p></div>
        <div class="card card-hover"><h3>Automatic updates</h3><p style="font-size:.9rem;color:var(--muted);margin:0">Confirmations, reminders and cancellations are emailed and logged automatically.</p></div>
      </div>
    </div>
    <div class="reveal">
      <div class="card" style="padding:0;overflow:hidden">
        <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=80" alt="Doctor speaking with a patient in a bright clinic" style="height:390px;width:100%;object-fit:cover">
        <div style="padding:22px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
          <div><strong style="display:block">Patient-first by design</strong><span style="font-size:.82rem;color:var(--muted)">Clear next steps at every stage</span></div>
          <span class="badge badge-ok">Secure sessions</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:10px">
  <p class="eyebrow reveal">What we handle</p>
  <h2 class="reveal">One platform, three calm workflows.</h2>
  <div class="grid grid-3" style="margin-top:26px">
    <article class="card card-hover service reveal">
      <div class="icon">◷</div>
      <h3>Booking that actually confirms</h3>
      <p>Patients pick a real open slot. The doctor confirms, the system emails both sides and the schedule updates instantly.</p>
    </article>
    <article class="card card-hover service reveal">
      <div class="icon">✚</div>
      <h3>Consultations with a record</h3>
      <p>Doctors add diagnosis, notes and prescriptions. Patients see the summary in their private portal after the visit.</p>
    </article>
    <article class="card card-hover service reveal">
      <div class="icon">◎</div>
      <h3>Clinic oversight</h3>
      <p>Administrators see daily load, pending requests, revenue and every email sent — with one-click automation runs.</p>
    </article>
  </div>
</section>

<section class="section">
  <p class="eyebrow reveal">How it works</p>
  <h2 class="reveal">From “I need care” to “I'm booked”.</h2>
  <div class="steps" style="margin-top:26px">
    <div class="step reveal"><h3>Choose a doctor</h3><p>Compare specialties, consultation fees and the next available slot.</p></div>
    <div class="step reveal"><h3>Pick a real slot</h3><p>Only open times are shown. Your booking is held immediately and sent for confirmation.</p></div>
    <div class="step reveal"><h3>Arrive informed</h3><p>Get an email confirmation, a reminder the day before, and your notes afterwards.</p></div>
  </div>
</section>

<?php if ($doctors): ?>
<section class="section" style="padding-top:20px">
  <div class="page-head reveal">
    <div>
      <p class="eyebrow" style="margin-bottom:8px">Meet the team</p>
      <h2 style="margin:0">Doctors accepting patients today.</h2>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/doctors')) ?>">View all doctors</a>
  </div>
  <div class="grid grid-3" style="margin-top:24px">
    <?php foreach ($doctors as $doctor): ?>
      <article class="doctor-card reveal">
        <div class="doctor-photo" style="background-image:url('<?= e($doctor['photo_url'] ?: 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=800&q=80') ?>')"></div>
        <div class="doctor-body">
          <span class="doctor-spec"><?= e($doctor['specialty']) ?></span>
          <h3><?= e($doctor['name']) ?></h3>
          <p class="doctor-bio"><?= e($doctor['bio'] ?: 'Experienced clinician focused on clear communication and practical follow-up.') ?></p>
          <div class="doctor-meta">
            <span><?= money((float)$doctor['consultation_fee']) ?></span>
            <?php if ($doctor['next_slot']): ?><span>Next: <?= e(date('D, M j · g:i A', strtotime($doctor['next_slot']['date'] . ' ' . $doctor['next_slot']['time']))) ?></span><?php endif; ?>
          </div>
          <div class="doctor-actions">
            <a class="btn btn-primary btn-sm" href="<?= e(url('/patient/book?doctor=' . $doctor['id'])) ?>">Book a visit</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="card reveal" style="background:linear-gradient(120deg,var(--forest),#11594d);border:0;color:#fff;padding:44px;display:flex;justify-content:space-between;align-items:center;gap:28px;flex-wrap:wrap">
    <div style="max-width:60ch">
      <h2 style="color:#fff;margin-bottom:10px">Your next appointment is one screen away.</h2>
      <p style="color:#c9ded7;margin:0">Create a patient account to book, cancel and review your consultation notes securely.</p>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <a class="btn" style="background:#8fe3c8;color:#0a2520" href="<?= e(url('/register')) ?>">Create patient account</a>
      <a class="btn btn-ghost" href="<?= e(url('/doctors')) ?>" style="border-color:rgba(255,255,255,.3);color:#fff">Browse doctors</a>
    </div>
  </div>
</section>
