<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Sign in — FarmCoop') ?></title>
  <meta name="theme-color" content="#143d2b">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body>
<div class="auth">
  <aside class="auth-art">
    <a class="brand brand-light" href="<?= e(url('/')) ?>">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 32 32" fill="none"><path d="M7 26c0-10 6.5-16.5 18-18-1.2 11.5-7.5 18-18 18Z" fill="currentColor"/><path d="M7 26c4-5.5 8-9.5 14-12" stroke="#143d2b" stroke-width="1.8" stroke-linecap="round"/></svg>
      </span>
      <span class="brand-word">Farm<em>Coop</em></span>
    </a>
    <div>
      <p class="eyebrow" style="color:var(--gold)">Welcome back</p>
      <h1>The market opens <em>before sunrise.</em></h1>
      <p>Farmers manage live harvests and incoming orders. Buyers reserve verified produce and follow every step to pickup.</p>
      <div class="auth-points">
        <div class="auth-point"><i>✓</i><span>Live quantities that cannot be oversold</span></div>
        <div class="auth-point"><i>✓</i><span>One order trail from reservation to handover</span></div>
        <div class="auth-point"><i>₦</i><span>Farmers set the price — no middleman cut</span></div>
      </div>
    </div>
    <small>© <?= date('Y') ?> FarmCoop · Lagos, Nigeria</small>
  </aside>

  <main class="auth-form-wrap">
    <div class="auth-form">
      <a class="auth-back" href="<?= e(url('/')) ?>">← Back to the marketplace</a>
      <h2>Sign in</h2>
      <p class="sub">Farmers, buyers and administrators use the same secure sign-in.</p>

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
        <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
      </form>

      <p class="auth-foot">New to FarmCoop? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
      <p class="auth-foot" style="margin-top:10px"><a href="<?= e(url('/track')) ?>">Track an order without signing in →</a></p>
    </div>
  </main>
</div>
</body>
</html>
