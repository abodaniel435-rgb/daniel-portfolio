<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Courier fleet</h1>
        <p><?= count($couriers) ?> rider<?= count($couriers) === 1 ? '' : 's' ?> on the RoutePilot network · capacity limits are enforced on assignment.</p>
      </div>
      <?php if (Auth::is('admin')): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/users')) ?>">Add a rider</a>
      <?php endif; ?>
    </div>

    <?php if (!$couriers): ?>
      <div class="panel">
        <div class="empty">
          <strong>No couriers on the network yet</strong>
          Riders can self-register from the public site, or an administrator can create accounts from the admin console.
        </div>
      </div>
    <?php else: ?>
      <div class="grid grid-3">
        <?php foreach ($couriers as $courier):
            $capacity = max(1, (int)$courier['max_daily_shipments']);
            $load = min(100, (int)round((int)$courier['open_shipments'] / $capacity * 100));
            $onDuty = (int)$courier['is_active'] === 1 && (int)$courier['is_available'] === 1;
        ?>
          <article class="panel" style="margin:0">
            <div class="row" style="gap:12px;margin-bottom:14px">
              <span class="avatar" style="width:44px;height:44px;font-size:.95rem"><?= e(strtoupper(mb_substr($courier['name'], 0, 1))) ?></span>
              <div style="flex:1;min-width:0">
                <strong style="display:block;font-family:var(--font-display);font-size:1rem"><?= e($courier['name']) ?></strong>
                <span class="small muted"><?= e(ucfirst($courier['vehicle'])) ?> · <?= e($courier['zone']) ?></span>
              </div>
              <?= $onDuty ? '<span class="pill pill-done pill-live">On duty</span>' : '<span class="pill pill-draft">Off duty</span>' ?>
            </div>

            <div class="meter"><i style="width:<?= $load ?>%"></i></div>
            <div class="row-between" style="margin-top:8px">
              <span class="small muted"><?= (int)$courier['open_shipments'] ?>/<?= $capacity ?> open parcels</span>
              <span class="small" style="font-weight:600"><?= $load ?>% load</span>
            </div>

            <dl class="kv mt-2">
              <dt>Delivered today</dt><dd><?= (int)$courier['delivered_today'] ?></dd>
              <dt>Lifetime delivered</dt><dd><?= (int)$courier['lifetime_delivered'] ?></dd>
              <dt>Plate</dt><dd><?= e($courier['plate_number'] ?: '—') ?></dd>
              <dt>Phone</dt><dd><?= e($courier['phone'] ?: '—') ?></dd>
            </dl>

            <div class="row mt-2" style="gap:8px">
              <form method="post" action="<?= e(url('/dispatch/couriers/' . $courier['id'] . '/toggle')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <button class="btn <?= (int)$courier['is_available'] === 1 ? 'btn-ghost' : 'btn-primary' ?> btn-sm" type="submit">
                  <?= (int)$courier['is_available'] === 1 ? 'Set off duty' : 'Set available' ?>
                </button>
              </form>
              <a class="btn btn-ghost btn-sm" href="<?= e(url('/dispatch/shipments?courier=' . $courier['id'])) ?>">View parcels</a>
            </div>
            <?php if ((int)$courier['is_active'] !== 1): ?>
              <p class="hint" style="margin-top:10px">Account deactivated by an administrator — this rider cannot sign in.</p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="panel mt-3">
      <div class="panel-head"><div><h2>Assignment rules</h2><p class="sub">Applied on every dispatch action</p></div></div>
      <div class="grid grid-3">
        <div><strong class="small">Capacity is enforced</strong><p class="hint" style="margin-top:4px">A rider cannot receive more open parcels than their daily capacity. Raise capacity in the admin console.</p></div>
        <div><strong class="small">Only available riders</strong><p class="hint" style="margin-top:4px">Off-duty or deactivated riders are excluded from assignment lists automatically.</p></div>
        <div><strong class="small">Reassignment is logged</strong><p class="hint" style="margin-top:4px">Every assign, unassign and reassign writes a tracking event and an audit-log entry.</p></div>
      </div>
    </div>
  </div>
</div>
