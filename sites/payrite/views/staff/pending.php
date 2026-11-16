<?php
declare(strict_types=1);
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Waiting on the desk</p>
      <h1>Your account exists. It is not yet attached to a pay record.</h1>
      <p class="lede">Registration created the login, and that is deliberately all it can do. A payroll officer has
        to link your account to a row on the roster before any payslip can be shown to you — otherwise anyone who
        knew a colleague's email could register it and read their pay.</p>
    </div>
  </div>

  <div class="grid-3">
    <div class="card">
      <h3>What to do</h3>
      <p class="tiny">Tell whoever runs payroll the exact email you signed up with. They link it under
        <span class="mono">Staff logins</span> on the desk.</p>
    </div>
    <div class="card">
      <h3>What you will see after</h3>
      <p class="tiny">Every locked run that includes you, each with the components, the reliefs, the band table that
        applied and the employer's side on top.</p>
    </div>
    <div class="card">
      <h3>In the meantime</h3>
      <p class="tiny">The <a href="<?= e(url('/')) ?>">calculator</a> still works without an account — type your own
        figures and compare the result with any slip you were given by hand.</p>
    </div>
  </div>

  <p style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap">
    <a class="btn btn-ghost" href="<?= e(url('/profile')) ?>">My package page</a>
    <a class="btn btn-ghost" href="<?= e(url('/rates')) ?>">Bands and reliefs</a>
  </p>
</section>
