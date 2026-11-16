<?php
declare(strict_types=1);
/** @var array<string,mixed> $gate @var array<string,mixed> $city @var array<string,mixed> $haul @var array<string,mixed> $stats */
?>
<section class="hero">
  <div class="hero-grid">
    <div>
      <p class="eyebrow">Farm gate · wholesale market · city retail</p>
      <h1>A ₦19,000 crate spread that <em>loses</em> money over 62 km.</h1>
      <p class="lede">
        Price asymmetry is the quiet tax on every smallholder and market seller in Nigeria. A trader
        quotes per mudu, a farmer quotes per crate, and a WhatsApp group quotes per feeling. PriceDesk
        puts every member's morning quote on one axis — naira per kilogram — takes the median, has a
        human verify it, then subtracts the fuel, the levies, the porters and the spoilage before it
        tells you whether crossing town is worth loading the truck.
      </p>
      <p class="lede">
        This is the server behind that idea: accounts, a verification queue, an audit trail and a JSON
        endpoint. The browser no longer decides what a price is.
      </p>
      <p style="display:flex;gap:12px;flex-wrap:wrap;margin-top:22px">
        <a class="btn btn-primary" href="<?= e(url('/board')) ?>">Open today's board</a>
        <a class="btn btn-ghost" href="<?= e(url('/compare')) ?>">Is it worth the trip?</a>
      </p>
      <div class="stat-row">
        <div class="stat"><b><?= (int)($stats['verified'] ?? 0) ?></b><span>verified quotes on the board</span></div>
        <div class="stat"><b><?= (int)($stats['pending'] ?? 0) ?></b><span>waiting for a reviewer</span></div>
        <div class="stat"><b><?= (int)($stats['markets'] ?? 0) ?></b><span>markets reporting</span></div>
        <div class="stat"><b><?= (int)($stats['reporters'] ?? 0) ?></b><span>members filing prices</span></div>
      </div>
    </div>

    <aside class="panel">
      <p class="eyebrow" style="margin-bottom:8px">Worked example, run live</p>
      <h3 style="margin-bottom:6px">Six quotes come in for one commodity</h3>
      <p class="tiny" style="margin-bottom:14px">Five sellers price a mudu between ₦1,150 and ₦1,300.
        One reports a whole crate at ₦9,000. Mean and median disagree loudly about that.</p>

      <div class="readout-row"><span>Naïve mean of the six</span><b><?= e(money((float)$gate['mean'])) ?></b></div>
      <div class="readout-row"><span>Median, per kilogram</span><b><?= e(money((float)$gate['median'])) ?></b></div>
      <div class="readout-row"><span>Spread around it</span><b>±<?= e((string)$gate['spreadPct']) ?>%</b></div>
      <div class="readout-row"><span>Range seen</span><b><?= e(money((float)$gate['min'])) ?> – <?= e(money((float)$gate['max'])) ?></b></div>

      <p class="verdict" style="margin-top:16px">
        The crate is <?= e((string)$gate['count']) ?> quotes deep and still sits outside the band. It is
        thrown at a human, not at the average — and the median barely notices it.
      </p>
      <p class="tiny" style="margin-top:12px">
        Every figure on this page came out of <span class="mono">Pricing::aggregate()</span> on this
        request. The front page quotes nothing it has not just calculated.
      </p>
    </aside>
  </div>
</section>

