<?php /** Public marketing + live board. */ ?>
<section class="hero">
  <div class="hero-inner">
    <div class="reveal">
      <p class="eyebrow"><span class="pulse"></span> Lagos last-mile, under control</p>
      <h1>Every rider.<br>Every parcel.<br><em>Every scan on record.</em></h1>
      <p>RoutePilot replaces the WhatsApp dispatch group with a real operations desk — assign couriers, capture every status update with a location, and send customers a tracking page that actually updates.</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="#track">Track a parcel</a>
        <a class="btn btn-ghost" href="<?= e(url('/login')) ?>">Open the dispatch desk</a>
      </div>
      <div class="hero-note">
        <span class="pulse"></span>
        <?= (int)$stats['active'] ?> parcels moving right now · <?= (int)$stats['couriers'] ?> riders available today
      </div>
    </div>

    <div class="track-card reveal" id="track">
      <h3>Where is my parcel?</h3>
      <p>Enter the tracking code from your email or SMS. No account needed.</p>
      <form class="track-form" method="get" action="<?= e(url('/track')) ?>">
        <label class="sr-only" for="hero-code">Tracking code</label>
        <input id="hero-code" name="code" placeholder="RP-YYMMDD-XXXX" autocomplete="off" spellcheck="false" required
               value="<?= e($_GET['code'] ?? '') ?>">
        <button class="btn btn-primary" type="submit">Track</button>
      </form>
      <?php if (($demoCode ?? '') !== ''): ?>
        <p style="margin:12px 0 0;font-size:.76rem">
          Demo code:
          <a href="<?= e(url('/track?code=' . rawurlencode($demoCode))) ?>" class="mono-code" style="color:var(--amber)"><?= e($demoCode) ?></a>
        </p>
      <?php endif; ?>

      <div class="hero-board">
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.14em;color:#7f90a9;font-weight:700;padding-bottom:6px">Live network board</div>
        <?php if ($board): foreach ($board as $row): ?>
          <div class="hero-board-line">
            <span class="route"><?= e(explode(',', $row['origin'])[0]) ?> → <?= e(explode(',', $row['destination'])[0]) ?></span>
            <span style="color:#6f7f99;font-size:.72rem"><?= e(rp_time_ago($row['updated_at'])) ?></span>
            <?= rp_pill($row['status'], rp_is_open($row['status']) ? 'pill-live' : '') ?>
          </div>
        <?php endforeach; else: ?>
          <p style="color:#93a4bf;font-size:.84rem;margin:8px 0 0">The board is quiet — the first shipments will appear here.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<div class="stats">
  <div class="stat stat-accent"><strong><?= (int)$stats['delivered'] ?></strong><span>Parcels delivered</span><small>Tracked end to end</small></div>
  <div class="stat"><strong><?= (int)$stats['active'] ?></strong><span>Active shipments</span><small>Assigned, moving or arriving</small></div>
  <div class="stat"><strong><?= (int)$stats['couriers'] ?></strong><span>Riders available</span><small>Lagos Island &amp; Mainland</small></div>
  <div class="stat"><strong><?= (int)$stats['zones'] ?></strong><span>Pickup zones</span><small>Yaba · Ikeja · Lekki · Surulere</small></div>
</div>

