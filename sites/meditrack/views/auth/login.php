<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Sign in — MediTrack') ?></title>
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
      <p class="eyebrow" style="color:#8fe3c8">Welcome back</p>
      <h1>Care starts with a clear schedule.</h1>
      <p>Sign in to book appointments, review consultation notes or manage the clinic.</p>
      <div class="auth-points">
        <div class="auth-point"><i>✓</i><span>Real-time appointment status for patients</span></div>
        <div class="auth-point"><i>✓</i><span>One schedule for doctors and administrators</span></div>
        <div class="auth-point"><i>✓</i><span>Automatic confirmations and reminders</span></div>
      </div>
    </div>
    <small style="color:#9fbcb3">© <?= date('Y') ?> MediTrack · Lagos, Nigeria</small>
  </aside>
  <main class="auth-form-wrap">
    <div class="auth-form">
      <a href="<?= e(url('/')) ?>" style="font-size:.82rem;color:var(--muted)">← Back to site</a>
      <h2 style="margin-top:18px">Sign in</h2>
      <p class="sub">Patients, doctors and administrators use the same secure sign-in.</p>

      <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" style="margin-bottom:16px"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url('/login')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div>
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@example.com">
          <?= field_error('email') ?>
        </div>
        <div>
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Your password">
        </div>
        <button class="btn btn-primary btn-block" type="submit">Sign in</button>
      </form>

      <p class="auth-foot">New patient? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
    </div>
  </main>
</div>
</body>
</html>
