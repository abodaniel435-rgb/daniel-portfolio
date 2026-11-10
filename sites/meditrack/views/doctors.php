<section class="section" style="padding-bottom:26px">
  <p class="eyebrow">Our clinicians</p>
  <h1 style="font-size:clamp(2rem,4vw,3.2rem)">Find the right doctor.</h1>
  <p class="lede">Verified specialists with transparent fees and live availability. Choose a doctor to see open appointment times.</p>
  <div class="tab-links" style="margin-top:22px" role="group" aria-label="Filter by specialty">
    <a class="<?= $selected === '' ? 'active' : '' ?>" href="<?= e(url('/doctors')) ?>">All specialties</a>
    <?php foreach ($specialties as $specialty): ?>
      <a class="<?= $selected === $specialty ? 'active' : '' ?>" href="<?= e(url('/doctors?specialty=' . urlencode($specialty))) ?>"><?= e($specialty) ?></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section" style="padding-top:0">
  <?php if (!$doctors): ?>
    <div class="empty"><strong>No doctors in this specialty yet</strong>Try another specialty or view all clinicians.</div>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($doctors as $doctor): ?>
        <article class="doctor-card reveal">
          <div class="doctor-photo" style="background-image:url('<?= e($doctor['photo_url'] ?: 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=800&q=80') ?>')"></div>
          <div class="doctor-body">
            <span class="doctor-spec"><?= e($doctor['specialty']) ?></span>
            <h3><?= e($doctor['name']) ?></h3>
            <p class="doctor-bio"><?= e($doctor['bio'] ?: 'Focused on clear communication, accurate diagnosis and practical follow-up.') ?></p>
            <div class="doctor-meta">
              <span><?= e($doctor['qualifications'] ?: 'Licensed clinician') ?></span>
              <?php if ($doctor['room']): ?><span><?= e($doctor['room']) ?></span><?php endif; ?>
              <span><?= money((float)$doctor['consultation_fee']) ?></span>
            </div>
            <div class="doctor-meta" style="margin-top:0">
              <span><?= $doctor['next_slot'] ? 'Next: ' . e(date('D, M j · g:i A', strtotime($doctor['next_slot']['date'] . ' ' . $doctor['next_slot']['time']))) : 'No open slots in 3 weeks' ?></span>
            </div>
            <div class="doctor-actions">
              <a class="btn btn-primary btn-sm" href="<?= e(url('/patient/book?doctor=' . $doctor['id'])) ?>">See times & book</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
