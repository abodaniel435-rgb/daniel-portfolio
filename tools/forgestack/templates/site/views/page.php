<?php $site = site_config(); ?>
<section class="page-head">
  <div class="page-head-inner">
    <p class="eyebrow"><?= e($site['app_name']) ?></p>
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-lede"><?= e($pageLede) ?></p>
  </div>
</section>

<?php foreach ($sections as $section): ?>
  <?php include BASE_PATH . '/views/partials/section.php'; ?>
<?php endforeach; ?>

<section class="section section-tight">
  <div class="inline-cta">
    <div>
      <h2>Ready when you are.</h2>
      <p>Book online in under a minute, or call <?= e($site['contact']['phone']) ?> and we will do it for you.</p>
    </div>
    <div class="cta-actions">
      <a class="btn btn-primary" href="<?= e(url('/book')) ?>"><?= e($site['labels']['book']) ?></a>
      <a class="btn btn-outline" href="<?= e(url('/contact')) ?>">Contact</a>
    </div>
  </div>
</section>
