<?php $site = site_config(); ?>
<section class="page-head page-head-compact">
  <div class="page-head-inner">
    <p class="eyebrow">Contact</p>
    <h1>Come and see us.</h1>
    <p class="page-lede">The contact form was not part of this project’s feature set, so this page shares the details you need instead.</p>
  </div>
</section>

<section class="section section-form">
  <div class="contact-grid">
    <div class="card">
      <h2><?= e($site['app_name']) ?></h2>
      <dl class="detail-grid detail-grid-single">
        <div><dt>Address</dt><dd><?= e($site['contact']['address']) ?></dd></div>
        <div><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $site['contact']['phone'])) ?>"><?= e($site['contact']['phone']) ?></a></dd></div>
        <div><dt>Email</dt><dd><a href="mailto:<?= e($site['contact']['email']) ?>"><?= e($site['contact']['email']) ?></a></dd></div>
        <div><dt>Hours</dt><dd><?= e($site['copy']['footer']['hours']) ?></dd></div>
      </dl>
    </div>
    <div class="card">
      <h2>Book online</h2>
      <p>The fastest way to reach the team is the request form. It is logged, confirmed and tracked with a reference.</p>
      <a class="btn btn-primary" href="<?= e(url('/book')) ?>"><?= e($site['labels']['book']) ?></a>
      <p class="form-fineprint" style="margin-top:16px">Prefer email? Write to <a href="mailto:<?= e($site['contact']['email']) ?>"><?= e($site['contact']['email']) ?></a> and the team will reply within one working day.</p>
    </div>
  </div>
</section>
