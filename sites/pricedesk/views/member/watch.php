<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $markets @var int $marketId @var array<int,array<string,mixed>> $commodities @var array<int,array<string,mixed>> $watching */
$watchingFor = static function (int $commodityId) use ($watching, $marketId): bool {
    foreach ($watching as $row) {
        if ((int)$row['commodity_id'] === $commodityId && (int)$row['market_id'] === $marketId) {
            return true;
        }
    }
    return false;
};
$currentMarket = null;
foreach ($markets as $market) {
    if ((int)$market['id'] === $marketId) {
        $currentMarket = $market;
    }
}
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Notifications you asked for, by line</p>
      <h1>Watchlist</h1>
      <p class="lede">Watching is per market and per commodity, because "tell me about tomato" is not a
        question anybody can answer with one price. You are told twice, and the two moments mean different
        things: once when a member files a quote — it is only queued — and once when a reviewer puts it on
        the board.</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/board')) ?>">See the board</a>
  </div>

  <div class="stat-row" style="margin:0 0 26px">
    <div class="stat"><b><?= count($watching) ?></b><span>lines watched</span></div>
    <div class="stat"><b><?= count($markets) ?></b><span>markets on the board</span></div>
    <div class="stat"><b><?= count($commodities) ?></b><span>commodities tracked</span></div>
  </div>

  <form method="get" action="<?= e(url('/watch')) ?>" class="filters panel">
    <div class="field">
      <label for="market">Show the shelf for</label>
      <select name="market" id="market">
        <?php foreach ($markets as $market): ?>
          <option value="<?= (int)$market['id'] ?>" <?= (int)$market['id'] === $marketId ? 'selected' : '' ?>>
            <?= e((string)$market['name']) ?> · <?= e((string)$market['state']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Show</button>
    <p class="tiny" style="margin:0;max-width:36ch">
      <?= $currentMarket === null ? '' : e((string)$currentMarket['name']) ?> is tracked below. Tap a line
      to watch it; tap it again to stop.
    </p>
  </form>

  <div class="optgroup-list">
    <?php foreach ($commodities as $commodity): ?>
      <?php $on = $watchingFor((int)$commodity['id']); ?>
      <div class="toggle <?= $on ? 'toggle-on' : '' ?>">
        <span>
          <b><?= e((string)$commodity['name']) ?></b>
          <span><?= e((string)$commodity['category']) ?> · <?= e((string)$commodity['unit_label']) ?></span>
        </span>
        <form method="post" action="<?= e(url('/watch')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="market_id" value="<?= (int)$marketId ?>">
          <input type="hidden" name="commodity_id" value="<?= (int)$commodity['id'] ?>">
          <button class="btn btn-sm <?= $on ? 'btn-bad' : 'btn-ghost' ?>" type="submit">
            <?= $on ? 'Watching — stop' : 'Watch this line' ?>
          </button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($watching !== []): ?>
    <div class="section">
      <div class="section-head"><h2>Everything you watch, in one list</h2></div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th scope="col">Commodity</th>
              <th scope="col">Market</th>
              <th scope="col">Watching since</th>
              <th scope="col"><span class="visually-hidden">Stop</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($watching as $row): ?>
              <tr>
                <td><?= e((string)$row['commodity_name']) ?></td>
                <td><?= e((string)$row['market_name']) ?></td>
                <td class="mono"><?= e(short_date((string)$row['created_at'])) ?></td>
                <td class="n">
                  <form method="post" action="<?= e(url('/watch')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="market_id" value="<?= (int)$row['market_id'] ?>">
                    <input type="hidden" name="commodity_id" value="<?= (int)$row['commodity_id'] ?>">
                    <button class="btn btn-bad btn-sm" type="submit">Stop</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="tiny" style="margin-top:14px">The weekly sweep also reads this list: a line you watch that
        has had nothing verified for seven days earns you one nudge, not seven. That guard is a query
        against <span class="mono">email_outbox</span>, so it survives a reload and a different browser.</p>
    </div>
  <?php endif; ?>
</section>