<section class="section">
  <div class="split">
    <div class="reveal">
      <p class="eyebrow">The problem we fix</p>
      <h2>Dispatch over WhatsApp has no memory.</h2>
      <p class="lede">A rider says “I've dropped it” in a group chat. Nobody can prove when, where, or for whom. Customers call three times to ask. RoutePilot turns that conversation into a structured, timestamped record — without slowing your riders down.</p>
      <div class="grid grid-2" style="margin-top:26px">
        <div class="card card-hover feature">
          <div class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/></svg>
          </div>
          <h3>One scan, one record</h3>
          <p>Every status change stores who updated it, where and when — a real audit trail, not a screenshot.</p>
        </div>
        <div class="card card-hover feature">
          <div class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12H5.5L4 18V4z"/><path d="M8 9h8M8 12.5h5"/></svg>
          </div>
          <h3>Customers stop calling</h3>
          <p>A shareable tracking page shows the timeline, ETA copy and pickup city — never the recipient's full details.</p>
        </div>
      </div>
    </div>

    <div class="route-visual reveal">
      <h3>Inside a RoutePilot tracking page</h3>
      <div class="route-line">
        <div class="route-stop done">
          <strong>Picked up · Yaba</strong>
          <span>Today, 9:55 AM · scanned by Emeka</span>
        </div>
        <div class="route-stop done">
          <strong>In transit · Third Mainland Bridge</strong>
          <span>Today, 11:10 AM · “light traffic, on schedule”</span>
        </div>
        <div class="route-stop current">
          <strong>Out for delivery · Ikoyi</strong>
          <span>Today, 12:40 PM · rider 8 minutes away</span>
        </div>
        <div class="route-stop">
          <strong>Delivered</strong>
          <span>Recipient receives an automatic confirmation email</span>
        </div>
      </div>
      <div class="route-meta">
        <span>RP-YYMMDD-XXXX</span>
        <span>Same-day service · ₦6,200</span>
      </div>
    </div>
  </div>
</section>

<section class="section section-tight" id="services">
  <div class="row-between reveal">
    <div>
      <p class="eyebrow">Delivery services</p>
      <h2 style="margin:0">Choose the promise your customer needs.</h2>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/track')) ?>">Track an existing parcel</a>
  </div>
  <div class="grid grid-3" style="margin-top:26px">
    <?php foreach ($services as $key => $service): ?>
      <article class="card card-hover feature reveal">
        <div class="icon" aria-hidden="true">
          <?php if ($key === 'same_day'): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z"/></svg>
          <?php elseif ($key === 'express'): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12h4l3-9 4 18 3-9h4"/></svg>
          <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7"/><circle cx="7" cy="18.5" r="1.6"/><circle cx="17.5" cy="18.5" r="1.6"/></svg>
          <?php endif; ?>
        </div>
        <span class="tag"><?= e($service['label']) ?></span>
        <h3 style="margin-top:12px"><?= e($service['eta']) ?></h3>
        <p><?= e($service['blurb']) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section" id="how-it-works">
  <div class="row-between reveal">
    <div>
      <p class="eyebrow">How it works</p>
      <h2 style="margin:0">Booked, assigned, scanned, delivered.</h2>
    </div>
  </div>
  <div class="steps" style="margin-top:28px">
    <div class="step reveal">
      <h3>Dispatch books the parcel</h3>
      <p>Sender, recipient, route, weight and service are captured once. RoutePilot issues a unique tracking code such as <span class="mono-code">RP-YYMMDD-XXXX</span>.</p>
    </div>
    <div class="step reveal">
      <h3>The nearest rider is assigned</h3>
      <p>The board shows every rider's open load. Assign or reassign in one click — the customer is emailed automatically.</p>
    </div>
    <div class="step reveal">
      <h3>Every scan is public-safe</h3>
      <p>Riders update status with a location and note. Customers see the timeline and ETA; only the recipient's first name is shown.</p>
    </div>
  </div>
  <div class="note mt-3">
    <strong>Automation included.</strong> Overdue pickups are flagged, in-transit parcels without a scan for 3 days are marked delayed, ETA reminders go out when a parcel is out for delivery, and administrators get a daily digest.
  </div>
</section>

<section class="section" style="padding-top:16px">
  <div class="card reveal" style="background:linear-gradient(120deg,#0e1728,var(--navy) 55%,#1b2436);border:1px solid rgba(245,165,36,.24);color:#fff;padding:44px;display:flex;justify-content:space-between;align-items:center;gap:30px;flex-wrap:wrap;position:relative;overflow:hidden">
    <div style="position:absolute;right:-60px;top:-80px;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(245,165,36,.22),transparent 70%)"></div>
    <div style="max-width:58ch;position:relative">
      <h2 style="color:#fff;margin-bottom:10px">Riders: keep your queue clean.</h2>
      <p style="color:#a9b8cd;margin:0">Create a courier account, get parcels assigned to your zone, and update each drop with one tap. Your delivery history travels with you.</p>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;position:relative">
      <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Create rider account</a>
      <a class="btn btn-ghost" href="<?= e(url('/login')) ?>">Sign in</a>
    </div>
  </div>
</section>
