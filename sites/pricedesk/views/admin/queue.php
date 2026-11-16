<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $rows @var array<int,array<string,mixed>> $recent */
require BASE_PATH . '/views/admin/nav.php';
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Oldest first · a price nobody looks at is a price rotting</p>
      <h1>Verification queue</h1>
      <p class="lede">Nothing here is on the board. Each quote has been converted to naira per kilogram
        and set against the verified median for the same commodity in the same market over the previous
        fortnight, so the judgement in front of you is "does this belong", not "can I do long division at
        the market gate".</p>
    </div>
    <div class="stat-row" style="margin:0;max-width:220px">
      <div class="stat"><b><?= count($rows) ?></b><span>waiting</span></div>
    </div>
  </div>

  <?php if ($rows === []): ?>
    <div class="panel">
      <h3>The queue is empty</h3>
      <p style="margin:0">Every submitted price has been decided. That is either a well-run board or a
        board nobody is using — the coverage table on the <a href="<?= e(url('/admin')) ?>">overview</a>
        says which.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($rows as $row): ?>
    <?php
      $deviation = $row['deviationPct'];
      $far = $deviation !== null && abs((float)$deviation) >= 40;
      $peers = (int)$row['peerCount'];
    ?>
    <article class="card queue-item">
      <div>
        <p class="eyebrow" style="margin-bottom:6px">
          Report #<?= (int)$row['id'] ?> · <?= e(short_date((string)$row['report_date'])) ?>
          <?php if ((string)$row['report_date'] !== date('Y-m-d', strtotime((string)$row['created_at']))): ?>
            · filed <?= e(short_datetime((string)$row['created_at'])) ?>
          <?php endif; ?>
        </p>
        <h3 style="margin-bottom:4px"><?= e((string)$row['commodity_name']) ?> at <?= e((string)$row['market_name']) ?></h3>
        <p class="muted" style="margin-bottom:12px">
          filed by <?= e((string)($row['reporter_name'] ?? 'a deleted account')) ?>
        </p>

        <div class="readout-row"><span>Quoted</span>
          <b><?= e(money((float)$row['price'])) ?> per <?= e((string)$row['unit']) ?><?php if ((int)$row['quantity'] > 1) { echo ' × ' . (int)$row['quantity']; } ?></b>
        </div>
        <div class="readout-row"><span>Normalised</span><b><?= e(money((float)$row['perKg'])) ?> / kg</b></div>
        <div class="readout-row"><span>Verified peers, 14 days</span>
          <b><?php if ($peers === 0): ?>
                none yet
              <?php else: ?>
                <?= e(money((float)$row['peerMedian'])) ?> / kg across <?= $peers ?> quote<?= $peers === 1 ? '' : 's' ?>
              <?php endif; ?></b>
        </div>
        <div class="readout-row"><span>Deviation</span>
          <b class="deviation <?= $far ? 'deviation-far' : '' ?>">
            <?= $deviation === null ? 'no peer group to measure against' : e(sprintf('%+.1f%%', (float)$deviation)) ?>
          </b>
        </div>
        <?php if ((string)$row['note'] !== ''): ?>
          <p class="tiny" style="margin:12px 0 0">"<?= e((string)$row['note']) ?>"</p>
        <?php endif; ?>
        <?php if ($peers < 3): ?>
          <p class="tiny" style="margin:10px 0 0;color:#9a7b2f">
            Fewer than three verified peers: the deviation above is a weak signal, and approving this
            quote is what gives the next one something to be measured against.
          </p>
        <?php endif; ?>
      </div>

      <div class="queue-actions">
        <form method="post" action="<?= e(url('/admin/queue/' . (int)$row['id'] . '/verify')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-ok btn-sm" type="submit">Verify — put it on the board</button>
        </form>
        <form method="post" action="<?= e(url('/admin/queue/' . (int)$row['id'] . '/reject')) ?>"
              style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <?= csrf_field() ?>
          <input type="text" name="reason" maxlength="255" required placeholder="Why it is being turned down"
                 style="width:min(280px,60vw)">
          <button class="btn btn-bad btn-sm" type="submit">Reject</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>

  <div class="panel" style="margin-top:26px">
    <h3>What a decision does</h3>
    <div class="grid-3">
      <p class="tiny" style="margin:0"><strong>Verify.</strong> The quote joins the board's median and every
        watcher of that line is emailed that a verified price is live. You are recorded in
        <span class="mono">verified_by</span>.</p>
      <p class="tiny" style="margin:0"><strong>Reject.</strong> The reason is mandatory and the reporter reads
        it on their own history page. A rejection you cannot explain is a rumour with authority.</p>
      <p class="tiny" style="margin:0"><strong>Unverify.</strong> Wrong calls are correctable — see the last
        twenty below. The report returns to this queue and leaves the board, and the audit trail keeps both
        decisions.</p>
    </div>
  </div>

  <div class="section">
    <div class="section-head">
      <h2>On the board — the last twenty you verified</h2>
      <span class="tiny">Pulling one off sends it back to the queue above.</span>
    </div>
    <?php if ($recent === []): ?>
      <div class="panel"><p class="tiny" style="margin:0">Nothing verified yet.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th scope="col">Trading day</th>
              <th scope="col">Commodity</th>
              <th scope="col">Market</th>
              <th scope="col" class="n">Price</th>
              <th scope="col">Reporter</th>
              <th scope="col">Verified by</th>
              <th scope="col"><span class="visually-hidden">Undo</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $row): ?>
              <tr>
                <td class="mono"><?= e(short_date((string)$row['report_date'])) ?></td>
                <td><?= e((string)$row['commodity_name']) ?></td>
                <td><?= e((string)$row['market_name']) ?></td>
                <td class="n"><?= e(money((float)$row['price'])) ?><span class="tiny" style="display:block">/<?= e((string)$row['unit']) ?></span></td>
                <td class="tiny"><?= e((string)($row['reporter_name'] ?? '—')) ?></td>
                <td class="tiny"><?= e((string)($row['verifier_name'] ?? '—')) ?><span class="tiny" style="display:block"><?= e(short_datetime((string)($row['verified_at'] ?? ''))) ?></span></td>
                <td class="n">
                  <form method="post" action="<?= e(url('/admin/queue/' . (int)$row['id'] . '/unverify')) ?>"
                        onsubmit="return confirm('Pull this price off the board? Members may already have traded against it.')">
                    <?= csrf_field() ?>
                    <button class="btn btn-bad btn-sm" type="submit">Unverify</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>
