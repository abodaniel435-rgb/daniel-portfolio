<?php
$remainingPct = (float)$listing['quantity_listed'] > 0
    ? max(0, min(100, ((float)$listing['quantity'] / (float)$listing['quantity_listed']) * 100))
    : 0;
$role = Auth::role();
$isOwner = (int)$listing['farmer_id'] === (int)Auth::id();
?>
<section class="page-hero" style="padding-bottom:38px">
  <div class="page-hero-inner" style="padding-bottom:0">
    <span class="crumb">
      <a href="<?= e(url('/marketplace')) ?>">Marketplace</a> /
      <a href="<?= e(url('/marketplace?category=' . urlencode($listing['category']))) ?>"><?= e(category_label($listing['category'])) ?></a>
    </span>
    <h1><?= e($listing['produce_name']) ?></h1>
    <p>
      <?php if ($listing['farm_name']): ?>
        Listed by <a href="<?= e(url('/farm/' . $listing['farm_id'])) ?>" style="color:var(--gold)"><?= e($listing['farm_name']) ?></a>
      <?php else: ?>
        Listed by <?= e($listing['farmer_name']) ?>
      <?php endif; ?>
      · <?= e($listing['lga']) ?>, <?= e($listing['state']) ?>
    </p>
  </div>
</section>