<?php if (!empty($ticker ?? [])): ?>
<section class="section">
  <div class="section-head"><h2>Live from the board</h2><a class="btn btn-ghost btn-sm" href="<?= e(url('/board')) ?>">All lines</a></div>
  <div class="ticker">
    <?php foreach ($ticker as $line): ?>
      <div>
        <b><?= e(money((float)$line['median'])) ?></b>
        <span><?= e($line['commodity']) ?> · <?= e($line['market']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section" id="rules">
  <div class="section-head"><h2>Three rules the arithmetic obeys</h2></div>
  <div class="grid-3">
    <article class="card">
      <p class="eyebrow">01 · Normalise</p>
      <h3>Nothing is averaged until the units agree</h3>
      <p>A ₦1,100 mudu can be a <em>dearer</em> kilogram than a ₦26,000 crate. Every quote is converted
        through the unit weights an administrator curates per commodity, so a basket, a lug and a 50 kg
        bag land on the same axis before anyone sums them.</p>
      <p class="tiny">Weights are data, not code. A wrong crate size is an edit in the console, not a deploy.</p>
    </article>
    <article class="card">
      <p class="eyebrow">02 · Take the middle</p>
      <h3>A median, never a mean — with the spread beside it</h3>
      <p>One farmer quoting a fantasy price moves a mean and barely moves a median. The board prints the
        median, the half-spread and a confidence pill, because three quotes that disagree by 40% are a
        different kind of knowledge from twelve that agree within 1%.</p>
      <p class="tiny">Confidence is computed from quote count and dispersion, and shown as
        <span class="conf conf-thin">thin</span>, <span class="conf conf-fair">fair</span>,
        <span class="conf conf-solid">solid</span> or <span class="conf conf-disputed">disputed</span>.</p>
    </article>
    <article class="card">
      <p class="eyebrow">03 · Pay for the trip</p>
      <h3>The gap is not the profit</h3>
      <p>Return fuel, market levies, checkpoints, the head porter and the share that rots in traffic all
        come off before a kilo earns anything. PriceDesk returns the break-even load: how many
        kilograms must move before hauling beats selling where you stand.</p>
      <p class="tiny">Spoilage is charged against the cargo you paid for, so the sellable weight shrinks
        while the cost of arriving does not.</p>
    </article>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <h2>The same two boards, sixty-two kilometres apart</h2>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/compare')) ?>">Run your own</a>
  </div>
  <div class="split-page" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr)">
    <div class="panel">
      <p class="eyebrow">Outcome</p>
      <div class="readout-big"><?= e(money((float)$haul['netPerKg'])) ?><span class="tiny" style="display:block;font-family:var(--font-body)">per kilogram, after the trip</span></div>
      <div class="readout-row"><span>Price gap to be chased</span><b><?= e(money((float)$haul['gapPerKg'])) ?>/kg</b></div>
      <div class="readout-row"><span>Fuel, there and back</span><b><?= e(money((float)$haul['fuel'])) ?></b></div>
      <div class="readout-row"><span>Levies and checkpoints</span><b><?= e(money((float)$haul['levies'])) ?></b></div>
      <div class="readout-row"><span>Porters at the gate</span><b><?= e(money((float)$haul['porter'])) ?></b></div>
      <div class="readout-row"><span>Cost per sellable kilo</span><b><?= e(money((float)$haul['costPerKg'])) ?></b></div>
      <div class="readout-row"><span>Break-even load</span><b><?= e(number_format((float)$haul['breakEvenKg'], 0)) ?> of <?= e(number_format((float)$haul['capacityKg'], 0)) ?> kg</b></div>
      <div class="readout-row"><span>If the vehicle fills</span><b><?= e(money((float)$haul['fullLoadProfit'])) ?></b></div>
      <p class="verdict <?= (float)$haul['netPerKg'] > 0 ? 'verdict-ok' : 'verdict-bad' ?>" style="margin-top:16px">
        <?= e((string)$haul['verdict']) ?>
      </p>
      <p class="tiny" style="margin-top:12px">
        Origin <?= e((string)$gate['count']) ?> verified quotes · destination <?= e((string)$city['count']) ?> ·
        <?= e(number_format((float)$haul['distanceKm'], 0)) ?> km each way, <?= (int)round((float)$haul['sellableKg']) ?> kg
        sellable after <?= e((string)(int)round((float)$haul['capacityKg'] - (float)$haul['sellableKg'])) ?> kg expected loss.
      </p>
    </div>
    <div>
      <h3>Why this belongs on a server</h3>
      <p class="lede">A calculator in a browser is a fine thing to poke at and a useless thing to trade
        on. Nothing a visitor types into one is known to anybody else. The moment a price matters it
        needs four things a page cannot provide:</p>
      <ul>
        <li><strong>Accumulation.</strong> Quotes are rows in <span class="mono">price_reports</span>, so
          a median has something to be the median <em>of</em>, and history survives closing the tab.</li>
        <li><strong>Attribution.</strong> Every price has a <span class="mono">reporter_id</span> and every
          verification has a <span class="mono">verified_by</span>. A board nobody is accountable for is a rumour.</li>
        <li><strong>Curation.</strong> A submitted price is <em>queued</em>. It reaches the board when a
          human verifies it, and a rejection carries a reason the reporter can read.</li>
        <li><strong>A boundary other software can stand on.</strong> <span class="mono">/api/quote</span>
          returns the same aggregate the page renders, so a union's spreadsheet and this site cannot
          disagree.</li>
      </ul>
      <p class="tiny">Passwords are bcrypt hashes. Every POST carries a CSRF token. Sessions are rotated at
        login. A member cannot reach the queue and an anonymous visitor cannot file. Registration never
        reads a role from the form — the only administrator is the one the installer creates from
        <span class="mono">.env</span>.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="section-head"><h2>What is on the board right now</h2></div>
  <div class="grid-2">
    <article class="card">
      <h3>For the seller</h3>
      <p>File your morning price in the units you actually trade in. Watch a line and be told when a
        verified price lands on it — and when it has gone quiet, which is the same news arriving later.</p>
    </article>
    <article class="card">
      <h3>For the association</h3>
      <p>The queue is where a disputed price gets settled, the audit trail says who settled it, and the
      rates page is where diesel gets updated on the day it moves rather than at the next deploy.</p>
    </article>
  </div>
  <p style="display:flex;gap:12px;flex-wrap:wrap;margin-top:22px">
    <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Join the board</a>
    <a class="btn btn-ghost" href="<?= e(url('/api/quote')) ?>?commodity=1">Look at the JSON</a>
  </p>
</section>
