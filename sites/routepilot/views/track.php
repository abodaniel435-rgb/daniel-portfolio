<?php /** Public tracking page — no login, privacy-safe. */ ?>
<section class="section section-narrow" style="padding-bottom:32px">
  <p class="eyebrow">Public tracking</p>
  <h1 style="font-size:clamp(2rem,4.4vw,3.2rem)">Follow your parcel,<br>scan by scan.</h1>
  <p class="lede">Enter the tracking code from your confirmation email. You will see the current status, the route history and what happens next — no account required.</p>

  <form class="panel" method="get" action="<?= e(url('/track')) ?>" style="margin-top:24px">
    <div class="form-row" style="align-items:end">
      <div>
        <label for="code">Tracking code</label>
        <input id="code" name="code" value="<?= e($code) ?>" placeholder="RP-YYMMDD-XXXX" required
               autocomplete="off" spellcheck="false" class="mono-code" style="letter-spacing:.1em;text-transform:uppercase">
        <p class="hint">Format: RP-YYMMDD-XXXX · found in your shipment email.</p>
      </div>
      <div><button class="btn btn-primary btn-block" type="submit">Track parcel</button></div>
    </div>
  </form>

  <?php if ($error !== ''): ?>
    <div class="panel" style="border-color:rgba(245,165,36,.4)">
      <div class="row-between">
        <div>
          <h3 style="margin-bottom:4px">No tracking record</h3>
          <p class="muted small" style="margin:0"><?= e($error) ?></p>
        </div>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/#track')) ?>">Try from home</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($shipment):
      $first = rp_recipient_first_name($shipment);
      $timeline = rp_timeline($shipment);
      $lastEvent = $events[count($events) - 1] ?? null;
  ?>
    <div class="track-result">
      <div class="track-hero">
        <span class="code"><?= e($shipment['tracking_code']) ?></span>
        <h2><?= e($shipment['origin']) ?> → <?= e($shipment['destination']) ?></h2>
        <p><?= e(rp_service_label($shipment['service'])) ?> service · booked <?= e(short_date($shipment['created_at'])) ?> · <?= e((string)$shipment['weight_kg']) ?>kg parcel</p>
        <div class="row" style="margin-top:16px">
          <?= rp_pill($shipment['status'], rp_is_open($shipment['status']) ? 'pill-live' : '') ?>
          <?php if (!empty($shipment['last_location'])): ?>
            <span style="font-size:.8rem;color:#a9b8cd">Last scan: <strong style="color:#eef3fb"><?= e($shipment['last_location']) ?></strong> · <?= e(rp_time_ago($lastEvent['created_at'] ?? null)) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="track-grid">
        <div class="panel" style="margin:0">
          <div class="panel-head">
            <div>
              <h2>Route timeline</h2>
              <p class="sub"><?= count($events) ?> recorded scan<?= count($events) === 1 ? '' : 's' ?> · times shown in West Africa Time</p>
            </div>
            <button class="copy-btn" type="button" data-copy="<?= e(rp_track_url($shipment['tracking_code'])) ?>">Copy link</button>
          </div>

          <div class="progress-strip" aria-hidden="true">
            <?php foreach ($timeline as $step): ?>
              <div class="progress-step <?= $step['done'] ? 'done' : '' ?>">
                <span class="progress-dot"></span>
                <span class="progress-label"><?= e($step['label']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="timeline">
            <?php foreach ($events as $index => $event):
                $isLast = $index === count($events) - 1;
            ?>
              <div class="tl-item done <?= $isLast ? 'current' : '' ?> <?= $event['status'] === 'failed' ? 'failed' : '' ?>">
                <span class="tl-dot"><?= $isLast ? '●' : '✓' ?></span>
                <div class="tl-body">
                  <strong><?= e(rp_status_label($event['status'])) ?><?= $isLast ? ' · latest' : '' ?></strong>
                  <span><?= e($event['location'] !== '' ? $event['location'] : 'Location not captured') ?> · <?= e(date('D, M j · g:i A', strtotime($event['created_at']))) ?></span>
                  <?php if ($event['note'] !== ''): ?>
                    <div class="tl-note"><?= e($event['note']) ?></div>
                  <?php endif; ?>
                  <div class="tl-meta"><?= e(rp_event_source_label($event['source'])) ?><?= $event['author_name'] ? ' · ' . e($event['author_name']) : '' ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="stack">
          <div class="panel" style="margin:0">
            <h2 style="font-size:1.05rem;margin-bottom:12px">Delivery summary</h2>
            <div class="delivery-eta">
              <span class="dot"></span>
              <div><?= e(rp_eta_copy($shipment)) ?></div>
            </div>
            <dl class="track-facts" style="margin-top:14px">
              <div class="track-fact"><dt>Status</dt><dd><?= e(rp_status_label($shipment['status'])) ?></dd></div>
              <div class="track-fact"><dt>Service</dt><dd><?= e(rp_service_label($shipment['service'])) ?></dd></div>
              <div class="track-fact"><dt>Pickup date</dt><dd><?= e(short_date($shipment['pickup_date'])) ?></dd></div>
              <div class="track-fact"><dt>From</dt><dd><?= e($shipment['origin']) ?></dd></div>
              <div class="track-fact"><dt>To</dt><dd><?= e($shipment['destination']) ?></dd></div>
              <div class="track-fact"><dt>Recipient</dt><dd><?= e($first) ?> <span class="muted small">(first name only)</span></dd></div>
              <div class="track-fact"><dt>Parcel</dt><dd><?= e($shipment['package_description']) ?></dd></div>
              <?php if ($shipment['courier_name']): ?>
                <div class="track-fact"><dt>Courier</dt><dd><?= e($shipment['courier_name']) ?> · <?= e(ucfirst((string)$shipment['courier_vehicle'])) ?></dd></div>
              <?php endif; ?>
            </dl>
          </div>

          <div class="panel" style="margin:0">
            <h3 style="font-size:.98rem">Need help?</h3>
            <p class="small muted">Our dispatch desk is open Monday to Saturday, 7:00–21:00. Quote your tracking code and we will trace the parcel for you.</p>
            <div class="stack">
              <a class="btn btn-ghost btn-sm" href="tel:+2348021110042">Call dispatch · +234 802 111 0042</a>
              <a class="btn btn-ghost btn-sm" href="mailto:dispatch@routepilot.test?subject=<?= e('Tracking ' . $shipment['tracking_code']) ?>">Email dispatch</a>
            </div>
            <p class="hint" style="margin-top:14px">For privacy, this page only shows the recipient's first name and never reveals phone numbers or email addresses.</p>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($error === ''): ?>
    <div class="empty" style="margin-top:22px">
      <strong>Ready when you are</strong>
      Paste a tracking code above to see the route history, current status and expected delivery.
    </div>
  <?php endif; ?>
</section>