<section class="section" style="padding-top:36px">
  <div class="detail-grid">
    <div>
      <div class="detail-media reveal">
        <img src="<?= e(listing_image($listing)) ?>" alt="<?= e($listing['produce_name']) ?>">
      </div>

      <div class="detail-body reveal">
        <div class="detail-meta">
          <span class="badge badge-gold"><?= e(category_label($listing['category'])) ?></span>
          <?= fc_badge($listing['status']) ?>
          <?php if ((int)$listing['is_verified'] === 1): ?><span class="badge badge-ok">Verified farm</span><?php endif; ?>
          <span class="badge badge-neutral">Ref <?= e($listing['reference']) ?></span>
        </div>

        <div class="price-block">
          <span class="amount"><?= e(money((float)$listing['unit_price'])) ?></span>
          <span class="per">per <?= e($listing['unit']) ?></span>
          <span class="stock">
            <strong style="color:var(--forest);display:block;font-size:1rem"><?= e(fc_qty((float)$listing['quantity'], $listing['unit'])) ?> left</strong>
            <?= e(fc_qty((float)$listing['quantity_listed'], $listing['unit'])) ?> listed
          </span>
        </div>
        <div class="stock-meter" aria-hidden="true"><i style="width:<?= (int)round($remainingPct) ?>%"></i></div>

        <h2 style="font-size:1.25rem;margin:26px 0 10px">About this harvest</h2>
        <div class="prose">
          <?php foreach (array_filter(preg_split('/\n\s*\n/', (string)$listing['description'])) as $paragraph): ?>
            <p><?= nl2br(e(trim($paragraph))) ?></p>
          <?php endforeach; ?>
        </div>

        <h2 style="font-size:1.25rem;margin:26px 0 14px">Harvest details</h2>
        <dl class="spec-list">
          <div><dt>Available from</dt><dd><?= e(short_date($listing['available_from'])) ?></dd></div>
          <div><dt>Pickup window</dt><dd><?= e($listing['pickup_window'] ?: 'Arrange with the farmer') ?></dd></div>
          <div><dt>Quantity remaining</dt><dd><?= e(fc_qty((float)$listing['quantity'], $listing['unit'])) ?></dd></div>
          <div><dt>Unit</dt><dd>Per <?= e($listing['unit']) ?></dd></div>
          <div><dt>Pickup location</dt><dd><?= e($listing['location'] ?: ($listing['lga'] . ', ' . $listing['state'])) ?></dd></div>
          <div><dt>Listed</dt><dd><?= e(short_date($listing['created_at'])) ?></dd></div>
        </dl>
      </div>
    </div>

    <aside class="seller-card reveal d1">
      <div class="flex-between" style="margin-bottom:12px">
        <span class="eyebrow" style="margin:0">The farm</span>
        <?php if ((int)$listing['is_verified'] === 1): ?><span class="badge badge-ok">✓ Verified</span><?php else: ?><span class="badge badge-neutral">Unverified</span><?php endif; ?>
      </div>
      <?php if ($listing['farm_photo']): ?>
        <img src="<?= e($listing['farm_photo']) ?>" alt="<?= e($listing['farm_name']) ?>" style="width:100%;height:132px;object-fit:cover;border-radius:12px;margin-bottom:14px" loading="lazy">
      <?php endif; ?>
      <h3><?= e($listing['farm_name'] ?: $listing['farmer_name']) ?></h3>
      <p class="seller-line"><?= e($listing['farm_lga'] ?: $listing['lga']) ?>, <?= e($listing['farm_state'] ?: $listing['state']) ?> · <?= e($listing['farmer_name']) ?></p>

      <?php if ($listing['produce_focus']): ?>
        <p class="small muted" style="margin-bottom:12px"><strong style="color:var(--forest)">Focus:</strong> <?= e($listing['produce_focus']) ?></p>
      <?php endif; ?>
      <?php if ($listing['farm_about']): ?>
        <p class="small muted" style="margin-bottom:16px"><?= e(mb_strimwidth((string)$listing['farm_about'], 0, 190, '…')) ?></p>
      <?php endif; ?>

      <?php if ($isOwner): ?>
        <div class="owner-note">This is your listing. Manage quantity and status from your farmer workspace.</div>
        <a class="btn btn-primary btn-block" href="<?= e(url('/farmer/listings/' . $listing['id'] . '/edit')) ?>">Edit listing</a>
      <?php elseif ($canOrder): ?>
        <div class="buy-box">
          <div class="total"><span>Price per <?= e($listing['unit']) ?></span><b><?= e(money((float)$listing['unit_price'])) ?></b></div>
          <p class="small muted" style="margin:0 0 12px">Quantity is held the moment you order. The farmer confirms within 24 hours.</p>
          <?php if ($role === 'buyer'): ?>
            <a class="btn btn-gold btn-block" href="<?= e(url('/buyer/checkout/' . $listing['id'])) ?>">Reserve this harvest</a>
          <?php elseif ($role === 'guest'): ?>
            <a class="btn btn-gold btn-block" href="<?= e(url('/login')) ?>">Sign in to reserve</a>
            <p class="hint" style="text-align:center">New to FarmCoop? <a href="<?= e(url('/register?role=buyer')) ?>">Create a buyer account</a></p>
          <?php else: ?>
            <div class="owner-note" style="margin:0">You are signed in as <?= e($role) ?>. Buyer accounts place orders on FarmCoop.</div>
          <?php endif; ?>
        </div>
        <ul class="small muted" style="padding-left:18px;margin:0 0 16px">
          <li>Pay the farmer on collection</li>
          <li>Produce weighed with you at pickup</li>
          <li>Free cancellation while the order is pending</li>
        </ul>
      <?php else: ?>
        <div class="owner-note">This harvest is <?= e($listing['status']) ?> and cannot take new orders right now.</div>
        <a class="btn btn-ghost btn-block" href="<?= e(url('/marketplace')) ?>">Browse similar produce</a>
      <?php endif; ?>

      <?php if ($listing['farm_id']): ?>
        <a class="btn btn-ghost btn-block" style="margin-top:10px" href="<?= e(url('/farm/' . $listing['farm_id'])) ?>">Visit the farm page</a>
      <?php endif; ?>
      <?php if ($listing['farmer_phone'] && ($isOwner || $role === 'buyer')): ?>
        <p class="hint" style="text-align:center;margin-top:12px">Farm contact: <?= e($listing['farmer_phone']) ?></p>
      <?php endif; ?>
    </aside>
  </div>

  <?php if ($related): ?>
    <div class="related">
      <div class="flex-between" style="margin-bottom:18px">
        <h2 style="margin:0;font-size:1.5rem">More <?= e(strtolower(category_label($listing['category']))) ?> on the market</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/marketplace?category=' . urlencode($listing['category']))) ?>">View category</a>
      </div>
      <div class="grid grid-3">
        <?php foreach ($related as $item): ?>
          <article class="produce-card">
            <a class="produce-link" href="<?= e(url('/listing/' . $item['id'])) ?>" aria-label="View <?= e($item['produce_name']) ?>"></a>
            <div class="produce-media">
              <img src="<?= e(listing_image($item)) ?>" alt="<?= e($item['produce_name']) ?>" loading="lazy">
              <div class="produce-tags"><span class="badge badge-gold"><?= e(category_label($item['category'])) ?></span></div>
              <span class="produce-qty"><?= e(fc_qty((float)$item['quantity'], $item['unit'])) ?> available</span>
            </div>
            <div class="produce-body">
              <span class="produce-farm"><?= e($item['farm_name'] ?: 'FarmCoop farm') ?> · <?= e($item['state']) ?></span>
              <h3 class="produce-title"><a href="<?= e(url('/listing/' . $item['id'])) ?>"><?= e($item['produce_name']) ?></a></h3>
              <div class="produce-foot">
                <span class="produce-price"><?= e(money((float)$item['unit_price'])) ?><small>per <?= e($item['unit']) ?></small></span>
                <span class="produce-meta">From <?= e(short_date($item['available_from'])) ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
