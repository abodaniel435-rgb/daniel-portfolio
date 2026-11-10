<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Create an account — MediTrack') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
<div class="auth">
  <aside class="auth-art">
    <a class="brand" href="<?= e(url('/')) ?>"><span class="brand-mark">+</span>Medi<em style="color:#8fe3c8">Track</em></a>
    <div>
      <p class="eyebrow" style="color:#8fe3c8">Patient account</p>
      <h1>Your health record, in your hands.</h1>
      <p>Create one account to book appointments, follow their status and keep every consultation summary together.</p>
      <div class="auth-points">
        <div class="auth-point"><i>1</i><span>Book verified doctors in under a minute</span></div>
        <div class="auth-point"><i>2</i><span>Cancel or reschedule without a phone call</span></div>
        <div class="auth-point"><i>3</i><span>Read notes and prescriptions after every visit</span></div>
      </div>
    </div>
    <small style="color:#9fbcb3">© <?= date('Y') ?> MediTrack · Lagos, Nigeria</small>
  </aside>
  <main class="auth-form-wrap">
    <div class="auth-form">
      <a href="<?= e(url('/')) ?>" style="font-size:.82rem;color:var(--muted)">← Back to site</a>
      <h2 style="margin-top:18px">Create your account</h2>
      <p class="sub">Patient accounts are free. Clinical staff accounts are created by the clinic administrator.</p>

      <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" style="margin-bottom:16px"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url('/register')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div>
          <label for="name">Full name</label>
          <input id="name" name="name" value="<?= e(old('name')) ?>" required autocomplete="name" placeholder="Chidera Eze">
          <?= field_error('name') ?>
        </div>
        <div class="form-row">
          <div>
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@example.com">
            <?= field_error('email') ?>
          </div>
          <div>
            <label for="phone">Phone number</label>
            <input id="phone" name="phone" value="<?= e(old('phone')) ?>" required autocomplete="tel" placeholder="+234 803 000 0000">
            <?= field_error('phone') ?>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" minlength="10" required autocomplete="new-password" placeholder="At least 10 characters">
            <?= field_error('password') ?>
          </div>
          <div>
            <label for="password_confirm">Confirm password</label>
            <input id="password_confirm" name="password_confirm" type="password" minlength="10" required autocomplete="new-password">
            <?= field_error('password_confirm') ?>
          </div>
        </div>
        <p class="hint">By creating an account you agree to keep your login private. We never store plain-text passwords.</p>
        <button class="btn btn-primary btn-block" type="submit">Create patient account</button>
      </form>

      <p class="auth-foot">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
    </div>
  </main>
</div>
</body>
</html>
