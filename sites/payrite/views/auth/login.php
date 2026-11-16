<?php
declare(strict_types=1);
$bodyClass = 'auth';
?>
<section class="hero">
  <div class="hero-grid">
    <div>
      <p class="eyebrow">Payroll desk</p>
      <h1>Sign in to <em>the run.</em></h1>
      <p class="lede">Staff see their own locked payslips and the package behind them. The administrator sees the
        roster, the band table and the month that has to go out.</p>
      <div class="grid-2" style="margin-top:26px">
        <div class="card">
          <h3>Staff</h3>
          <p class="tiny">Your account is created by you and linked to a staff record by the payroll officer. Until
            that link exists, the desk shows what your package would produce and tells you it is waiting.</p>
        </div>
        <div class="card">
          <h3>Administrator</h3>
          <p class="tiny">The one admin account is created by the installer from <span class="mono">ADMIN_EMAIL</span>
            in <span class="mono">.env</span>. Registration cannot make you one, no matter what is posted.</p>
        </div>
      </div>
      <p class="tiny">Six wrong attempts against one email or IP in fifteen minutes pauses further tries. The counter
        lives in MySQL, so closing the tab does not reset it.</p>
    </div>

    <form class="panel" method="post" action="<?= e(url('/login')) ?>" autocomplete="on">
      <?= csrf_field() ?>
      <h3>Sign in</h3>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required maxlength="190"
               value="<?= e((string)old('email')) ?>" autocomplete="username" autofocus>
        <?= field_error('email') ?>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <?= field_error('password') ?>
      </div>
      <button class="btn btn-primary" type="submit">Sign in</button>
      <p class="tiny" style="margin-top:16px">On the payroll but no account yet?
        <a href="<?= e(url('/register')) ?>">Create a staff login</a>.</p>
      <p class="tiny" style="margin-top:8px">Just want the arithmetic?
        <a href="<?= e(url('/')) ?>">The calculator needs no sign-in</a>.</p>
    </form>
  </div>
</section>
