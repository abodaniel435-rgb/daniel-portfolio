<?php
$site = site_config();
$art = null;
foreach ((array)cfg('copy.sections', []) as $section) {
    if (!empty($section['image'])) {
        $art = $section;
        break;
    }
}
?>
<section class="auth-wrap">
  <div class="auth-art"<?= $art ? ' style="background-image:linear-gradient(160deg,rgba(0,0,0,.72),rgba(0,0,0,.35)),url(' . e($art['image']) . ')"' : '' ?>>
    <p class="eyebrow eyebrow-light"><?= e($site['tagline']) ?></p>
    <h1>One place for every visit.</h1>
    <p>Create an account and your requests, references and updates stay together — on any device.</p>
  </div>
  <div class="auth-form-wrap">
    <form class="auth-form" method="post" action="<?= e(url('/register')) ?>">
      <?= csrf_field() ?>
      <h2>Create an account</h2>
      <p class="auth-sub">For <?= e($site['labels']['customer_plural']) ?>. It takes about twenty seconds.</p>
      <?php if (errors()): ?>
        <div class="alert alert-error"><?= e(implode(' ', errors())) ?></div>
      <?php endif; ?>
      <label for="name">Full name</label>
      <input id="name" name="name" value="<?= e(old('name')) ?>" required autocomplete="name">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
      <label for="phone">Phone</label>
      <input id="phone" name="phone" value="<?= e(old('phone')) ?>" required autocomplete="tel">
      <label for="password">Password <span class="label-hint">10+ characters</span></label>
      <input id="password" type="password" name="password" minlength="10" required autocomplete="new-password">
      <label for="password_confirm">Confirm password</label>
      <input id="password_confirm" type="password" name="password_confirm" minlength="10" required autocomplete="new-password">
      <button class="btn btn-primary btn-block" type="submit">Create account</button>
      <p class="auth-foot">Already registered? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
    </form>
  </div>
</section>
