<?php $site = site_config(); $hero = cfg('copy.hero'); ?>
<section class="hero">
  <div class="hero-inner">
    <div class="hero-copy">
      <p class="eyebrow"><span class="pulse"></span><?= e($hero['eyebrow']) ?></p>
      <h1><?= e($hero['headline']) ?><br><em><?= e($hero['accent']) ?></em></h1>
      <p class="hero-lede"><?= e($hero['lede']) ?></p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('/book')) ?>"><?= e($site['labels']['book']) ?></a>
        <a class="btn btn-outline" href="<?= e(url('/track')) ?>"><?= e($site['labels']['track']) ?></a>
      </div>
      <p class="hero-note"><?= e($hero['note']) ?></p>
    </div>
    <aside class="hero-panel">
      <h3><?= e($hero['panel_title']) ?></h3>
      <ul>
        <?php foreach ((array)$hero['panel'] as $row): ?>
          <li><strong><?= e($row[0]) ?></strong><span><?= e($row[1]) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <p class="hero-panel-foot"><?= e($hero['panel_foot']) ?></p>
      <div class="hero-live">
        <span class="pulse"></span>
        <?= (int)$stats['total'] ?> requests on record · <?= (int)$stats['completed'] ?> completed
      </div>
    </aside>
  </div>
</section>

<?php if (!empty($sections)): ?>
  <?php foreach ($sections as $section): ?>
    <?php include BASE_PATH . '/views/partials/section.php'; ?>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (cfg('modules.orders', false) && $products): ?>
  <section class="section section-tight">
    <div class="section-head">
      <div>
        <p class="eyebrow"><?= $site['profile'] === 'restaurant' ? 'From the counter' : 'From the dispatch desk' ?></p>
        <h2><?= $site['profile'] === 'restaurant' ? 'Take something home.' : 'Packing, without the runaround.' ?></h2>
      </div>
      <a class="btn btn-outline" href="<?= e(url('/shop')) ?>"><?= e($site['labels']['shop_title']) ?></a>
    </div>
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <article class="product-card">
          <div class="product-top">
            <span class="product-sku"><?= e($product['sku']) ?></span>
            <?= $product['stock'] > 0 ? '<span class="badge badge-ok">In stock</span>' : '<span class="badge badge-bad">Sold out</span>' ?>
          </div>
          <h3><?= e($product['name']) ?></h3>
          <p><?= e($product['description']) ?></p>
          <div class="product-foot">
            <strong><?= money_fmt((float)$product['price']) ?></strong>
            <span><?= (int)$product['stock'] ?> left</span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
