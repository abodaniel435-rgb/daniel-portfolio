<section class="hero">
  <div class="hero-inner">
    <div class="hero-copy reveal">
      <p class="hero-eyebrow">Farm-direct marketplace · Nigeria</p>
      <h1>The harvest,<br>without the <em>middlemen.</em></h1>
      <p>FarmCoop gives smallholder farmers a verified storefront with live quantities — and gives restaurants, hotels and market traders a supply they can inspect, reserve and collect on a schedule they set.</p>
      <div class="hero-actions">
        <a class="btn btn-gold btn-lg" href="<?= e(url('/marketplace')) ?>">Shop the market</a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('/register?role=farmer')) ?>">Sell your harvest</a>
      </div>
      <div class="hero-note"><span class="dot"></span> <?= (int)$stats['farms'] ?> verified farms · <?= (int)$stats['listings'] ?> live harvests across <?= (int)$stats['states'] ?> states</div>
    </div>

    <aside class="harvest-panel reveal d1" aria-label="Latest harvests">
      <div class="panel-top">
        <h3>Just listed</h3>
        <span class="live-tag"><span class="dot"></span> Live</span>
      </div>
      <?php if ($featured): foreach ($featured as $item): ?>
        <a class="harvest-row" href="<?= e(url('/listing/' . $item['id'])) ?>" style="text-decoration:none">
          <img class="harvest-thumb" src="<?= e(listing_image($item)) ?>" alt="<?= e($item['produce_name']) ?>" loading="lazy">
          <div>
            <p class="name"><?= e($item['produce_name']) ?></p>
            <p class="where"><?= e($item['farm_name'] ?: $item['farmer_name']) ?> · <?= e($item['state']) ?></p>
          </div>
          <span class="price"><?= e(money((float)$item['unit_price'])) ?></span>
        </a>
      <?php endforeach; else: ?>
        <p class="harvest-empty">The market is being restocked. Check back shortly or list your own harvest.</p>
      <?php endif; ?>
      <a class="btn btn-ghost btn-block" href="<?= e(url('/marketplace')) ?>" style="border-color:rgba(255,255,255,.28);color:#fff">Browse all harvests</a>
    </aside>
  </div>
</section>

<div class="search-card">
  <form class="search-form" method="get" action="<?= e(url('/marketplace')) ?>" role="search">
    <div class="field">
      <label for="hero-q">What are you buying?</label>
      <input id="hero-q" name="q" type="search" placeholder="Tomatoes, ofada rice, plantain…" autocomplete="off">
    </div>
    <div class="field">
      <label for="hero-category">Category</label>
      <select id="hero-category" name="category">
        <option value="">All produce</option>
        <?php foreach ($categories as $slug => $label): ?>
          <option value="<?= e($slug) ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="hero-state">State</label>
      <select id="hero-state" name="state">
        <option value="">Anywhere in Nigeria</option>
        <?php foreach (nigerian_states() as $state): ?>
          <option value="<?= e($state) ?>"><?= e($state) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Find produce</button>
  </form>
</div>

<div class="trust-strip">
  <div class="trust-item"><span class="tick" aria-hidden="true">✓</span><div><strong>Verified farms</strong><span>Profiles checked before a verified badge appears</span></div></div>
  <div class="trust-item"><span class="tick" aria-hidden="true">#</span><div><strong>Live quantities</strong><span>Stock counts down the moment an order is placed</span></div></div>
  <div class="trust-item"><span class="tick" aria-hidden="true">₦</span><div><strong>No middleman markup</strong><span>Farmers set the price, buyers pay it</span></div></div>
  <div class="trust-item"><span class="tick" aria-hidden="true">◷</span><div><strong>Pickup windows</strong><span>Every listing states when and where to collect</span></div></div>
</div>

<section class="section">
  <div class="section-head reveal">
    <p class="eyebrow">Today's market</p>
    <h2>Fresh from the farm gate.</h2>
    <p class="lede">Six harvests are live right now, with real quantities and firm pickup windows. Order in bulk and the farmer confirms within a day.</p>
  </div>
  <div class="grid grid-3">
    <?php foreach ($featured as $index => $item): ?>
      <article class="produce-card reveal <?= $index === 1 ? 'd1' : ($index === 2 ? 'd2' : '') ?>">
        <a class="produce-link" href="<?= e(url('/listing/' . $item['id'])) ?>" aria-label="View <?= e($item['produce_name']) ?>"></a>
        <div class="produce-media">
          <img src="<?= e(listing_image($item)) ?>" alt="<?= e($item['produce_name']) ?>" loading="lazy">
          <div class="produce-tags">
            <span class="badge badge-gold"><?= e(category_label($item['category'])) ?></span>
            <?php if ((int)$item['is_verified'] === 1): ?><span class="badge badge-ok">Verified farm</span><?php endif; ?>
          </div>
          <span class="produce-qty"><?= e(fc_qty((float)$item['quantity'], $item['unit'])) ?> available</span>
        </div>
        <div class="produce-body">
          <span class="produce-farm"><?= e($item['farm_name'] ?: $item['farmer_name']) ?> · <?= e($item['state']) ?></span>
          <h3 class="produce-title"><a href="<?= e(url('/listing/' . $item['id'])) ?>"><?= e($item['produce_name']) ?></a></h3>
          <p class="produce-desc"><?= e(mb_strimwidth((string)$item['description'], 0, 118, '…')) ?></p>
          <div class="produce-foot">
            <span class="produce-price"><?= e(money((float)$item['unit_price'])) ?><small>per <?= e($item['unit']) ?></small></span>
            <span class="produce-meta">From <?= e(short_date($item['available_from'])) ?><br><?= e($item['lga']) ?></span>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="flex-between" style="margin-top:30px">
    <p class="muted small" style="margin:0">Prices are set per unit by the farmer and confirmed on collection.</p>
    <a class="btn btn-ghost" href="<?= e(url('/marketplace')) ?>">See all <?= (int)$stats['listings'] ?> live harvests →</a>
  </div>
