<?php $selectedRole = old('role', $_GET['role'] ?? 'buyer'); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Create an account — FarmCoop') ?></title>
  <meta name="theme-color" content="#143d2b">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <script>document.documentElement.classList.add('js');</script>
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
      <p class="eyebrow" style="color:var(--gold)">Join the coop</p>
      <h1>Sell what you grow. Buy what you <em>trust.</em></h1>
      <p>One account gives you a verified storefront or a verified supply chain — with a record both sides can point to.</p>
      <div class="auth-points">
        <div class="auth-point"><i>1</i><span>Farmers: publish harvests with live quantities and a pickup window</span></div>
        <div class="auth-point"><i>2</i><span>Buyers: reserve stock, choose pickup or delivery, track every status</span></div>
        <div class="auth-point"><i>3</i><span>Both sides get email confirmation at each step</span></div>
      </div>
    </div>
    <small>© <?= date('Y') ?> FarmCoop · Lagos, Nigeria</small>
  </aside>

  <main class="auth-form-wrap">
    <div class="auth-form" style="max-width:520px">
      <a class="auth-back" href="<?= e(url('/')) ?>">← Back to the marketplace</a>
      <h2>Create your account</h2>
      <p class="sub">Choose how you want to use FarmCoop. You can only pick one role per account.</p>

      <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" style="margin-bottom:16px"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url('/register')) ?>" class="form-grid">
        <?= csrf_field() ?>

        <fieldset class="fieldset">
          <legend>I am joining as</legend>
          <div class="role-picker">
            <div class="role-option">
              <input id="role-buyer" type="radio" name="role" value="buyer" <?= $selectedRole === 'buyer' ? 'checked' : '' ?>>
              <label for="role-buyer"><strong>Buyer</strong><span>Restaurant, hotel or market trader</span></label>
            </div>
            <div class="role-option">
              <input id="role-farmer" type="radio" name="role" value="farmer" <?= $selectedRole === 'farmer' ? 'checked' : '' ?>>
              <label for="role-farmer"><strong>Farmer</strong><span>Sell your harvest directly</span></label>
            </div>
          </div>
          <?= field_error('role') ?>
        </fieldset>

        <div class="form-row">
          <div>
            <label for="name">Full name</label>
            <input id="name" name="name" value="<?= e(old('name')) ?>" required autocomplete="name" placeholder="Ngozi Adeyemi">
            <?= field_error('name') ?>
          </div>
          <div>
            <label for="phone">Phone number</label>
            <input id="phone" name="phone" type="tel" value="<?= e(old('phone')) ?>" required autocomplete="tel" placeholder="+234 803 000 0000">
            <?= field_error('phone') ?>
          </div>
        </div>

        <div>
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@example.com">
          <?= field_error('email') ?>
        </div>

        <div id="farm-fields" data-farm-fields class="<?= $selectedRole === 'farmer' ? '' : 'hidden' ?>">
          <div class="panel-sub" style="display:grid;gap:14px">
            <div>
              <label for="farm_name">Farm name</label>
              <input id="farm_name" name="farm_name" value="<?= e(old('farm_name')) ?>" data-optional="true" placeholder="Ogunlana Family Farms">
              <?= field_error('farm_name') ?>
              <p class="hint">This is the storefront name buyers will see.</p>
            </div>
            <div class="form-row">
              <div>
                <label for="state">State</label>
                <select id="state" name="state" data-optional="true">
                  <option value="">Select state</option>
                  <?php foreach (nigerian_states() as $state): ?>
                    <option value="<?= e($state) ?>" <?= old('state') === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                  <?php endforeach; ?>
                </select>
                <?= field_error('state') ?>
              </div>
              <div>
                <label for="lga">LGA</label>
                <input id="lga" name="lga" value="<?= e(old('lga')) ?>" data-optional="true" placeholder="Abeokuta North">
              </div>
            </div>
            <div>
              <label for="produce_focus">Produce focus</label>
              <input id="produce_focus" name="produce_focus" value="<?= e(old('produce_focus')) ?>" data-optional="true" placeholder="Tomatoes, peppers and leafy greens">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password" placeholder="At least 10 characters">
            <?= field_error('password') ?>
          </div>
          <div>
            <label for="password_confirm">Confirm password</label>
            <input id="password_confirm" name="password_confirm" type="password" required minlength="10" autocomplete="new-password" placeholder="Repeat password">
            <?= field_error('password_confirm') ?>
          </div>
        </div>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Create my account</button>
        <p class="hint" style="text-align:center;margin:0">By joining you agree to honest listing and handover. Farm verification is completed by the FarmCoop team.</p>
      </form>

      <p class="auth-foot">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
    </div>
  </main>
</div>
</body>
</html>
