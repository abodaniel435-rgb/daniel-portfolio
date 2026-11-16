<?php
declare(strict_types=1);
$bodyClass = 'auth';
?>
<section class="section" style="margin-top:0">
  <div class="hero-grid" style="grid-template-columns:minmax(0,1fr) minmax(0,460px)">
    <div>
      <p class="eyebrow">Members of the cooperative</p>
      <h1>Welcome back to the board.</h1>
      <p class="lede">Sign in to file today's prices, watch the lines you trade, and see which of your
        quotes an administrator has verified.</p>
      <p class="tiny">Eight sign-in failures against one email or IP in fifteen minutes pauses further
        attempts. That throttle is stored in MySQL, so refreshing the page does not reset it.</p>
    </div>
    <form method="post" action="<?= e(url('/login')) ?>" class="panel" autocomplete="on">
      <?= csrf_field() ?>
      <h3 style="margin-bottom:14px">Sign in</h3>
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
      <p class="tiny" style="margin-top:16px">Not on the board yet?
        <a href="<?= e(url('/register')) ?>">Create an account</a> — you will be joining as a member.</p>
    </form>
  </div>
</section>
