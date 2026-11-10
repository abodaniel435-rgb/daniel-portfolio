<?php $heading = 'Page not found'; ?>
<section class="section section-narrow">
  <p class="eyebrow">404</p>
  <h1><?= e($heading) ?></h1>
  <p class="lede">The page you requested does not exist or has moved.</p>
  <a class="btn btn-primary" href="<?= e(url('/')) ?>">Return home</a>
</section>
