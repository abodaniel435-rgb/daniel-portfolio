<?php
$qs = static function (array $overrides) use ($q, $status, $category): string {
    $params = array_filter(
        array_merge(['q' => $q, 'status' => $status, 'category' => $category], $overrides),
        static fn ($v) => $v !== '' && $v !== null
    );
    return url('/admin/listings' . ($params ? '?' . http_build_query($params) : ''));
};
$tabs = ['' => 'All', 'open' => 'Live', 'paused' => 'Paused', 'reserved' => 'Reserved', 'sold' => 'Sold', 'expired' => 'Expired'];
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Marketplace listings</h1>
        <p>Every harvest published to the market, with the stock each farmer is still holding.</p>
      </div>
      <span class="pill-note"><?= (int)$page['total'] ?> listing<?= (int)$page['total'] === 1 ? '' : 's' ?></span>
    </div>

    <div class="tab-links">
      <?php foreach ($tabs as $key => $label): ?>
        <a class="<?= $status === $key ? 'active' : '' ?>" href="<?= e($qs(['status' => $key, 'page' => null])) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>

    <form class="toolbar" method="get" action="<?= e(url('/admin/listings')) ?>">
      <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <input name="q" type="search" value="<?= e($q) ?>" placeholder="Search produce, reference or farm" aria-label="Search listings">
      <select name="category" aria-label="Filter by category">
        <option value="">All categories</option>
        <?php foreach ($categories as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $category === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary btn-sm" type="submit">Filter</button>
      <?php if ($q !== '' || $status !== '' || $category !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/listings')) ?>">Reset</a><?php endif; ?>
    </form>

    <?php if (!$rows): ?>
      <div class="empty"><strong>No listings match</strong>Try a different category or clear the search.</div>
    <?php else: ?>
      <div class="panel" style="padding:0;overflow:hidden">
        <div class="table-wrap" style="border:0">
          <table class="data">
            <thead><tr><th>Produce</th><th>Farmer</th><th>Price</th><th>Remaining</th><th>Orders</th><th>Status</th><th>Manage</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td>
                  <div style="display:flex;gap:11px;align-items:center;min-width:210px">
                    <img src="<?= e(listing_image($row)) ?>" alt="" width="44" height="44" style="width:44px;height:44px;border-radius:8px;object-fit:cover;flex:0 0 auto" loading="lazy">
                    <div>
                      <a class="strong" href="<?= e(url('/listing/' . $row['id'])) ?>"><?= e($row['produce_name']) ?></a><br>
                      <small class="muted"><?= e(category_label($row['category'])) ?> · <?= e($row['state']) ?></small><br>
                      <small class="order-ref"><?= e($row['reference']) ?></small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="small" style="font-weight:600"><?= e($row['farm_name'] ?: $row['farmer_name']) ?></span><br>
                  <small class="muted"><?= (int)$row['is_verified'] === 1 ? 'Verified farm' : 'Unverified farm' ?></small>
                </td>
                <td class="nowrap"><?= e(money((float)$row['unit_price'])) ?><br><small class="muted">per <?= e($row['unit']) ?></small></td>
                <td class="nowrap"><strong><?= e(fc_qty((float)$row['quantity'], $row['unit'])) ?></strong><br><small class="muted">of <?= e(fc_qty((float)$row['quantity_listed'], $row['unit'])) ?></small></td>
                <td><small class="muted"><?= (int)$row['order_count'] ?> total</small></td>
                <td><?= fc_badge($row['status']) ?></td>
                <td>
                  <div class="table-actions">
                    <form method="post" action="<?= e(url('/admin/listings/' . $row['id'] . '/status')) ?>">
                      <?= csrf_field() ?>
                      <select name="status" aria-label="Set status for <?= e($row['produce_name']) ?>" style="padding:6px 9px;font-size:.8rem;border-radius:8px;width:auto">
                        <?php foreach (['open', 'paused', 'reserved', 'sold', 'expired'] as $option): ?>
                          <option value="<?= e($option) ?>" <?= $row['status'] === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button class="btn btn-ghost btn-sm" type="submit" data-confirm="Change the status of this listing? Buyers see the change immediately.">Save</button>
                    </form>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/farm/' . $row['farmer_id'])) ?>">Farm</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if ($page['pages'] > 1): ?>
        <nav class="pagination" aria-label="Listing pages">
          <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
            <?php if ($i === $page['current']): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= e($qs(['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head"><h2>Moderation notes</h2><span class="badge badge-gold">Marketplace</span></div>
      <div class="grid grid-3">
        <div><strong class="small" style="display:block">Pausing is reversible</strong><span class="small muted">Paused listings stay visible to the farmer and the admin but drop out of the public market.</span></div>
        <div><strong class="small" style="display:block">Expiring is automatic</strong><span class="small muted">The automation job expires produce past its available date once there are no active orders.</span></div>
        <div><strong class="small" style="display:block">Empty stock cannot reopen</strong><span class="small muted">A listing at zero remaining quantity must be topped up before it can go live again.</span></div>
      </div>
    </div>
  </div>
</div>
