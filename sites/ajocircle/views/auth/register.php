<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Create your account — AjoCircle') ?></title>
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
      <p class="eyebrow" style="color:var(--gold)">Join the circle</p>
      <h1>Save together. <em>Record everything.</em></h1>
      <p>Create an account, take a position in an open circle, and never argue about a contribution again.</p>
      <div class="auth-points">
        <div class="auth-point"><i>1</i><span>Browse open circles and see the full rotation before joining</span></div>
        <div class="auth-point"><i>2</i><span>Join in one click — your schedule and position appear instantly</span></div>
        <div class="auth-point"><i>3</i><span>Track your next due date, running total and expected payout</span></div>
      </div>
    </div>
    <small style="color:#8f96bd">© <?= date('Y') ?> AjoCircle · Lagos, Nigeria</small>
  </aside>

  <main class="auth-form-wrap">
    <div class="auth-form">
      <a href="<?= e(url('/')) ?>" style="font-size:.82rem;color:var(--muted);font-weight:700">← Back to site</a>
      <h2 style="margin-top:16px">Create your account</h2>
      <p class="sub">Free for members. Your organiser verifies every contribution.</p>

      <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" style="margin-bottom:16px"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url('/register')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div>
          <label for="name">Full name</label>
          <input id="name" name="name" type="text" value="<?= e(old('name')) ?>" required maxlength="120" autocomplete="name" placeholder="Adaeze Nwosu">
          <?= field_error('name') ?>
        </div>
        <div class="form-row">
          <div>
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@example.com">
            <?= field_error('email') ?>
          </div>
          <div>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" type="tel" value="<?= e(old('phone')) ?>" required maxlength="40" autocomplete="tel" placeholder="+234 803 000 0000">
            <?= field_error('phone') ?>
          </div>
        </div>
        <div>
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password" placeholder="At least 10 characters">
          <?= field_error('password') ?>
          <p class="hint">Use at least 10 characters — a short phrase works well.</p>
        </div>
        <div>
          <label for="password_confirm">Confirm password</label>
          <input id="password_confirm" name="password_confirm" type="password" required minlength="10" autocomplete="new-password" placeholder="Repeat your password">
        </div>
        <button class="btn btn-primary btn-block" type="submit">Create account</button>
      </form>

      <p class="auth-foot">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
    </div>
  </main>
</div>
</body>
</html>
