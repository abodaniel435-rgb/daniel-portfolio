<section class="page-hero">
  <div class="page-hero-inner">
    <span class="crumb"><a href="<?= e(url('/marketplace')) ?>">Marketplace</a> / Farm storefront</span>
    <div class="flex-between" style="align-items:flex-end">
      <div>
        <h1><?= e($farm['farm_name']) ?></h1>
        <p><?= e($farm['lga']) ?>, <?= e($farm['state']) ?> · <?= count($listings) ?> live listing<?= count($listings) === 1 ? '' : 's' ?> · <?= (int)$ordersFulfilled ?> fulfilled order<?= (int)$ordersFulfilled === 1 ? '' : 's' ?></p>
      </div>
      <?php if ((int)$farm['is_verified'] === 1): ?>
        <span class="badge badge-ok" style="font-size:.8rem;padding:8px 14px">✓ Verified farm</span>
      <?php else: ?>
        <span class="badge badge-neutral" style="font-size:.8rem;padding:8px 14px">Awaiting verification</span>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:38px">
  <div class="split" style="align-items:start;margin-bottom:44px">
    <div class="reveal" style="border-radius:var(--radius);overflow:hidden;border:1px solid var(--line);box-shadow:var(--shadow-sm)">
      <img src="<?= e($farm['photo_url'] ?: 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=1200&q=80') ?>" alt="<?= e($farm['farm_name']) ?>" style="width:100%;height:360px;object-fit:cover">
    </div>
    <div class="reveal d1">
      <p class="eyebrow">About the farm</p>
      <h2 style="font-size:1.9rem"><?= e($farm['produce_focus'] ?: 'Mixed produce') ?></h2>
      <div class="prose">
        <?php if ($farm['about']): ?>
          <?php foreach (array_filter(preg_split('/\n\s*\n/', (string)$farm['about'])) as $paragraph): ?>
            <p><?= nl2br(e(trim($paragraph))) ?></p>
          <?php endforeach; ?>
        <?php else: ?>
          <p><?= e($farm['farm_name']) ?> sells direct from <?= e($farm['lga']) ?>, <?= e($farm['state']) ?>. Contact the farm through any listing to arrange a visit before you commit to a bulk order.</p>
        <?php endif; ?>
      </div>
      <dl class="kv" style="margin-top:20px">
        <dt>Farmer</dt><dd><?= e($farm['name']) ?></dd>
        <dt>Location</dt><dd><?= e($farm['lga']) ?>, <?= e($farm['state']) ?></dd>
        <dt>On FarmCoop since</dt><dd><?= e(short_date($farm['joined_at'])) ?></dd>
        <dt>Verification</dt><dd><?= (int)$farm['is_verified'] === 1 ? 'Verified by FarmCoop operations' : 'Not yet verified' ?></dd>
      </dl>
      <div class="btn-row" style="margin-top:22px">
        <a class="btn btn-primary" href="<?= e(url('/marketplace?state=' . urlencode($farm['state']))) ?>">Produce in <?= e($farm['state']) ?></a>
        <?php if ($farm['phone']): ?><a class="btn btn-ghost" href="tel:<?= e($farm['phone']) ?>">Call the farm</a><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="flex-between" style="margin-bottom:20px">
    <h2 style="margin:0;font-size:1.6rem">Live from <?= e($farm['farm_name']) ?></h2>
    <span class="pill-note">Quantities update as buyers order</span>
  </div>

  <?php if (!$listings): ?>
    <div class="empty">
      <strong>Nothing listed right now</strong>
      <p style="margin:0">This farm has no live harvests at the moment. Check the wider marketplace or come back after the next harvest.</p>
      <a class="btn btn-primary" href="<?= e(url('/marketplace')) ?>">Browse the marketplace</a>
    </div>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($listings as $index => $item): ?>
        <article class="produce-card reveal <?= $index % 3 === 1 ? 'd1' : ($index % 3 === 2 ? 'd2' : '') ?>">
          <a class="produce-link" href="<?= e(url('/listing/' . $item['id'])) ?>" aria-label="View <?= e($item['produce_name']) ?>"></a>
          <div class="produce-media">
            <img src="<?= e(listing_image($item)) ?>" alt="<?= e($item['produce_name']) ?>" loading="lazy">
            <div class="produce-tags"><span class="badge badge-gold"><?= e(category_label($item['category'])) ?></span></div>
            <span class="produce-qty"><?= e(fc_qty((float)$item['quantity'], $item['unit'])) ?> available</span>
          </div>
          <div class="produce-body">
            <span class="produce-farm"><?= e($item['lga']) ?> · <?= e($item['state']) ?></span>
            <h3 class="produce-title"><a href="<?= e(url('/listing/' . $item['id'])) ?>"><?= e($item['produce_name']) ?></a></h3>
            <p class="produce-desc"><?= e(mb_strimwidth((string)$item['description'], 0, 108, '…')) ?></p>
            <div class="produce-foot">
              <span class="produce-price"><?= e(money((float)$item['unit_price'])) ?><small>per <?= e($item['unit']) ?></small></span>
              <span class="produce-meta">From <?= e(short_date($item['available_from'])) ?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
