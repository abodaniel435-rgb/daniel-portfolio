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
    <h1><?= e($site['labels']['portal']) ?></h1>
    <p>Track every request, keep references in one place and book again in two taps.</p>
    <ul class="auth-points">
      <?php foreach ((array)array_slice((array)cfg('copy.sections', []), 0, 1) as $section): ?>
        <?php foreach (array_slice((array)($section['bullets'] ?? []), 0, 3) as $bullet): ?>
          <li><?= e($bullet) ?></li>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="auth-form-wrap">
    <form class="auth-form" method="post" action="<?= e(url('/login')) ?>">
      <?= csrf_field() ?>
      <h2>Welcome back</h2>
      <p class="auth-sub">Sign in to see your <?= e($site['labels']['txn_plural']) ?>.</p>
      <?php if (errors()): ?>
        <div class="alert alert-error"><?= e(errors()['email'] ?? 'Please check your details.') ?></div>
      <?php endif; ?>
      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required autocomplete="current-password">
      <button class="btn btn-primary btn-block" type="submit">Sign in</button>
      <?php if (cfg('modules.auth', false)): ?>
        <p class="auth-foot">New here? <a href="<?= e(url('/register')) ?>">Create an account</a> — or <a href="<?= e(url('/book')) ?>"><?= e(strtolower($site['labels']['book'])) ?></a> without one.</p>
      <?php endif; ?>
    </form>
  </div>
</section>
