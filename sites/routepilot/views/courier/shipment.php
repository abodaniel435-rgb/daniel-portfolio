<?php $open = rp_is_open($shipment['status']); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <div class="row" style="gap:10px;margin-bottom:8px">
          <span class="code-chip" style="font-size:.82rem"><?= e($shipment['tracking_code']) ?></span>
          <?= rp_pill($shipment['status'], $open ? 'pill-live' : '') ?>
        </div>
        <h1 style="font-size:clamp(1.4rem,2.6vw,1.95rem)"><?= e($shipment['origin']) ?> → <?= e($shipment['destination']) ?></h1>
        <p><?= e(rp_service_label($shipment['service'])) ?> · pickup <?= e(short_date($shipment['pickup_date'])) ?></p>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/courier/queue')) ?>">← Back to my route</a>
    </div>

    <div class="grid" style="grid-template-columns:1.35fr .95fr;align-items:start">
      <div class="stack">
        <div class="panel">
          <div class="panel-head"><div><h2>Route timeline</h2><p class="sub">Everything recorded on this parcel</p></div></div>
          <div class="timeline">
            <?php foreach ($events as $index => $event):
                $isLast = $index === count($events) - 1;
            ?>
              <div class="tl-item done <?= $isLast ? 'current' : '' ?> <?= $event['status'] === 'failed' ? 'failed' : '' ?>">
                <span class="tl-dot"><?= $isLast ? '●' : '✓' ?></span>
                <div class="tl-body">
                  <strong><?= e(rp_status_label($event['status'])) ?></strong>
                  <span><?= e($event['location'] ?: 'Location not captured') ?> · <?= e(date('M j · g:i A', strtotime($event['created_at']))) ?></span>
                  <?php if ($event['note'] !== ''): ?><div class="tl-note"><?= e($event['note']) ?></div><?php endif; ?>
                  <div class="tl-meta"><?= e(rp_event_source_label($event['source'])) ?><?= $event['author_name'] ? ' · ' . e($event['author_name']) : '' ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($transitions): ?>
          <div class="panel">
            <div class="panel-head"><div><h2>Update this parcel</h2><p class="sub">Status changes are emailed to the recipient immediately</p></div></div>
            <form method="post" action="<?= e(url('/courier/shipment/' . $shipment['id'] . '/status')) ?>" class="form-grid" data-status-form>
              <?= csrf_field() ?>
              <div class="form-row-3">
                <div>
                  <label for="status">Move to</label>
                  <select id="status" name="status" required>
                    <?php foreach ($transitions as $next): ?>
                      <option value="<?= e($next) ?>" <?= $next === 'out_for_delivery' ? 'selected' : '' ?>><?= e(rp_status_label($next)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="location">Location</label>
                  <input id="location" name="location" required maxlength="140" placeholder="e.g. Lekki Phase 1" value="<?= e($shipment['last_location']) ?>">
                </div>
                <div>
                  <label for="note">Note <span class="muted small">(optional)</span></label>
                  <input id="note" name="note" maxlength="500" placeholder="What happened?">
                </div>
              </div>
              <div class="row"><button class="btn btn-primary btn-sm" type="submit">Save update</button></div>
            </form>
          </div>
        <?php else: ?>
          <div class="panel"><div class="note">This parcel is closed (<?= e(rp_status_label($shipment['status'])) ?>). No further updates can be recorded.</div></div>
        <?php endif; ?>
      </div>

      <div class="stack">
        <div class="panel" style="margin:0">
          <h2 style="font-size:1.05rem">Delivery details</h2>
          <dl class="kv">
            <dt>Recipient</dt><dd><?= e($shipment['recipient_name']) ?><br><a class="small" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $shipment['recipient_phone'])) ?>"><?= e($shipment['recipient_phone']) ?></a></dd>
            <dt>Address area</dt><dd><?= e($shipment['destination']) ?></dd>
            <dt>Pickup from</dt><dd><?= e($shipment['origin']) ?><br><span class="small muted"><?= e($shipment['sender_name']) ?> · <?= e($shipment['sender_phone']) ?></span></dd>
            <dt>Package</dt><dd><?= e($shipment['package_description']) ?></dd>
            <dt>Weight</dt><dd><?= e((string)$shipment['weight_kg']) ?> kg</dd>
            <dt>Fee to collect</dt><dd><?= money((float)$shipment['fee']) ?></dd>
            <dt>Last scan</dt><dd><?= e($shipment['last_location'] ?: '—') ?></dd>
          </dl>
          <div class="divider"></div>
          <a class="btn btn-ghost btn-sm btn-block" href="<?= e(url('/track?code=' . rawurlencode($shipment['tracking_code']))) ?>" target="_blank" rel="noopener">Open customer tracking page ↗</a>
        </div>

        <div class="panel" style="margin:0">
          <h3 style="font-size:.98rem">Rider checklist</h3>
          <div class="stack small muted">
            <span>1. Confirm the parcel count with the sender before sealing.</span>
            <span>2. Scan <b>picked up</b> only once the parcel is physically with you.</span>
            <span>3. Update <b>in transit</b> at each major movement.</span>
            <span>4. Scan <b>out for delivery</b> before the final leg.</span>
            <span>5. Collect proof of delivery, then scan <b>delivered</b>.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
