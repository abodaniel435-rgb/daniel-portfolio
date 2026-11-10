<section class="hero">
  <div class="hero-inner">
    <div class="reveal">
      <p class="eyebrow">Rotating savings, properly recorded</p>
      <h1>Your ajo group deserves a ledger, <em>not a notebook</em>.</h1>
      <p>AjoCircle gives every circle a transparent contribution schedule, verified payments and a payout order nobody can argue with — for market associations, church groups, cooperatives and friends.</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Create your circle account</a>
        <a class="btn btn-ghost" href="<?= e(url('/circles')) ?>">Browse open circles</a>
      </div>
      <div class="hero-trust">
        <span><b><?= money($stats['pooled']) ?></b> recorded in contributions</span>
        <span><b><?= (int)$stats['members'] ?></b> members</span>
        <span><b><?= (int)$stats['circles'] ?></b> live circles</span>
      </div>
    </div>

    <?php if ($feature): ?>
      <div class="hero-panel reveal" aria-label="Live circle preview">
        <div class="hero-panel-head">
          <h3><?= e($feature['name']) ?></h3>
          <span><i class="pulse"></i> live</span>
        </div>
        <div class="hero-ring-row">
          <div class="ring" style="--p:<?= (int)$featurePercent ?>"><span><?= (int)$featurePercent ?>%</span></div>
          <div class="ring-copy">
            <strong><?= e(frequency_label($feature['frequency'])) ?> · <?= money((float)$feature['contribution_amount']) ?></strong>
            <span><?= (int)$feature['member_count'] ?> of <?= (int)$feature['member_limit'] ?> members · fixed payout order</span>
          </div>
        </div>
        <?php foreach ($featureRounds as $round): ?>
          <div class="mini-round">
            <span class="pos"><?= (int)$round['round_no'] ?></span>
            <div>
              <span class="who"><?= e($round['recipient']['name'] ?? 'Slot open') ?></span>
              <span class="when"><?= e(short_date($round['due_date'])) ?> · <?= (int)$round['paid_count'] ?>/<?= (int)$round['member_count'] ?> paid</span>
            </div>
            <span class="amt"><?= money($round['expected']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="hero-panel reveal">
        <div class="hero-panel-head"><h3>Every round, on record</h3><span>ledger</span></div>
        <div class="hero-ring-row">
          <div class="ring" style="--p:82"><span>82%</span></div>
          <div class="ring-copy">
            <strong>Contributions verified</strong>
            <span>No more “I paid last week” without a receipt.</span>
          </div>
        </div>
        <div class="mini-round"><span class="pos">1</span><div><span class="who">Round one collected</span><span class="when">Due date · member count</span></div><span class="amt"><?= money(120000) ?></span></div>
        <div class="mini-round"><span class="pos">2</span><div><span class="who">Next payout scheduled</span><span class="when">Fixed order, no shuffle</span></div><span class="amt"><?= money(120000) ?></span></div>
      </div>
    <?php endif; ?>
  </div>
</section>

<div class="stats">
  <div class="stat stat-accent"><strong><?= money($stats['pooled']) ?></strong><span>Total contributions verified</span></div>
  <div class="stat"><strong><?= money($stats['payouts']) ?></strong><span>Paid out to members</span></div>
  <div class="stat stat-gold"><strong><?= (int)$stats['members'] ?></strong><span>Members on the ledger</span></div>
  <div class="stat"><strong><?= (int)$stats['circles'] ?></strong><span>Circles saving together</span></div>
</div>

<section class="section">
  <div class="split">
    <div>
      <p class="eyebrow">The real problem</p>
      <h2>The group chat remembers nothing.</h2>
      <p class="lede">Contributions get scattered across WhatsApp threads, bank alerts and a treasurer’s notebook. By round four nobody agrees on who paid, who is owed, or whose turn it is — and the group loses trust.</p>
      <ul style="list-style:none;padding:0;margin:22px 0 0;display:grid;gap:12px">
        <?php foreach ([
            '“I sent ₦20,000 last month” — no reference, no record.',
            'The payout order changes quietly and the last person notices.',
            'Someone stops paying and there is no fair way to say so.',
            'The treasurer carries all the risk, alone.',
        ] as $pain): ?>
          <li style="display:flex;gap:11px;align-items:flex-start;font-size:.94rem;color:var(--muted)">
            <span style="width:22px;height:22px;border-radius:50%;background:var(--rose-soft);color:var(--rose);display:grid;place-items:center;font-size:.72rem;font-weight:800;flex:0 0 auto">×</span>
            <span><?= e($pain) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="card" style="border-left:4px solid var(--emerald)">
      <p class="eyebrow" style="margin-bottom:10px">AjoCircle instead</p>
      <h3 style="font-size:1.2rem">One ledger, visible to the whole circle.</h3>
      <p style="color:var(--muted);font-size:.92rem">AjoCircle mirrors how ajo and esusu already work — it just writes it down properly.</p>
      <div style="display:grid;gap:12px;margin-top:18px">
        <?php foreach ([
            ['Schedule', 'Every round gets a due date from your start date and frequency.'],
            ['Receipts', 'Members record a payment reference; the organiser verifies it.'],
            ['Reminders', 'Automatic nudges three days before each due date.'],
            ['Payouts', 'One payout per round, in the fixed order members joined.'],
        ] as [$label, $copy]): ?>
          <div style="display:flex;gap:12px;align-items:flex-start">
            <span style="width:26px;height:26px;border-radius:8px;background:var(--emerald-soft);color:var(--emerald-deep);display:grid;place-items:center;font-size:.74rem;font-weight:800;flex:0 0 auto">✓</span>
            <div>
              <strong style="display:block;font-size:.9rem;font-family:var(--font-display)"><?= e($label) ?></strong>
              <span style="font-size:.83rem;color:var(--muted)"><?= e($copy) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section section-tight" id="how" style="background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
  <div class="center" style="max-width:720px;margin:0 auto 42px">
    <p class="eyebrow" style="justify-content:center">How it works</p>
    <h2>Four steps, no spreadsheet</h2>
    <p class="lede">The same trust your circle already has — with a record that settles arguments before they start.</p>
  </div>
  <div class="steps" style="max-width:1220px;margin:auto">
    <div class="step"><h3>Join a circle</h3><p>Pick an open circle and take the next free position. Capacity is enforced, duplicates blocked.</p></div>
    <div class="step"><h3>Get your schedule</h3><p>Every round is generated from the start date and frequency, so due dates are never improvised.</p></div>
    <div class="step"><h3>Record the payment</h3><p>Pay by transfer or cash, then add the reference. Your organiser verifies it and the ledger updates.</p></div>
    <div class="step"><h3>Take your payout</h3><p>When your round comes, the organiser schedules the pool. One payout per round, on record.</p></div>
  </div>
</section>

<section class="section">
  <div class="page-head" style="margin-bottom:28px">
    <div>
      <p class="eyebrow">Open circles</p>
      <h2 style="margin:0">Find a circle with a free slot</h2>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/circles')) ?>">See all circles</a>
  </div>

  <?php if (!$openCircles): ?>
    <div class="empty"><strong>No open circles right now</strong>New groups are added by organisers — check back shortly.</div>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($openCircles as $circle): ?>
        <article class="circle-card reveal">
          <div class="circle-top">
            <div>
              <h3><a href="<?= e(url('/circles/' . $circle['id'])) ?>" style="color:inherit"><?= e($circle['name']) ?></a></h3>
              <span class="circle-cat"><?= e($circle['category']) ?></span>
            </div>
            <?= pill($circle['status']) ?>
          </div>
          <div class="circle-money">
            <b><?= money((float)$circle['contribution_amount']) ?></b>
            <span>/ <?= e(strtolower(frequency_label($circle['frequency']))) ?></span>
          </div>
          <p class="circle-desc"><?= e($circle['description']) ?></p>
          <div class="bar-label"><span><?= (int)$circle['member_count'] ?> of <?= (int)$circle['member_limit'] ?> slots taken</span><span><?= (int)$circle['member_limit'] - (int)$circle['member_count'] ?> left</span></div>
          <div class="bar"><i style="width:<?= (int)round((int)$circle['member_count'] / max(1, (int)$circle['member_limit']) * 100) ?>%"></i></div>
          <div class="circle-foot">
            <span class="meta-row"><span>Starts <?= e(short_date($circle['start_date'])) ?></span></span>
            <a class="btn btn-soft btn-sm" href="<?= e(url('/circles/' . $circle['id'])) ?>">View circle</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="section-tight" id="trust" style="background:var(--indigo);color:#fff">
  <div style="max-width:1220px;margin:auto" class="split">
    <div>
      <p class="eyebrow" style="color:var(--gold)">Transparency promise</p>
      <h2 style="color:#fff">Every payout is public, every position is fixed.</h2>
      <p style="color:#c4c8e2">Positions are assigned by join order and never reshuffled. When a round is paid out, the amount, date and recipient are recorded in the circle ledger — visible to every member, downloadable as a statement.</p>
      <div style="display:grid;gap:14px;margin-top:26px">
        <div style="display:flex;gap:12px;align-items:flex-start"><span style="width:26px;height:26px;border-radius:8px;background:rgba(227,179,65,.16);color:var(--gold);display:grid;place-items:center;font-size:.74rem;font-weight:800;flex:0 0 auto">1</span><div><strong style="color:#fff;font-family:var(--font-display);font-size:.92rem">Position by join order</strong><span style="display:block;font-size:.84rem;color:#a9b0d4">Round N pays the member who holds position N.</span></div></div>
        <div style="display:flex;gap:12px;align-items:flex-start"><span style="width:26px;height:26px;border-radius:8px;background:rgba(227,179,65,.16);color:var(--gold);display:grid;place-items:center;font-size:.74rem;font-weight:800;flex:0 0 auto">2</span><div><strong style="color:#fff;font-family:var(--font-display);font-size:.92rem">One payout per round</strong><span style="display:block;font-size:.84rem;color:#a9b0d4">The database refuses a second payout for the same round.</span></div></div>
        <div style="display:flex;gap:12px;align-items:flex-start"><span style="width:26px;height:26px;border-radius:8px;background:rgba(227,179,65,.16);color:var(--gold);display:grid;place-items:center;font-size:.74rem;font-weight:800;flex:0 0 auto">3</span><div><strong style="color:#fff;font-family:var(--font-display);font-size:.92rem">Audit trail on everything</strong><span style="display:block;font-size:.84rem;color:#a9b0d4">Verifications, late marks and payouts are logged with who and when.</span></div></div>
      </div>
    </div>
    <div class="card" style="background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.16);box-shadow:none">
      <div class="panel-head" style="margin-bottom:8px"><h3 style="color:#fff;margin:0;font-size:.95rem">Recent payouts</h3><span class="badge badge-ok">settled</span></div>
      <?php if (!$recentPayouts): ?>
        <p style="color:#a9b0d4;font-size:.85rem;margin:10px 0 0">Payout records appear here as circles complete their rounds.</p>
      <?php else: ?>
        <?php foreach ($recentPayouts as $payout): ?>
          <div class="mini-round" style="grid-template-columns:1fr auto">
            <div>
              <span class="who"><?= e($payout['recipient']) ?></span>
              <span class="when"><?= e($payout['name']) ?> · <?= e(short_date($payout['paid_on'])) ?></span>
            </div>
            <span class="amt"><?= money((float)$payout['amount']) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="center" style="max-width:700px;margin:0 auto 40px">
    <p class="eyebrow" style="justify-content:center">Questions</p>
    <h2>What members usually ask</h2>
  </div>
  <div class="grid grid-3">
    <div class="card feature"><div class="icon">₦</div><h3>Does AjoCircle hold my money?</h3><p>No. Members pay each other directly by transfer, cash or mobile money. AjoCircle records the reference and verifies it — the money never passes through us.</p></div>
    <div class="card feature gold"><div class="icon">⇄</div><h3>Can the payout order change?</h3><p>Positions are assigned when a member joins and stay fixed. The ledger shows every member’s position, so the order is impossible to rewrite quietly.</p></div>
    <div class="card feature indigo"><div class="icon">✓</div><h3>What if someone stops paying?</h3><p>Missed rounds are marked late automatically and the organiser is notified. Every entry keeps its payment reference, so disputes are settled with evidence.</p></div>
  </div>
</section>

<section class="section-tight">
  <div style="max-width:1220px;margin:auto;background:linear-gradient(135deg,var(--emerald-deep),var(--emerald));border-radius:26px;padding:48px;display:flex;align-items:center;justify-content:space-between;gap:28px;flex-wrap:wrap;box-shadow:0 26px 60px rgba(15,159,116,.26)">
    <div>
      <h2 style="color:#fff;margin-bottom:8px;font-size:clamp(1.5rem,2.6vw,2.1rem)">Start the circle your group already trusts.</h2>
      <p style="color:#d5f5e9;margin:0;max-width:56ch">Create an account, join an open circle, and give your treasurer the night off.</p>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <a class="btn btn-gold" href="<?= e(url('/register')) ?>">Create account</a>
      <a class="btn btn-ghost" href="<?= e(url('/circles')) ?>">Browse circles</a>
    </div>
  </div>
</section>
