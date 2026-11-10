<?php /** Staff sign-in (dispatcher, courier, admin). Standalone layout. */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Sign in — RoutePilot') ?></title>
  <meta name="theme-color" content="#0b1220">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="theme-dark">
<div class="auth">
  <aside class="auth-art">
    <a class="brand" href="<?= e(url('/')) ?>">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.3 2.7a1 1 0 0 1 .22 1.06l-7.2 17.2a1 1 0 0 1-1.86.05l-2.4-6.1-6.1-2.4a1 1 0 0 1 .05-1.86l17.2-7.2a1 1 0 0 1 1.09.25Z"/></svg>
      </span>
      Route<em>Pilot</em>
    </a>
    <div style="position:relative;z-index:1">
      <p class="eyebrow" style="color:var(--amber-soft)">Dispatch desk</p>
      <h1>The whole route, in one screen.</h1>
      <p>Sign in to assign riders, update parcel statuses and keep a clean audit trail of every delivery in Lagos.</p>
      <div class="auth-points">
        <div class="auth-point"><i>✓</i><span>Dispatchers see every open parcel and rider load</span></div>
        <div class="auth-point"><i>✓</i><span>Couriers only see the parcels assigned to them</span></div>
        <div class="auth-point"><i>✓</i><span>Every status update is timestamped, located and emailed</span></div>
      </div>
    </div>
    <small style="color:#8c9db6;position:relative;z-index:1">© <?= date('Y') ?> RoutePilot Logistics · Lagos, Nigeria</small>
  </aside>

  <main class="auth-form-wrap">
    <div class="auth-form">
      <a class="auth-back" href="<?= e(url('/')) ?>">← Back to site</a>
      <h2 style="margin-top:20px">Sign in</h2>
      <p class="sub">Dispatcher, courier and administrator accounts all use this secure sign-in.</p>

      <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" style="margin-bottom:16px"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url('/login')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div>
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@routepilot.test">
          <?= field_error('email') ?>
        </div>
        <div>
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Your password">
        </div>
        <button class="btn btn-primary btn-block" type="submit">Sign in to RoutePilot</button>
      </form>

      <?php $demoPassword = (string)env('DEMO_PASSWORD', ''); if (env('APP_DEBUG') && $demoPassword !== ''): ?>
        <div class="auth-demo">
          <b>Demo accounts</b><br>
          Dispatcher · dispatcher@routepilot.test<br>
          Courier · courier@routepilot.test · courier2@routepilot.test<br>
          Admin · abodaniel435@gmail.com<br>
          Password · <?= e($demoPassword) ?> (admin uses ADMIN_PASSWORD)
        </div>
      <?php endif; ?>

      <p class="auth-foot">New rider? <a href="<?= e(url('/register')) ?>">Create a courier account</a></p>
      <p class="auth-foot" style="margin-top:8px"><a href="<?= e(url('/track')) ?>">Track a parcel without signing in</a></p>
    </div>
  </main>
</div>
</body>
</html>
