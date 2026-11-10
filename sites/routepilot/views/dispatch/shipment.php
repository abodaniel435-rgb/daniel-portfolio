<?php
$open = rp_is_open($shipment['status']);
$trackUrl = rp_track_url($shipment['tracking_code']);
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <div class="row" style="gap:10px;margin-bottom:8px">
          <span class="code-chip" style="font-size:.82rem"><?= e($shipment['tracking_code']) ?></span>
          <?= rp_pill($shipment['status'], $open ? 'pill-live' : '') ?>
          <?php if ($shipment['flagged_delayed_at']): ?><span class="tag">Delayed</span><?php endif; ?>
          <?php if ($shipment['flagged_late_pickup_at']): ?><span class="tag">Late pickup</span><?php endif; ?>
        </div>
        <h1 style="font-size:clamp(1.4rem,2.6vw,1.95rem)"><?= e($shipment['origin']) ?> → <?= e($shipment['destination']) ?></h1>
        <p><?= e(rp_service_label($shipment['service'])) ?> · pickup <?= e(short_date($shipment['pickup_date'])) ?> · created <?= e(rp_time_ago($shipment['created_at'])) ?></p>
      </div>
      <div class="row">
        <button class="btn btn-ghost btn-sm" type="button" data-copy="<?= e($trackUrl) ?>">Copy tracking link</button>
        <a class="btn btn-ghost btn-sm" href="<?= e($trackUrl) ?>" target="_blank" rel="noopener">Public page ↗</a>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/dispatch/shipments')) ?>">All shipments</a>
      </div>
    </div>

    <?php if ($shipment['flagged_delayed_at'] || $shipment['flagged_late_pickup_at']): ?>
      <div class="warn-note" style="margin-bottom:20px">
        <strong>Automation flag.</strong>
        <?php if ($shipment['flagged_late_pickup_at']): ?>Pickup date passed while unassigned (<?= e(short_datetime($shipment['flagged_late_pickup_at'])) ?>).<?php endif; ?>
        <?php if ($shipment['flagged_delayed_at']): ?>No courier scan for over 3 days (<?= e(short_datetime($shipment['flagged_delayed_at'])) ?>).<?php endif; ?>
        Assign or reassign this parcel to keep the customer promise.
      </div>
    <?php endif; ?>

    <div class="grid" style="grid-template-columns:1.5fr .95fr;align-items:start">
      <div class="stack">
        <div class="panel">
          <div class="panel-head">
            <div><h2>Route timeline</h2><p class="sub"><?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?> · every scan is immutable</p></div>
            <?= rp_pill($shipment['status']) ?>
          </div>
          <div class="timeline">
            <?php foreach ($events as $index => $event):
                $isLast = $index === count($events) - 1;
            ?>
              <div class="tl-item done <?= $isLast ? 'current' : '' ?> <?= $event['status'] === 'failed' ? 'failed' : '' ?>">
                <span class="tl-dot"><?= $isLast ? '●' : '✓' ?></span>
                <div class="tl-body">
                  <strong><?= e(rp_status_label($event['status'])) ?><?= $isLast ? ' · latest' : '' ?></strong>
                  <span><?= e($event['location'] !== '' ? $event['location'] : 'Location not captured') ?> · <?= e(date('M j, Y · g:i A', strtotime($event['created_at']))) ?></span>
                  <?php if ($event['note'] !== ''): ?><div class="tl-note"><?= e($event['note']) ?></div><?php endif; ?>
                  <div class="tl-meta"><?= e(rp_event_source_label($event['source'])) ?><?= $event['author_name'] ? ' · ' . e($event['author_name']) : '' ?> · <?= e(rp_time_ago($event['created_at'])) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($transitions): ?>
          <div class="panel">
            <div class="panel-head"><div><h2>Record a status update</h2><p class="sub">Use this when the rider radios an update to the desk</p></div></div>
            <form method="post" action="<?= e(url('/dispatch/shipments/' . $shipment['id'] . '/status')) ?>" class="grid" data-status-form>
              <?= csrf_field() ?>
              <div class="form-row-3">
                <div>
                  <label for="status">New status</label>
                  <select id="status" name="status" required>
                    <?php foreach ($transitions as $next): ?>
                      <option value="<?= e($next) ?>"><?= e(rp_status_label($next)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="location">Location</label>
                  <input id="location" name="location" required maxlength="140" placeholder="e.g. Yaba, Lagos" value="<?= e($shipment['last_location']) ?>">
                </div>
                <div>
                  <label for="note">Note <span class="muted small">(optional)</span></label>
                  <input id="note" name="note" maxlength="500" placeholder="What happened?">
                </div>
              </div>
              <div class="row"><button class="btn btn-primary btn-sm" type="submit">Save update &amp; notify recipient</button></div>
            </form>
          </div>
        <?php else: ?>
          <div class="panel">
            <div class="note">This shipment is closed (<?= e(rp_status_label($shipment['status'])) ?>). The route history is now read-only.</div>
          </div>
        <?php endif; ?>
      </div>

      <div class="stack">
        <div class="panel" style="margin:0">
          <div class="panel-head"><div><h2>Courier</h2></div><?= $shipment['courier_id'] ? rp_pill('assigned') : '<span class="tag">Unassigned</span>' ?></div>
          <?php if ($shipment['courier_id']): ?>
            <div class="row" style="gap:12px;margin-bottom:14px">
              <span class="avatar" style="background:linear-gradient(140deg,var(--amber-soft),var(--amber));color:#231604;width:42px;height:42px"><?= e(strtoupper(mb_substr((string)$shipment['courier_name'], 0, 1))) ?></span>
              <div>
                <strong style="display:block"><?= e($shipment['courier_name']) ?></strong>
                <span class="small muted"><?= e(ucfirst((string)$shipment['courier_vehicle'])) ?> · <?= e((string)$shipment['courier_zone']) ?><?= $shipment['courier_plate'] ? ' · ' . e($shipment['courier_plate']) : '' ?></span>
              </div>
            </div>
            <dl class="kv">
              <dt>Courier phone</dt><dd><?= e((string)$shipment['courier_phone']) ?></dd>
              <dt>Assigned</dt><dd><?= e(short_datetime($shipment['assigned_at'])) ?></dd>
              <dt>Last scan</dt><dd><?= e($shipment['last_location'] ?: '—') ?></dd>
            </dl>
            <div class="divider"></div>
            <?php if (rp_open_for_reassign($shipment['status'])): ?>
              <form method="post" action="<?= e(url('/dispatch/shipments/' . $shipment['id'] . '/unassign')) ?>" data-confirm="Return this parcel to the unassigned queue?" class="inline-form">
                <?= csrf_field() ?>
                <button class="btn btn-danger btn-sm btn-block" type="submit">Unassign courier</button>
              </form>
            <?php else: ?>
              <p class="hint" style="margin:0">A parcel already collected cannot be unassigned — record a failed delivery instead.</p>
            <?php endif; ?>
          <?php else: ?>
            <p class="small muted">No rider is holding this parcel. Assign one to move it forward.</p>
          <?php endif; ?>

          <?php if ($open): ?>
            <div class="divider"></div>
            <form method="post" action="<?= e(url('/dispatch/shipments/' . $shipment['id'] . '/assign')) ?>" class="form-grid">
              <?= csrf_field() ?>
              <div>
                <label for="assign_courier"><?= $shipment['courier_id'] ? 'Reassign to' : 'Assign a courier' ?></label>
                <select id="assign_courier" name="courier_id" required>
                  <option value="">Choose a rider…</option>
                  <?php foreach ($couriers as $courier): ?>
                    <option value="<?= (int)$courier['id'] ?>" <?= (int)$courier['is_available'] === 1 ? '' : 'disabled' ?>>
                      <?= e($courier['name']) ?> · <?= e($courier['zone']) ?> · <?= (int)$courier['open_shipments'] ?>/<?= (int)$courier['max_daily_shipments'] ?><?= (int)$courier['is_available'] === 1 ? '' : ' (off duty)' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn btn-primary btn-sm" type="submit"><?= $shipment['courier_id'] ? 'Reassign parcel' : 'Assign parcel' ?></button>
            </form>
          <?php endif; ?>
        </div>

        <div class="panel" style="margin:0">
          <h2 style="font-size:1.05rem">Shipment details</h2>
          <dl class="kv">
            <dt>Sender</dt><dd><?= e($shipment['sender_name']) ?><br><span class="small muted"><?= e($shipment['sender_phone']) ?></span></dd>
            <dt>Recipient</dt><dd><?= e($shipment['recipient_name']) ?><br><span class="small muted"><?= e($shipment['recipient_phone']) ?></span><br><span class="small muted"><?= e($shipment['recipient_email']) ?></span></dd>
            <dt>Parcel</dt><dd><?= e($shipment['package_description']) ?></dd>
            <dt>Weight</dt><dd><?= e((string)$shipment['weight_kg']) ?> kg</dd>
            <dt>Service</dt><dd><?= e(rp_service_label($shipment['service'])) ?></dd>
            <dt>Fee</dt><dd><?= money((float)$shipment['fee']) ?></dd>
            <dt>Pickup date</dt><dd><?= e(short_date($shipment['pickup_date'])) ?></dd>
            <dt>Created by</dt><dd><?= e($shipment['creator_name'] ?: 'Dispatch') ?></dd>
            <?php if ($shipment['delivered_at']): ?><dt>Delivered</dt><dd><?= e(short_datetime($shipment['delivered_at'])) ?></dd><?php endif; ?>
            <?php if ($shipment['failed_reason']): ?><dt>Failure note</dt><dd><?= e($shipment['failed_reason']) ?></dd><?php endif; ?>
          </dl>
          <div class="divider"></div>
          <p class="hint" style="margin:0">Customer tracking: <a href="<?= e($trackUrl) ?>" target="_blank" rel="noopener"><?= e($shipment['tracking_code']) ?></a></p>
        </div>

        <div class="panel" style="margin:0">
          <h3 style="font-size:.98rem">Notifications</h3>
          <p class="small muted" style="margin:0">The recipient was emailed when the shipment was booked and on every status change. Delivery confirmation is sent automatically on success.</p>
          <?php if (Auth::is('admin')): ?>
            <div class="row mt-2">
              <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/outbox')) ?>">Open email outbox</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
