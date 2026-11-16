<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $rows @var array<string,mixed> $tally */
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Everything you have filed</p>
      <h1>Your reports</h1>
      <p class="lede">This is your record against the board: what you quoted, what it came to per
        kilogram, and what a reviewer decided. A rejection carries its reason, because being told
        "no" without being told why teaches nobody anything.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/report')) ?>">File another price</a>
  </div>

  <div class="stat-row" style="margin:0 0 26px">
    <div class="stat"><b><?= (int)$tally['total'] ?></b><span>prices filed</span></div>
    <div class="stat"><b><?= (int)$tally['verified'] ?></b><span>on the board</span></div>
    <div class="stat"><b><?= (int)$tally['pending'] ?></b><span>waiting for review</span></div>
    <div class="stat"><b><?= (int)$tally['rejected'] ?></b><span>turned down</span></div>
    <div class="stat"><b><?= e(short_date($tally['latest'] ?? null)) ?></b><span>most recent trading day</span></div>
  </div>

  <?php if ($rows === []): ?>
    <div class="panel">
      <h3>Nothing filed yet</h3>
      <p style="margin-bottom:14px">The board you are reading is somebody's Tuesday morning. Add yours
        and it stops being one person's opinion about prices.</p>
      <a class="btn btn-primary btn-sm" href="<?= e(url('/report')) ?>">File today's price</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <caption>Most recent trading days first. Withdraw is available while a quote is still yours alone.</caption>
        <thead>
          <tr>
            <th scope="col">Day</th>
            <th scope="col">Market</th>
            <th scope="col">Commodity</th>
            <th scope="col" class="n">You quoted</th>
            <th scope="col" class="n">Per kilogram</th>
            <th scope="col">Status</th>
            <th scope="col">Note</th>
            <th scope="col"><span class="visually-hidden">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="mono"><?= e(short_date((string)$row['report_date'])) ?></td>
              <td><?= e((string)$row['market_name']) ?></td>
              <td><?= e((string)$row['commodity_name']) ?></td>
              <td class="n"><?= e(money((float)$row['price'])) ?><span class="tiny" style="display:block">per <?= e((string)$row['unit']) ?><?= (int)$row['quantity'] > 1 ? ' × ' . (int)$row['quantity'] : '' ?></span></td>
              <td class="n"><?= e(money((float)$row['perKg'])) ?></td>
              <td>
                <?= status_badge((string)$row['status']) ?>
                <?php if ((string)$row['status'] === 'rejected' && (string)$row['reject_reason'] !== ''): ?>
                  <span class="tiny" style="display:block">"<?= e((string)$row['reject_reason']) ?>"</span>
                <?php endif; ?>
                <?php if ((string)$row['status'] === 'verified' && !empty($row['verified_at'])): ?>
                  <span class="tiny" style="display:block"><?= e(short_datetime((string)$row['verified_at'])) ?></span>
                <?php endif; ?>
              </td>
              <td class="tiny"><?= e((string)$row['note']) ?: '<span class="muted">—</span>' ?></td>
              <td class="n">
                <?php if ((string)$row['status'] !== 'verified'): ?>
                  <form method="post" action="<?= e(url('/history/withdraw')) ?>"
                        onsubmit="return confirm('Withdraw this quote? It leaves the queue entirely.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button class="btn btn-bad btn-sm" type="submit">Withdraw</button>
                  </form>
                <?php else: ?>
                  <span class="tiny">on the board</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
