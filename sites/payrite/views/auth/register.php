<?php
declare(strict_types=1);
$bodyClass = 'auth';
?>
<section class="hero">
  <div class="hero-grid">
    <div>
      <p class="eyebrow">Staff account</p>
      <h1>An account to read <em>your own slips.</em></h1>
      <p class="lede">Registering makes you staff. It does not open the desk, the roster or anyone else's pay —
        the role is set by the server, never taken from this form, and an administrator has to point your account
        at a staff record before payslips appear.</p>
      <div class="verdict info" style="margin-top:24px">
        <h4>What the account can see</h4>
        <p>Your locked payslips, the components your employer holds for you, and a note field to raise a query about
          a figure. Every query is joined through your own record, so there is no id in a URL to try changing.</p>
      </div>
      <p class="tiny">Passwords are stored as bcrypt hashes — the plain text never reaches the database, a log or
        this page again.</p>
    </div>

    <form class="panel" method="post" action="<?= e(url('/register')) ?>" autocomplete="on">
      <?= csrf_field() ?>
      <h3>Create a staff account</h3>
      <div class="field">
        <label for="name">Full name</label>
        <input id="name" name="name" type="text" required maxlength="120" value="<?= e((string)old('name')) ?>" autofocus>
        <?= field_error('name') ?>
      </div>
      <div class="field">
        <label for="email">Work email</label>
        <input type="email" id="email" name="email" required maxlength="190" value="<?= e((string)old('email')) ?>" autocomplete="username">
        <?= field_error('email') ?>
      </div>
      <div class="field">
        <label for="phone">Phone (optional)</label>
        <input id="phone" name="phone" type="text" maxlength="40" value="<?= e((string)old('phone')) ?>" inputmode="tel">
        <?= field_error('phone') ?>
      </div>
      <div class="grid-2">
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required autocomplete="new-password">
          <?= field_error('password') ?>
        </div>
        <div class="field">
          <label for="password_confirm">Confirm password</label>
          <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
          <?= field_error('password_confirm') ?>
        </div>
      </div>
      <p class="tiny" style="margin:-6px 0 14px">At least ten characters. Use something you do not use on your bank app.</p>
      <button class="btn btn-primary" type="submit">Create the account</button>
      <p class="tiny" style="margin-top:16px">Already registered? <a href="<?= e(url('/login')) ?>">Sign in</a>.</p>
    </form>
  </div>
</section>