</section>

<section class="section" style="background:var(--paper);border-top:1px solid var(--line);border-bottom:1px solid var(--line);max-width:none;padding-left:0;padding-right:0">
  <div style="max-width:var(--wrap);margin:auto;padding:0 24px">
    <div class="split">
      <div class="reveal">
        <p class="eyebrow">The problem we fix</p>
        <h2>Farmers grow the food. Middlemen keep the margin.</h2>
        <p class="lede">By the time tomatoes reach a Lagos kitchen they have passed through three hands, lost weight and gained a markup nobody can explain. Buyers cannot verify supply. Farmers cannot prove what they have.</p>
        <div class="grid grid-2" style="margin-top:26px">
          <div class="card card-hover">
            <div class="icon-badge" aria-hidden="true">₦</div>
            <h3>Farmers keep the price</h3>
            <p class="small muted" style="margin:0">A farmer publishes quantity, unit price and pickup window. Buyers order that listing directly — no negotiated cut in between.</p>
          </div>
          <div class="card card-hover">
            <div class="icon-badge" aria-hidden="true">✓</div>
            <h3>Buyers see the supply</h3>
            <p class="small muted" style="margin:0">Stock counts are live, farms are verified, and every order carries a reference with a status trail.</p>
          </div>
        </div>
      </div>
      <div class="reveal d1">
        <div style="border-radius:var(--radius);overflow:hidden;border:1px solid var(--line);box-shadow:var(--shadow)">
          <img src="https://images.unsplash.com/photo-1464226184884-fa280b87c399?auto=format&fit=crop&w=1200&q=80" alt="Rows of crops on a Nigerian farm" style="width:100%;height:430px;object-fit:cover">
        </div>
        <div class="card" style="margin-top:-46px;margin-left:22px;margin-right:22px;position:relative;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
          <div>
            <strong style="display:block;font-family:var(--font-display);font-size:1rem;color:var(--forest-deep)">Built for the way markets actually work</strong>
            <span class="small muted">Cash on collection · Weighed in front of the buyer</span>
          </div>
          <span class="badge badge-gold"><?= (int)$stats['orders'] ?> orders handled</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="how">
  <div class="section-head reveal">
    <p class="eyebrow">How it works</p>
    <h2>Three steps from listing to pickup.</h2>
    <p class="lede">No app to install, no negotiation calls. The marketplace holds the quantity while both sides agree on the handover.</p>
  </div>
  <div class="steps">
    <div class="step reveal">
      <h3>Farmers list what is ready</h3>
      <p>Produce, category, quantity, unit and price, plus a pickup window and location. The listing goes live immediately and shows the real remaining stock.</p>
    </div>
    <div class="step reveal d1">
      <h3>Buyers reserve a quantity</h3>
      <p>The buyer chooses pickup or delivery, a date and notes. The quantity is held atomically — the listing cannot be oversold while the farmer decides.</p>
    </div>
    <div class="step reveal d2">
      <h3>Handover is confirmed</h3>
      <p>The farmer accepts, packs and marks the order ready. The buyer gets an email at every step and the order closes once collected.</p>
    </div>
  </div>
</section>

<?php if ($farms): ?>
<section class="section" style="padding-top:0">
  <div class="section-head reveal">
    <p class="eyebrow">Verified storefronts</p>
    <h2>Farms you can name.</h2>
    <p class="lede">Every verified farm has a real address, a produce focus and a contact behind the listing.</p>
  </div>
  <div class="grid grid-3">
    <?php foreach ($farms as $farm): ?>
      <article class="farm-card reveal">
        <a href="<?= e(url('/farm/' . $farm['id'])) ?>" aria-label="Open <?= e($farm['farm_name']) ?>">
          <div class="farm-media" style="background-image:url('<?= e($farm['photo_url'] ?: 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=900&q=80') ?>')"></div>
        </a>
        <div class="farm-body">
          <div class="flex-between" style="margin-bottom:6px">
            <span class="badge badge-ok">Verified</span>
            <span class="small muted"><?= e($farm['lga']) ?>, <?= e($farm['state']) ?></span>
          </div>
          <h3><a href="<?= e(url('/farm/' . $farm['id'])) ?>"><?= e($farm['farm_name']) ?></a></h3>
          <p class="farm-loc"><?= e($farm['name']) ?> · <?= (int)$farm['live_listings'] ?> live listing<?= (int)$farm['live_listings'] === 1 ? '' : 's' ?></p>
          <span class="farm-focus"><?= e($farm['produce_focus'] ?: 'Mixed produce') ?></span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section" id="farmers">
  <div class="cta-band reveal">
    <div>
      <p class="eyebrow" style="color:var(--gold)">For farmers</p>
      <h2>Sell at your price, to buyers who show up.</h2>
      <p>Create a farm profile, list what is ready this week and let buyers come to you. You decide the quantity, the unit price and the pickup window — FarmCoop keeps the record straight.</p>
    </div>
    <div class="btn-row">
      <a class="btn btn-gold btn-lg" href="<?= e(url('/register?role=farmer')) ?>">Create a farm account</a>
      <a class="btn btn-ghost btn-lg" style="border-color:rgba(255,255,255,.3);color:#fff" href="<?= e(url('/login')) ?>">Sign in</a>
    </div>
  </div>
</section>
