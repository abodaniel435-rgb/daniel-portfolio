<section class="page-hero">
  <div class="page-hero-inner">
    <span class="crumb">FarmCoop / Marketplace</span>
    <h1>Buy straight from the farm gate.</h1>
    <p>Every listing below is published by a farmer with a real quantity, a unit price and a pickup window. Reserve what you need and the stock is held for you.</p>
  </div>
</section>

<section class="section" style="padding-top:34px">
  <form class="filter-bar" method="get" action="<?= e(url('/marketplace')) ?>" role="search">
    <div class="field">
      <label for="q">Search</label>
      <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Produce, farm or town" autocomplete="off">
    </div>
    <div class="field">
      <label for="category">Category</label>
      <select id="category" name="category">
        <option value="">All produce</option>
        <?php foreach ($categories as $slug => $label): ?>
          <option value="<?= e($slug) ?>" <?= $category === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="state">State</label>
      <select id="state" name="state">
        <option value="">Anywhere</option>
        <?php foreach ($states as $name): ?>
          <option value="<?= e($name) ?>" <?= $state === $name ? 'selected' : '' ?>><?= e($name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="sort">Sort</label>
      <select id="sort" name="sort">
        <option value="fresh" <?= $sort === 'fresh' ? 'selected' : '' ?>>Ready soonest</option>
        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
        <option value="quantity" <?= $sort === 'quantity' ? 'selected' : '' ?>>Largest quantity</option>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Apply</button>
  </form>

  <div class="chip-row" style="margin-bottom:20px" role="group" aria-label="Filter by category">
    <?php
      $chipQuery = static function (array $overrides) use ($category, $state, $q, $sort): string {
          $params = array_filter(array_merge(['category' => $category, 'state' => $state, 'q' => $q, 'sort' => $sort], $overrides), static fn ($v) => $v !== '' && $v !== null);
          return url('/marketplace' . ($params ? '?' . http_build_query($params) : ''));
      };
    ?>
    <a class="chip <?= $category === '' ? 'active' : '' ?>" href="<?= e($chipQuery(['category' => '', 'page' => null])) ?>">All produce</a>
    <?php foreach ($categories as $slug => $label): ?>
      <a class="chip <?= $category === $slug ? 'active' : '' ?>" href="<?= e($chipQuery(['category' => $slug, 'page' => null])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="result-bar">
    <span><strong><?= (int)$total ?></strong> live harvest<?= (int)$total === 1 ? '' : 's' ?><?= $q !== '' ? ' matching “' . e($q) . '”' : '' ?><?= $state !== '' ? ' in ' . e($state) : '' ?></span>
    <?php if ($category !== '' || $state !== '' || $q !== ''): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/marketplace')) ?>">Clear filters</a>
    <?php else: ?>
      <span class="pill-note">Ordering holds stock instantly</span>
    <?php endif; ?>
  </div>

  <?php if (!$listings): ?>
    <div class="empty reveal">
      <strong>No harvest matches those filters</strong>
      <p style="margin:0">Try a different category or state — new listings are published every day by verified farms.</p>
      <a class="btn btn-primary" href="<?= e(url('/marketplace')) ?>">Reset the marketplace</a>
    </div>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($listings as $index => $item): ?>
        <article class="produce-card reveal <?= $index % 3 === 1 ? 'd1' : ($index % 3 === 2 ? 'd2' : '') ?>">
          <a class="produce-link" href="<?= e(url('/listing/' . $item['id'])) ?>" aria-label="View <?= e($item['produce_name']) ?>"></a>
          <div class="produce-media">
            <img src="<?= e(listing_image($item)) ?>" alt="<?= e($item['produce_name']) ?>" loading="lazy">
            <div class="produce-tags">
              <span class="badge badge-gold"><?= e(category_label($item['category'])) ?></span>
              <?php if ((int)$item['is_verified'] === 1): ?><span class="badge badge-ok">Verified</span><?php endif; ?>
            </div>
            <span class="produce-qty"><?= e(fc_qty((float)$item['quantity'], $item['unit'])) ?> available</span>
          </div>
          <div class="produce-body">
            <span class="produce-farm"><?= e($item['farm_name'] ?: $item['farmer_name']) ?> · <?= e($item['state']) ?></span>
            <h3 class="produce-title"><a href="<?= e(url('/listing/' . $item['id'])) ?>"><?= e($item['produce_name']) ?></a></h3>
            <p class="produce-desc"><?= e(mb_strimwidth((string)$item['description'], 0, 110, '…')) ?></p>
            <div class="produce-foot">
              <span class="produce-price"><?= e(money((float)$item['unit_price'])) ?><small>per <?= e($item['unit']) ?></small></span>
              <span class="produce-meta">From <?= e(short_date($item['available_from'])) ?><br><?= e($item['lga']) ?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if ($page['pages'] > 1): ?>
      <nav class="pagination" aria-label="Marketplace pages">
        <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
          <?php if ($page['pages'] > 7 && $i > 2 && $i < $page['pages'] - 1 && abs($i - $page['current']) > 1): ?>
            <?php if ($i === 3): ?><span>…</span><?php endif; ?>
            <?php continue; ?>
          <?php endif; ?>
          <?php if ($i === $page['current']): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="<?= e($chipQuery(['page' => $i])) ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
