<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>My route</h1>
        <p><?= e($courier['zone']) ?> · <?= e(ucfirst($courier['vehicle'])) ?><?= $courier['plate_number'] ? ' · ' . e($courier['plate_number']) : '' ?> · <?= e(date('D, M j')) ?></p>
      </div>
      <form method="post" action="<?= e(url('/courier/availability')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <button class="btn <?= (int)$courier['is_available'] === 1 ? 'btn-ghost' : 'btn-primary' ?> btn-sm" type="submit">
          <?= (int)$courier['is_available'] === 1 ? 'Go off duty' : 'Go on duty' ?>
        </button>
      </form>
    </div>

    <?php if ((int)$courier['is_available'] !== 1): ?>
      <div class="warn-note" style="margin-bottom:20px">You are marked off duty. Dispatch will not assign new parcels until you go back on duty.</div>
    <?php endif; ?>

    <div class="kpi-row">
      <div class="kpi kpi-cyan"><span>Open parcels</span><strong><?= (int)$stats['open'] ?></strong><small>Assigned to you right now</small></div>
      <div class="kpi kpi-amber"><span>Delivered today</span><strong><?= (int)$stats['delivered_today'] ?></strong><small>Keep the streak going</small></div>
      <div class="kpi"><span>Lifetime deliveries</span><strong><?= (int)$stats['delivered_total'] ?></strong><small>Recorded against your name</small></div>
      <div class="kpi"><span>Fees collected today</span><strong><?= money((float)$stats['fees_today']) ?></strong><small>Cash handled on your route</small></div>
    </div>

    <div class="panel panel-flush">
      <div class="panel-head">
        <div><h2>Today's queue</h2><p class="sub">Update each parcel as you move — the recipient is emailed instantly</p></div>
        <span class="pill pill-near"><?= (int)$stats['open'] ?> active</span>
      </div>
      <div style="padding:0 22px 22px">
        <?php if (!$queue): ?>
          <div class="empty">
            <strong>Nothing assigned yet</strong>
            Dispatch will drop parcels into this queue. Keep your status on duty and check back shortly.
          </div>
        <?php else: ?>
          <?php foreach ($queue as $row):
              $transitions = rp_allowed_transitions($row['status']);
          ?>
            <article class="ship-card">
              <div class="ship-card-top">
                <a class="code-chip" href="<?= e(url('/courier/shipment/' . $row['id'])) ?>"><?= e($row['tracking_code']) ?></a>
                <?= rp_pill($row['status'], 'pill-live') ?>
                <?php if ($row['flagged_delayed_at']): ?><span class="tag">Delayed</span><?php endif; ?>
                <span class="tag"><?= e(rp_service_label($row['service'])) ?></span>
              </div>

              <div class="ship-route">
                <span><?= e($row['origin']) ?></span>
                <span class="arrow">→</span>
                <span><?= e($row['destination']) ?></span>
              </div>
              <div class="ship-meta">
                <span>Recipient: <b><?= e($row['recipient_name']) ?></b></span>
                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $row['recipient_phone'])) ?>"><?= e($row['recipient_phone']) ?></a>
                <span><?= e((string)$row['weight_kg']) ?> kg</span>
                <span>Pickup <?= e(short_date($row['pickup_date'])) ?></span>
                <span>Fee <b><?= money((float)$row['fee']) ?></b></span>
              </div>
              <div class="ship-meta" style="margin-top:6px">
                <span><?= e($row['package_description']) ?></span>
                <?php if ($row['last_location']): ?><span>Last scan: <b><?= e($row['last_location']) ?></b> · <?= e(rp_time_ago($row['updated_at'])) ?></span><?php endif; ?>
              </div>

              <?php if ($transitions): ?>
                <form class="ship-update" method="post" action="<?= e(url('/courier/shipment/' . $row['id'] . '/status')) ?>" data-status-form>
                  <?= csrf_field() ?>
                  <div class="form-row-3">
                    <div>
                      <label for="status-<?= (int)$row['id'] ?>">Move to</label>
                      <select id="status-<?= (int)$row['id'] ?>" name="status" required>
                        <?php foreach ($transitions as $next): ?>
                          <option value="<?= e($next) ?>" <?= $next === 'out_for_delivery' ? 'selected' : '' ?>><?= e(rp_status_label($next)) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div>
                      <label for="location-<?= (int)$row['id'] ?>">Where are you?</label>
                      <input id="location-<?= (int)$row['id'] ?>" name="location" required maxlength="140" placeholder="e.g. Yaba, Lagos" value="<?= e($row['last_location']) ?>">
                    </div>
                    <div>
                      <label for="note-<?= (int)$row['id'] ?>">Note <span class="muted small">(optional)</span></label>
                      <input id="note-<?= (int)$row['id'] ?>" name="note" maxlength="500" placeholder="What happened?">
                    </div>
                  </div>
                  <div class="actions">
                    <button class="btn btn-primary btn-sm" type="submit">Save update</button>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/track?code=' . rawurlencode($row['tracking_code']))) ?>" target="_blank" rel="noopener">Customer view ↗</a>
                    <span class="hint" style="margin:0">A failed delivery needs a reason in the note.</span>
                  </div>
                </form>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel panel-flush">
      <div class="panel-head"><div><h2>Recently completed</h2><p class="sub">Your last eight closed parcels</p></div></div>
      <?php if (!$completed): ?>
        <div style="padding:0 22px 22px"><div class="empty"><strong>No closed parcels yet</strong>Delivered and failed parcels will be listed here.</div></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Tracking</th><th>Route</th><th>Recipient</th><th>Updated</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($completed as $row): ?>
              <tr>
                <td><a class="code-chip" href="<?= e(url('/courier/shipment/' . $row['id'])) ?>"><?= e($row['tracking_code']) ?></a></td>
                <td><?= e($row['origin']) ?><span class="cell-sub">→ <?= e($row['destination']) ?></span></td>
                <td><?= e($row['recipient_name']) ?></td>
                <td><?= e(rp_time_ago($row['updated_at'])) ?></td>
                <td><?= rp_pill($row['status']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
