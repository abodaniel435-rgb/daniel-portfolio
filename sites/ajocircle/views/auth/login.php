<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Sign in — AjoCircle') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
<div class="auth">
  <aside class="auth-art">
    <a class="brand" href="<?= e(url('/')) ?>">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 12a8 8 0 1 1-2.34-5.66"/><path d="M20 4v4h-4"/></svg>
      </span>
      Ajo<em style="color:var(--gold)">Circle</em>
    </a>
    <div>
      <p class="eyebrow" style="color:var(--gold)">Welcome back</p>
      <h1>The ledger your circle <em>already agreed on</em>.</h1>
      <p>Sign in to see your next due contribution, your payout position and every entry your organiser has verified.</p>
      <div class="auth-points">
        <div class="auth-point"><i>✓</i><span>Contribution schedule generated from your circle start date</span></div>
        <div class="auth-point"><i>✓</i><span>Payment references kept on record for every round</span></div>
        <div class="auth-point"><i>✓</i><span>Printable statement for the whole circle year</span></div>
      </div>
    </div>
    <small style="color:#8f96bd">© <?= date('Y') ?> AjoCircle · Lagos, Nigeria</small>
  </aside>

  <main class="auth-form-wrap">
    <div class="auth-form">
      <a href="<?= e(url('/')) ?>" style="font-size:.82rem;color:var(--muted);font-weight:700">← Back to site</a>
      <h2 style="margin-top:16px">Sign in</h2>
      <p class="sub">Members and organisers use the same secure sign-in.</p>

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

      <?php $demoPassword = (string)env('DEMO_PASSWORD', ''); $adminPassword = (string)env('ADMIN_PASSWORD', ''); if (env('APP_DEBUG') && $demoPassword !== ''): ?>
        <div class="auth-demo">
          <b>Demo accounts</b><br>
          Member · member@ajocircle.test · <?= e($demoPassword) ?><br>
          <?php if ($adminPassword !== ''): ?>Organiser · abodaniel435@gmail.com · <?= e($adminPassword) ?><?php endif; ?>
        </div>
      <?php endif; ?>

      <p class="auth-foot">New to AjoCircle? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
    </div>
  </main>
</div>
</body>
</html>
