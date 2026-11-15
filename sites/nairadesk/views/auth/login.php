<?php declare(strict_types=1); ?>
<div class="split-page" style="grid-template-columns:minmax(0,420px) minmax(0,1fr)">
  <div class="panel">
    <p class="eyebrow">Sign in</p>
    <h2 style="font-size:1.5rem">Back to the books</h2>
    <form method="post" action="<?= e(url('/login')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>"></div>
      <p class="err"><?= field_error('email') ?></p>
      <div class="field"><label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required></div>
      <button class="btn btn-primary" type="submit">Sign in</button>
    </form>
    <p style="margin:18px 0 0;font-size:.9rem">No workspace yet? <a href="<?= e(url('/register')) ?>">Create one</a>.</p>
  </div>
  <div>
    <h2>What an account gets you</h2>
    <p>Your own company, your own staff, your own runs. Nothing is shared, and nothing is read from the URL — the workspace is resolved from the signed-in user id on every request.</p>
    <p>Passwords are stored as bcrypt hashes. Eight failures against one address in fifteen minutes locks the attempt out, whichever IP it comes from.</p>
  </div>
</div>
