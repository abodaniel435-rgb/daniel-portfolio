<?php /** Courier self-registration. Dispatcher and admin accounts are created by an admin. */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Courier sign-up — RoutePilot') ?></title>
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
      <p class="eyebrow" style="color:var(--amber-soft)">Rider network</p>
      <h1>Your queue, your zone, your record.</h1>
      <p>Join the RoutePilot rider network. Parcels are assigned to your zone, and every pickup, transfer and drop-off is recorded against your name.</p>
      <div class="auth-points">
        <div class="auth-point"><i>✓</i><span>See only the parcels assigned to you</span></div>
        <div class="auth-point"><i>✓</i><span>Update status with a location and note in seconds</span></div>
        <div class="auth-point"><i>✓</i><span>Build a verifiable delivery history</span></div>
      </div>
    </div>
    <small style="color:#8c9db6;position:relative;z-index:1">© <?= date('Y') ?> RoutePilot Logistics · Lagos, Nigeria</small>
  </aside>

  <main class="auth-form-wrap">
    <div class="auth-form">
      <a class="auth-back" href="<?= e(url('/')) ?>">← Back to site</a>
      <h2 style="margin-top:18px">Create rider account</h2>
      <p class="sub">Dispatcher and administrator accounts are issued by the operations admin.</p>

      <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" style="margin-bottom:16px"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url('/register')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div>
          <label for="name">Full name</label>
          <input id="name" name="name" value="<?= e(old('name')) ?>" required autocomplete="name" placeholder="Emeka Nwosu">
          <?= field_error('name') ?>
        </div>
        <div>
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="rider@example.com">
          <?= field_error('email') ?>
        </div>
        <div>
          <label for="phone">Phone number</label>
          <input id="phone" name="phone" value="<?= e(old('phone')) ?>" required autocomplete="tel" placeholder="+234 803 000 0000">
          <?= field_error('phone') ?>
        </div>
        <div class="form-row">
          <div>
            <label for="vehicle">Vehicle</label>
            <select id="vehicle" name="vehicle" required>
              <?php foreach (rp_vehicles() as $vehicle): ?>
                <option value="<?= e($vehicle) ?>" <?= old('vehicle') === $vehicle ? 'selected' : '' ?>><?= e(ucfirst($vehicle)) ?></option>
              <?php endforeach; ?>
            </select>
            <?= field_error('vehicle') ?>
          </div>
          <div>
            <label for="zone">Primary zone</label>
            <input id="zone" name="zone" value="<?= e(old('zone', 'Lagos Mainland')) ?>" required placeholder="e.g. Lagos Island">
            <?= field_error('zone') ?>
          </div>
        </div>
        <div>
          <label for="plate_number">Plate number <span class="muted small">(optional)</span></label>
          <input id="plate_number" name="plate_number" value="<?= e(old('plate_number')) ?>" placeholder="LSD-482-XA">
        </div>
        <div class="form-row">
          <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="At least 10 characters">
            <?= field_error('password') ?>
          </div>
          <div>
            <label for="password_confirm">Confirm password</label>
            <input id="password_confirm" name="password_confirm" type="password" required autocomplete="new-password" placeholder="Repeat password">
          </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Join the rider network</button>
      </form>

      <p class="auth-foot">Already riding with us? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
    </div>
  </main>
</div>
</body>
</html>
