<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>New shipment</h1>
        <p>Capture the parcel once. RoutePilot issues the tracking code and emails the recipient.</p>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/dispatch/shipments')) ?>">← Back to shipments</a>
    </div>

    <form method="post" action="<?= e(url('/dispatch/shipments')) ?>" data-shipment-form>
      <?= csrf_field() ?>
      <div class="grid" style="grid-template-columns:1.62fr .88fr;align-items:start">

        <div class="stack">
          <div class="panel">
            <div class="panel-head"><div><h2>Sender</h2><p class="sub">Who is handing the parcel to the rider</p></div></div>
            <div class="form-row">
              <div>
                <label for="sender_name">Sender name <span class="req">*</span></label>
                <input id="sender_name" name="sender_name" value="<?= e(old('sender_name')) ?>" required maxlength="120" placeholder="Adeola Fashions">
                <?= field_error('sender_name') ?>
              </div>
              <div>
                <label for="sender_phone">Sender phone <span class="req">*</span></label>
                <input id="sender_phone" name="sender_phone" value="<?= e(old('sender_phone')) ?>" required placeholder="+234 803 311 4220">
                <?= field_error('sender_phone') ?>
              </div>
            </div>
          </div>

          <div class="panel">
            <div class="panel-head"><div><h2>Recipient</h2><p class="sub">Only the first name is ever shown publicly</p></div></div>
            <div class="form-row">
              <div>
                <label for="recipient_name">Recipient name <span class="req">*</span></label>
                <input id="recipient_name" name="recipient_name" value="<?= e(old('recipient_name')) ?>" required maxlength="120" placeholder="Chiamaka Obi">
                <?= field_error('recipient_name') ?>
              </div>
              <div>
                <label for="recipient_phone">Recipient phone <span class="req">*</span></label>
                <input id="recipient_phone" name="recipient_phone" value="<?= e(old('recipient_phone')) ?>" required placeholder="+234 802 998 7712">
                <?= field_error('recipient_phone') ?>
              </div>
            </div>
            <div class="mt-2">
              <label for="recipient_email">Recipient email <span class="req">*</span></label>
              <input id="recipient_email" name="recipient_email" type="email" value="<?= e(old('recipient_email')) ?>" required placeholder="recipient@example.com">
              <p class="hint">Receives the tracking link, every status update and the delivery confirmation.</p>
              <?= field_error('recipient_email') ?>
            </div>
          </div>

          <div class="panel">
            <div class="panel-head"><div><h2>Route &amp; parcel</h2><p class="sub">Pickup and drop-off within Lagos</p></div></div>
            <div class="form-row">
              <div>
                <label for="origin">Origin <span class="req">*</span></label>
                <input id="origin" name="origin" value="<?= e(old('origin')) ?>" required maxlength="140" placeholder="Yaba, Lagos" list="rp-zones">
                <?= field_error('origin') ?>
              </div>
              <div>
                <label for="destination">Destination <span class="req">*</span></label>
                <input id="destination" name="destination" value="<?= e(old('destination')) ?>" required maxlength="140" placeholder="Lekki Phase 1, Lagos" list="rp-zones">
                <?= field_error('destination') ?>
              </div>
            </div>
            <datalist id="rp-zones">
              <option value="Yaba, Lagos"></option>
              <option value="Ikeja, Lagos"></option>
              <option value="Lekki Phase 1, Lagos"></option>
              <option value="Victoria Island, Lagos"></option>
              <option value="Ikoyi, Lagos"></option>
              <option value="Surulere, Lagos"></option>
              <option value="Gbagada, Lagos"></option>
              <option value="Maryland, Lagos"></option>
              <option value="Ajah, Lagos"></option>
              <option value="Magodo, Lagos"></option>
              <option value="Apapa, Lagos"></option>
              <option value="Ogba, Lagos"></option>
            </datalist>
            <div class="form-row-3 mt-2">
              <div>
                <label for="package_description">Package description <span class="req">*</span></label>
                <input id="package_description" name="package_description" value="<?= e(old('package_description')) ?>" required maxlength="255" placeholder="Two garment boxes">
                <?= field_error('package_description') ?>
              </div>
              <div>
                <label for="weight_kg">Weight (kg) <span class="req">*</span></label>
                <input id="weight_kg" name="weight_kg" type="number" step="0.1" min="0.1" max="200" value="<?= e(old('weight_kg', '1.0')) ?>" required>
                <?= field_error('weight_kg') ?>
              </div>
              <div>
                <label for="pickup_date">Pickup date <span class="req">*</span></label>
                <input id="pickup_date" name="pickup_date" type="date" value="<?= e(old('pickup_date', date('Y-m-d'))) ?>" required>
                <?= field_error('pickup_date') ?>
              </div>
            </div>
          </div>

          <div class="panel">
            <div class="panel-head"><div><h2>Service &amp; fee</h2><p class="sub">The promise you are making the customer</p></div></div>
            <div class="form-row">
              <div>
                <label for="service">Service level <span class="req">*</span></label>
                <select id="service" name="service" required>
                  <?php foreach ($services as $key => $svc): ?>
                    <option value="<?= e($key) ?>" <?= old('service', 'standard') === $key ? 'selected' : '' ?>><?= e($svc['label']) ?> — <?= e($svc['eta']) ?></option>
                  <?php endforeach; ?>
                </select>
                <p class="hint" data-service-promise></p>
                <?= field_error('service') ?>
              </div>
              <div>
                <label for="fee">Delivery fee (₦) <span class="req">*</span></label>
                <div class="row" style="gap:8px">
                  <input id="fee" name="fee" type="number" step="50" min="0" value="<?= e(old('fee')) ?>" required placeholder="0.00" style="flex:1">
                  <button class="btn btn-ghost btn-sm" type="button" data-fee-suggestion title="Use the suggested quote">—</button>
                </div>
                <p class="hint">Click the quote chip to fill a suggested fee based on service and weight.</p>
                <?= field_error('fee') ?>
              </div>
            </div>
            <div class="mt-2">
              <label for="courier_id">Assign a courier now <span class="muted small">(optional)</span></label>
              <select id="courier_id" name="courier_id">
                <option value="">Leave in the unassigned queue</option>
                <?php foreach ($couriers as $courier): ?>
                  <option value="<?= (int)$courier['id'] ?>" <?= old('courier_id') === (string)$courier['id'] ? 'selected' : '' ?>>
                    <?= e($courier['name']) ?> · <?= e(ucfirst($courier['vehicle'])) ?> · <?= e($courier['zone']) ?> · <?= (int)$courier['open_shipments'] ?> open
                  </option>
                <?php endforeach; ?>
              </select>
              <?= field_error('courier_id') ?>
              <p class="hint">Only available riders with spare capacity are listed.</p>
            </div>
          </div>

          <div class="row">
            <button class="btn btn-primary" type="submit">Create shipment &amp; notify recipient</button>
            <a class="btn btn-ghost" href="<?= e(url('/dispatch')) ?>">Cancel</a>
          </div>
        </div>

        <div class="stack">
          <div class="panel" style="margin:0">
            <h3>What happens next</h3>
            <div class="timeline" style="margin-top:16px">
              <div class="tl-item done"><span class="tl-dot">1</span><div class="tl-body"><strong>Tracking code issued</strong><span>Format RP-YYMMDD-XXXX, unique to this parcel.</span></div></div>
              <div class="tl-item done"><span class="tl-dot">2</span><div class="tl-body"><strong>Recipient emailed</strong><span>Tracking link, route, service promise and parcel summary.</span></div></div>
              <div class="tl-item done"><span class="tl-dot">3</span><div class="tl-body"><strong>Rider scans the parcel</strong><span>Every status update adds a located, timestamped event.</span></div></div>
            </div>
          </div>

          <div class="panel" style="margin:0">
            <h3>Pricing guide</h3>
            <p class="small muted">Base rate plus weight. Same-day and express carry a priority premium.</p>
            <dl class="kv">
              <dt>Same day</dt><dd>₦2,600 base</dd>
              <dt>Express</dt><dd>₦3,200 base</dd>
              <dt>Standard</dt><dd>₦1,800 base</dd>
              <dt>Weight</dt><dd>+₦380 / kg</dd>
            </dl>
            <div class="note mt-2">Automation flags any parcel whose pickup date passes while still unassigned.</div>
          </div>

          <div class="panel" style="margin:0">
            <h3>Privacy by default</h3>
            <p class="small muted" style="margin:0">The public tracking page shows the recipient's first name, the route and the scan history — never their phone number, email or full name.</p>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
