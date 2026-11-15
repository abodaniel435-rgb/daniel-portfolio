<?php $heading = 'Access denied'; ?>
<section class="section section-narrow">
  <p class="eyebrow">403</p>
  <h1><?= e($heading) ?></h1>
  <p class="lede">Your account does not have permission to open this page.</p>
  <a class="btn btn-primary" href="<?= e(url('/dashboard')) ?>">Back to dashboard</a>
</section>
