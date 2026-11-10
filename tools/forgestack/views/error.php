<?php $active = $active ?? ''; ?>
<section class="error-state">
  <p class="eyebrow"><?= http_response_code() ?> · ForgeStack</p>
  <h1><?= fs_e($title ?? 'Something went wrong') ?></h1>
  <p class="lede"><?= fs_e($message ?? 'The request could not be completed.') ?></p>
  <div class="page-actions">
    <a class="btn btn-primary" href="/"><?= fs_icon('arrow-left', 16) ?> Back to dashboard</a>
    <a class="btn btn-ghost" href="/preflight">Run preflight</a>
  </div>
</section>
