<?php declare(strict_types=1); ?>
<div class="split-page" style="grid-template-columns:minmax(0,460px) minmax(0,1fr)">
  <div class="panel">
    <p class="eyebrow">Create workspace</p>
    <h2 style="font-size:1.5rem">Start a payroll record</h2>
    <form method="post" action="<?= e(url('/register')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="grid-2">
        <div class="field"><label for="name">Your name</label>
          <input id="name" name="name" required value="<?= e(old('name')) ?>"></div>
        <div class="field"><label for="company">Business name</label>
          <input id="company" name="company" maxlength="160" value="<?= e(old('company')) ?>"></div>
      </div>
      <div class="field"><label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>"></div>
      <p class="err"><?= field_error('email') ?></p>
      <div class="field"><label for="phone">Phone <span style="font-weight:400;color:var(--ink-3)">(optional)</span></label>
        <input id="phone" name="phone" maxlength="40" autocomplete="tel" value="<?= e(old('phone')) ?>"></div>
      <div class="grid-2">
        <div class="field"><label for="password">Password</label>
          <input id="password" name="password" type="password" autocomplete="new-password" required minlength="10"></div>
        <div class="field"><label for="password_confirm">Confirm</label>
          <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required></div>
      </div>
      <p class="err"><?= field_error('password') ?></p>
      <button class="btn btn-primary" type="submit">Create workspace</button>
    </form>
  </div>
  <div>
    <h2>Ten characters, hashed, never stored</h2>
    <p>The password is checked for length and confirmation on the server, then passed to <code>password_hash()</code> with bcrypt. Only the hash reaches MySQL — an administrator with database access still cannot read an account password.</p>
    <p>Registration creates the account and an empty company owned by it. Staff, runs and rates are all scoped to that owner.</p>
  </div>
</div>
