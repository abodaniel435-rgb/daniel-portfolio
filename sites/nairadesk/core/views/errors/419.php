<?php $heading = 'Session expired'; ?>
<section class="section section-narrow">
  <p class="eyebrow">419</p>
  <h1><?= e($heading) ?></h1>
  <p class="lede">For your security, this form has expired. Go back, refresh the page and submit it again.</p>
  <a class="btn btn-primary" href="<?= e(url('/')) ?>">Return home</a>
</section>
