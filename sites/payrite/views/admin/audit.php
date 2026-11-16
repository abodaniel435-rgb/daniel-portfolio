<?php
declare(strict_types=1);
/** @var array $rows @var array $pager */
require BASE_PATH . '/views/admin/nav.php';
$href = static function (int $page): string {
    return e(url('/admin/audit') . '?page=' . $page);
};
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Audit trail</p>
      <h1>Every change, with a name against it</h1>
      <p class="lede">Roster edits, rate saves, runs built and locked, accounts linked, notices queued. The point of a
        payroll system is not that the arithmetic is right on the day — it is that six months later you can show who
        changed what, and when.</p>
    </div>
    <p class="tiny"><?= number_format((int)$pager['total']) ?> entries · page <?= (int)$pager['current'] ?> of <?= (int)$pager['pages'] ?></p>
  </div>

  <?php if (!$rows): ?>
    <p class="tiny">Nothing recorded yet.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Target</th><th>Detail</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php $meta = json_decode((string)($row['meta'] ?? ''), true); ?>
          <tr>
            <td class="tiny mono"><?= e(short_datetime((string)$row['created_at'])) ?></td>
            <td><?= e((string)($row['actor'] ?? 'system')) ?>
              <?php if (!empty($row['actor_id'])): ?><div class="tiny mono muted">#<?= (int)$row['actor_id'] ?></div><?php endif; ?></td>
            <td><span class="mono"><?= e((string)$row['action']) ?></span></td>
            <td class="tiny"><?= e((string)($row['target'] ?? '')) ?: '—' ?></td>
            <td class="tiny mono">
              <?php if (is_array($meta) && $meta): ?>
                <?php foreach ($meta as $key => $value): ?>
                  <?= e((string)$key) ?>=<?= e(is_scalar($value) ? (string)$value : json_encode($value)) ?><br>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ((int)$pager['pages'] > 1): ?>
      <nav class="tabs" style="margin-top:20px" aria-label="Pages">
        <?php if ((int)$pager['current'] > 1): ?>
          <a class="chip" href="<?= $href((int)$pager['current'] - 1) ?>">← Newer</a>
        <?php endif; ?>
        <?php
        $from = max(1, (int)$pager['current'] - 3);
        $to = min((int)$pager['pages'], $from + 6);
        ?>
        <?php for ($page = $from; $page <= $to; $page++): ?>
          <a class="chip<?= $page === (int)$pager['current'] ? ' is-active' : '' ?>" href="<?= $href($page) ?>"><?= $page ?></a>
        <?php endfor; ?>
        <?php if ((int)$pager['current'] < (int)$pager['pages']): ?>
          <a class="chip" href="<?= $href((int)$pager['current'] + 1) ?>">Older →</a>
        <?php endif; ?>
      </nav>
      <p class="tiny">Thirty entries a page, newest first. Nothing here is editable through the application — and an
        audit row that could be edited would not be evidence.</p>
    <?php endif; ?>
  <?php endif; ?>
</section>
