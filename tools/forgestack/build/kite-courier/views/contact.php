<?php $site = site_config(); $user = Auth::user(); ?>
<section class="page-head page-head-compact">
  <div class="page-head-inner">
    <p class="eyebrow">Contact</p>
    <h1>Talk to a person.</h1>
    <p class="page-lede"><?= e($site['copy']['contact_intro']) ?></p>
  </div>
</section>

<section class="section section-form">
  <div class="form-shell">
    <form class="card form-card" method="post" action="<?= e(url('/contact')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="form-row">
        <div>
          <label for="name">Name</label>
          <input id="name" name="name" value="<?= e(old('name', $user['name'] ?? '')) ?>" required autocomplete="name">
          <?= field_error('name') ?>
        </div>
        <div>
          <label for="email">Email</label>
          <input id="email" type="email" name="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" required autocomplete="email">
          <?= field_error('email') ?>
        </div>
      </div>
      <div class="form-row">
        <div>
          <label for="phone">Phone <span class="label-hint">optional</span></label>
          <input id="phone" name="phone" value="<?= e(old('phone', $user['phone'] ?? '')) ?>" autocomplete="tel">
        </div>
        <div>
          <label for="subject">Subject</label>
          <input id="subject" name="subject" value="<?= e(old('subject')) ?>" required maxlength="160">
          <?= field_error('subject') ?>
        </div>
      </div>
      <label for="body">Message</label>
      <textarea id="body" name="body" rows="7" required minlength="10" maxlength="3000"><?= e(old('body')) ?></textarea>
      <?= field_error('body') ?>
      <div class="form-submit">
        <button class="btn btn-primary" type="submit">Send message</button>
        <span class="form-total">We reply within one working day.</span>
      </div>
    </form>

    <aside class="form-aside">
      <div class="aside-card">
        <h3>Find us</h3>
        <p class="aside-address"><?= e($site['contact']['address']) ?></p>
        <p class="aside-hours"><?= e($site['copy']['footer']['hours']) ?></p>
        <p><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $site['contact']['phone'])) ?>"><?= e($site['contact']['phone']) ?></a></p>
        <p><a href="mailto:<?= e($site['contact']['email']) ?>"><?= e($site['contact']['email']) ?></a></p>
      </div>
      <div class="aside-card">
        <h3>Already booked?</h3>
        <p>Track a request with the reference from your confirmation email — no sign-in needed.</p>
        <a class="text-link" href="<?= e(url('/track')) ?>"><?= e($site['labels']['track']) ?> →</a>
      </div>
    </aside>
  </div>
</section>
