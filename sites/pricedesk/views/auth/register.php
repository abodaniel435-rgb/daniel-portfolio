<?php
declare(strict_types=1);
?>
<section class="section" style="margin-top:0">
  <div class="hero-grid" style="grid-template-columns:minmax(0,1fr) minmax(0,520px)">
    <div>
      <p class="eyebrow">Create a member account</p>
      <h1>A board is only as good as the people who feed it.</h1>
      <p class="lede">Members file the prices they actually traded at, in the units they actually
        trade in. Every one of those quotes is converted to naira per kilogram, checked against its
        neighbours, and published only after somebody with the console keys to it has verified it.</p>
      <ul>
        <li>Your reports appear on <strong>Your reports</strong> with the reason if one is rejected.</li>
        <li>A watched line emails you when a price is <em>queued</em> and again when it is <em>verified</em>,
          because those are different facts.</li>
        <li>One price per commodity, per market, per day, per member. The database enforces it — a single
          phone cannot become a trend.</li>
      </ul>
      <p class="tiny">Accounts join as members. There is no self-service administrator and the form has no
        role field: the only admin account is the one the installer creates from
        <span class="mono">.env</span> on the server.</p>
    </div>
    <form method="post" action="<?= e(url('/register')) ?>" class="panel" autocomplete="on">
      <?= csrf_field() ?>
      <h3 style="margin-bottom:14px">Join the board</h3>
      <div class="field">
        <label for="name">Your name</label>
        <input type="text" id="name" name="name" required maxlength="120" value="<?= e((string)old('name')) ?>" autofocus>
        <?= field_error('name') ?>
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required maxlength="190" value="<?= e((string)old('email')) ?>" autocomplete="email">
        <?= field_error('email') ?>
      </div>
      <div class="field">
        <label for="phone">Phone <span class="tiny">— optional</span></label>
        <input type="tel" id="phone" name="phone" maxlength="40" value="<?= e((string)old('phone')) ?>" autocomplete="tel">
      </div>
      <div class="grid-2">
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required minlength="10" autocomplete="new-password">
          <?= field_error('password') ?>
        </div>
        <div class="field">
          <label for="password_confirm">Repeat it</label>
          <input type="password" id="password_confirm" name="password_confirm" required minlength="10" autocomplete="new-password">
          <?= field_error('password_confirm') ?>
        </div>
      </div>
      <p class="tiny" style="margin-top:0">Ten characters minimum. It is hashed with bcrypt before anything
        reaches MySQL, and the hash is the only form the password ever takes in this database.</p>
      <button class="btn btn-primary" type="submit">Create the account</button>
      <p class="tiny" style="margin-top:16px">Already filing prices? <a href="<?= e(url('/login')) ?>">Sign in</a>.</p>
    </form>
  </div>
</section>
