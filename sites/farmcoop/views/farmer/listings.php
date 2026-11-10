<?php
$tabs = ['' => 'All', 'open' => 'Live', 'paused' => 'Paused', 'reserved' => 'Reserved', 'sold' => 'Sold', 'expired' => 'Expired'];
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Harvest listings</h1>
        <p>Pause a harvest when it is not ready, reserve it for a buyer, or close it once the field is empty.</p>
      </div>
      <a class="btn btn-primary" href="<?= e(url('/farmer/listings/new')) ?>">+ New listing</a>
    </div>

    <?php if (!$profile): ?>
      <div class="note" style="margin-bottom:20px">Your farm profile is incomplete — buyers cannot verify your farm until it is filled in. <a href="<?= e(url('/farmer/profile')) ?>">Complete it now →</a></div>
    <?php endif; ?>

    <div class="tab-links">
      <?php foreach ($tabs as $key => $label): ?>
        <a class="<?= $status === $key ? 'active' : '' ?>" href="<?= e(url('/farmer/listings' . ($key !== '' ? '?status=' . $key : ''))) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$rows): ?>
      <div class="empty">
        <strong>No listings in this view</strong>
        <p style="margin:0">Publish what is ready on the farm — buyers see live quantity and can reserve immediately.</p>
        <a class="btn btn-primary" href="<?= e(url('/farmer/listings/new')) ?>">Create a listing</a>
      </div>
    <?php else: ?>
      <div class="panel" style="padding:0;overflow:hidden">
        <div class="table-wrap" style="border:0">
          <table class="data">
            <thead>
              <tr><th>Produce</th><th>Price</th><th>Remaining</th><th>Orders</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td>
                  <div style="display:flex;gap:11px;align-items:center;min-width:220px">
                    <img src="<?= e(listing_image($row)) ?>" alt="" width="48" height="48" style="width:48px;height:48px;border-radius:8px;object-fit:cover;flex:0 0 auto" loading="lazy">
                    <div>
                      <span class="strong"><?= e($row['produce_name']) ?></span><br>
                      <small class="muted"><?= e(category_label($row['category'])) ?> · <?= e($row['lga']) ?>, <?= e($row['state']) ?></small><br>
                      <small class="order-ref"><?= e($row['reference']) ?></small>
                    </div>
                  </div>
                </td>
                <td class="nowrap"><?= e(money((float)$row['unit_price'])) ?><br><small class="muted">per <?= e($row['unit']) ?></small></td>
                <td class="nowrap"><strong><?= e(fc_qty((float)$row['quantity'], $row['unit'])) ?></strong><br><small class="muted">of <?= e(fc_qty((float)$row['quantity_listed'])) ?></small></td>
                <td><?= (int)$row['active_orders'] ?> active<br><small class="muted"><?= (int)$row['total_orders'] ?> total</small></td>
                <td><?= fc_badge($row['status']) ?><br><small class="muted"><?= e(short_date($row['available_from'])) ?></small></td>
                <td>
                  <div class="table-actions">
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/listing/' . $row['id'])) ?>">View</a>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/farmer/listings/' . $row['id'] . '/edit')) ?>">Edit</a>
                    <?php if ($row['status'] === 'open'): ?>
                      <form method="post" action="<?= e(url('/farmer/listings/' . $row['id'] . '/status')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="pause">
                        <button class="btn btn-ghost btn-sm" type="submit">Pause</button>
                      </form>
                      <form method="post" action="<?= e(url('/farmer/listings/' . $row['id'] . '/status')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="reserve">
                        <button class="btn btn-ghost btn-sm" type="submit">Reserve</button>
                      </form>
                    <?php elseif ($row['status'] === 'paused'): ?>
                      <form method="post" action="<?= e(url('/farmer/listings/' . $row['id'] . '/status')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="resume">
                        <button class="btn btn-soft btn-sm" type="submit">Resume</button>
                      </form>
                    <?php elseif (in_array($row['status'], ['reserved', 'expired'], true)): ?>
                      <form method="post" action="<?= e(url('/farmer/listings/' . $row['id'] . '/status')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="release">
                        <button class="btn btn-soft btn-sm" type="submit">Relist</button>
                      </form>
                    <?php endif; ?>
                    <?php if ($row['status'] !== 'sold' && (int)$row['active_orders'] === 0): ?>
                      <form method="post" action="<?= e(url('/farmer/listings/' . $row['id'] . '/status')) ?>" data-confirm="Mark this listing as sold? Buyers will no longer be able to order it.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="sold">
                        <button class="btn btn-ghost btn-sm" type="submit">Mark sold</button>
                      </form>
                    <?php endif; ?>
                    <?php if ((int)$row['total_orders'] === 0): ?>
                      <form method="post" action="<?= e(url('/farmer/listings/' . $row['id'] . '/delete')) ?>" data-confirm="Delete this listing permanently?">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <div class="panel" style="margin-top:22px">
      <div class="panel-head"><h2>Listing rules</h2><span class="badge badge-gold">Fair market</span></div>
      <div class="grid grid-3">
        <div><strong style="display:block;font-size:.9rem">Live quantity only</strong><p class="small muted" style="margin:4px 0 0">The quantity you publish is the quantity buyers can order. Every order decrements it instantly.</p></div>
        <div><strong style="display:block;font-size:.9rem">Pause, don't vanish</strong><p class="small muted" style="margin:4px 0 0">Pausing hides a listing without breaking the record buyers already hold.</p></div>
        <div><strong style="display:block;font-size:.9rem">Orders keep history</strong><p class="small muted" style="margin:4px 0 0">Listings with order history cannot be deleted — mark them sold instead.</p></div>
      </div>
    </div>
  </div>
</div>
