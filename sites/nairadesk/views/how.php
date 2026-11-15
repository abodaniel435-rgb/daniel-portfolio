<?php declare(strict_types=1); ?>
<p class="eyebrow">The working</p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)">How a payslip gets built</h1>
<div class="grid-2" style="margin-top:24px">
  <div class="panel">
    <h3>Order matters</h3>
    <ol style="padding-left:20px;font-size:.94rem;line-height:1.9">
      <li>Gross = basic + housing + transport + other.</li>
      <li>Annual gross = gross × 12.</li>
      <li>CRA = ₦200,000 + 20% of annual gross.</li>
      <li>Rent relief = 20% of annual rent, capped at ₦6,250,000.</li>
      <li>Employee pension comes off before tax.</li>
      <li>Taxable = annual gross − CRA − rent relief − pension.</li>
      <li>PAYE is the sum of each band slice × its rate, divided by 12.</li>
      <li>NHF is 2.5% of gross, taken from net, not from taxable.</li>
      <li>Net = gross − PAYE − pension − NHF.</li>
    </ol>
  </div>
  <div>
    <h2>Why it is a back end</h2>
    <p>A calculator in the browser can show you a number. It cannot tell you who changed the rent-relief cap, when, or what the payroll looked like before it. NairaDesk writes the inputs, the full result and the acting user into MySQL for every run, and keeps an audit trail of every statutory figure an administrator changes.</p>
    <p>The run you reopen next March is the run you filed in March, because each line stores the engine's complete output rather than re-deriving it from whatever the rates table says today.</p>
    <div class="panel">
      <p class="eyebrow" style="margin-bottom:8px">Guarantees the code holds</p>
      <p style="font-size:.92rem;margin:0">bcrypt password hashing · CSRF on every POST · session id regenerated at login · role gate on <code>/admin</code> · company resolved from the session, never the URL · prepared statements throughout · eight failed attempts per address per 15 minutes locks sign-in.</p>
    </div>
  </div>
</div>
