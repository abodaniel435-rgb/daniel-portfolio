<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $markets @var array<int,array<string,mixed>> $rows @var int|null $marketId @var string $marketName @var int $days */
$confLabel = ['solid' => 'Solid', 'fair' => 'Fair', 'thin' => 'Thin', 'disputed' => 'Disputed', 'none' => 'None'];
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Verified quotes only · last <?= (int)$days ?> days</p>
      <h1><?= e($marketName === 'every market' ? 'Every market' : $marketName) ?></h1>
      <p class="lede">Every figure below is naira per kilogram, converted from whatever unit the reporter
        used before anything was combined. The middle of the band is printed, never the average, and the
        spread tells you how much of it is agreement and how much is guesswork.</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/report')) ?>">File today's price</a>
  </div>

  <form method="get" action="<?= e(url('/board')) ?>" class="filters panel">
    <div class="field">
      <label for="market">Market</label>
      <select name="market" id="market">
        <option value="">Every market</option>
        <?php foreach ($markets as $market): ?>
          <option value="<?= (int)$market['id'] ?>" <?= $marketId === (int)$market['id'] ? 'selected' : '' ?>>
            <?= e($market['name']) ?> · <?= e($market['state']) ?> (<?= e((string)$market['kind']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field" style="min-width:150px">
      <label for="days">Window</label>
      <select name="days" id="days">
        <?php foreach ([7 => 'Last 7 days', 14 => 'Last 14 days', 30 => 'Last 30 days'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= $days === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Show the board</button>
    <p class="tiny" style="margin:0;max-width:34ch">A longer window answers "what is fair around here".
      A short one answers "what did it cost this morning".</p>
  </form>

  <?php if ($rows === []): ?>
    <div class="panel">
      <h3>Nothing verified for this view yet</h3>
      <p>A board is only worth as much as the quotes in it. Widen the window above, choose another
        market, or file the first price yourself — it reaches here once a reviewer has looked at it.</p>
      <p style="margin:0"><a class="btn btn-primary btn-sm" href="<?= e(url('/register')) ?>">Join and file a price</a></p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <caption>
          <?= count($rows) ?> line<?= count($rows) === 1 ? '' : 's' ?>, ranked by how many verified quotes back each one.
          <?php if ($marketId === null && isset($rows[0]['markets'])): ?>
            <span class="tiny">The range spans <?= (int)$rows[0]['markets'] ?> markets on purpose; the spread is
              measured <em>inside</em> each one, so it answers "does this market agree" and not "do markets agree
              with each other".</span>
          <?php endif; ?>
        </caption>
        <thead>
          <tr>
            <th scope="col">Commodity</th>
            <th scope="col" class="n">Median ₦/kg</th>
            <th scope="col" class="n">Range</th>
            <th scope="col" class="n">Spread</th>
            <th scope="col" class="n">Quotes</th>
            <th scope="col">Confidence</th>
            <th scope="col">Last filed</th>
            <th scope="col"><span class="visually-hidden">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php $confidence = (string)$row['confidence']; ?>
            <tr>
              <td class="commodity">
                <?= e((string)$row['commodity']) ?>
                <small><?= e((string)$row['category']) ?> · priced as <?= e((string)$row['unit_label']) ?></small>
              </td>
              <td class="n"><?= e(money((float)$row['median'])) ?></td>
              <td class="n"><?= e(money((float)$row['min'])) ?> – <?= e(money((float)$row['max'])) ?></td>
              <td class="n">±<?= e((string)$row['spreadPct']) ?>%</td>
              <td class="n"><?= (int)$row['count'] ?></td>
              <td>
                <span class="conf conf-<?= e($confidence) ?>"><?= e($confLabel[$confidence] ?? ucfirst($confidence)) ?></span>
              </td>
              <td>
                <?php $fresh = (string)$row['freshness']; ?>
                <span class="fresh fresh-<?= e($fresh) ?>"><?= e(ucfirst($fresh)) ?></span>
                <span class="tiny" style="display:block"><?= e(short_date($row['lastReport'] ?? null)) ?></span>
              </td>
              <td class="n">
                <a class="btn btn-ghost btn-sm" href="<?= e(url('/compare')) ?>?commodity=<?= (int)$row['commodity_id'] ?>">Haul maths</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="legend">
      <span><span class="conf conf-solid">solid</span> eight quotes or more, close agreement</span>
      <span><span class="conf conf-fair">fair</span> enough quotes to act on</span>
      <span><span class="conf conf-thin">thin</span> fewer than three, treat as a hint</span>
      <span><span class="conf conf-disputed">disputed</span> the market itself does not agree</span>
    </div>

    <div class="panel" style="margin-top:22px">
      <h3>Reading a line before you trade on it</h3>
      <div class="grid-3">
        <p class="tiny" style="margin:0"><strong>Check the spread, not the headline.</strong> A ₦1,450 median
          with a ±2% spread is a price. The same median at ±34% is three different arguments averaged
          together, and the board labels it <em>disputed</em> rather than pretending.</p>
        <p class="tiny" style="margin:0"><strong>Check who is behind it.</strong> Wholesale floors and city
          retail stalls are different commodities at different moments. Filter to one market when you are
          buying there; read all markets when you are deciding whether to drive.</p>
        <p class="tiny" style="margin:0"><strong>Check the date.</strong> A line marked
          <span class="fresh fresh-stale">stale</span> is not cheap, it is old. Nothing on this board is
          deleted silently — pull the JSON at <span class="mono">/api/quote</span> to see the quotes
          underneath.</p>
      </div>
    </div>
  <?php endif; ?>
</section>
