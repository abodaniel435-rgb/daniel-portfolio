<?php
$query = static function (array $overrides) use ($status, $circleId, $search): string {
    $statusParam = array_key_exists('status', $overrides) ? $overrides['status'] : $status;
    $pageParam = array_key_exists('page', $overrides) ? $overrides['page'] : null;
    $params = array_filter([
        'status' => $statusParam,
        'circle_id' => $circleId ?: null,
        'q' => $search !== '' ? $search : null,
        'page' => $pageParam,
    ], static fn ($v): bool => $v !== null && $v !== '');
    return url('/admin/contributions' . ($params ? '?' . http_build_query($params) : ''));
};
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Contributions</h1>
        <p>Every round across every circle. Verify a reference to add it to the pool, or mark a round late.</p>
      </div>
      <span class="badge badge-warn"><?= (int)$queued ?> awaiting verification</span>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div class="tab-links">
          <a class="<?= $status === '' ? 'active' : '' ?>" href="<?= e($query(['status' => null, 'page' => null])) ?>">All</a>
          <a class="<?= $status === 'submitted' ? 'active' : '' ?>" href="<?= e($query(['status' => 'submitted', 'page' => null])) ?>">Awaiting verification</a>
          <a class="<?= $status === 'pending' ? 'active' : '' ?>" href="<?= e($query(['status' => 'pending', 'page' => null])) ?>">Pending</a>
          <a class="<?= $status === 'late' ? 'active' : '' ?>" href="<?= e($query(['status' => 'late', 'page' => null])) ?>">Late</a>
          <a class="<?= $status === 'paid' ? 'active' : '' ?>" href="<?= e($query(['status' => 'paid', 'page' => null])) ?>">Verified</a>
        </div>
        <form class="toolbar" method="get" action="<?= e(url('/admin/contributions')) ?>" style="margin:0">
          <input type="hidden" name="status" value="<?= e($status) ?>">
          <select name="circle_id" aria-label="Circle">
            <option value="">All circles</option>
            <?php foreach ($circles as $circle): ?>
              <option value="<?= (int)$circle['id'] ?>" <?= $circleId === (int)$circle['id'] ? 'selected' : '' ?>><?= e($circle['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="search" name="q" value="<?= e($search) ?>" placeholder="Member, email or reference">
          <button class="btn btn-dark btn-sm" type="submit">Filter</button>
        </form>
      </div>

      <?php if (!$rows): ?>
        <div class="empty"><strong>No contributions match</strong>Try a different status or clear the filters.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Member</th><th>Circle</th><th>Round</th><th>Due</th><th class="right">Amount</th><th>Reference</th><th>Method</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><strong><?= e($row['member_name']) ?></strong><span class="sub">Position #<?= (int)$row['position'] ?> · <?= e($row['email']) ?></span></td>
                <td><?= e($row['circle_name']) ?></td>
                <td>#<?= (int)$row['round_no'] ?></td>
                <td class="nowrap"><?= e(short_date($row['due_date'])) ?></td>
                <td class="right num"><?= money((float)$row['amount']) ?></td>
                <td class="mono"><?= $row['payment_reference'] ? e($row['payment_reference']) : '—' ?></td>
                <td><?= e(ucwords(str_replace('_', ' ', $row['method']))) ?></td>
                <td><?= pill($row['status']) ?></td>
                <td class="right">
                  <div class="table-actions">
                    <?php if ($row['status'] !== 'paid' && !empty($row['payment_reference'])): ?>
                      <form method="post" action="<?= e(url('/admin/contributions/' . $row['id'] . '/verify')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-primary btn-sm" type="submit">Verify</button>
                      </form>
                    <?php endif; ?>
                    <?php if ($row['status'] === 'pending'): ?>
                      <form method="post" action="<?= e(url('/admin/contributions/' . $row['id'] . '/late')) ?>" data-confirm="Mark this round late?">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger btn-sm" type="submit">Late</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pager['pages'] > 1): ?>
          <div class="pagination">
            <?php for ($page = 1; $page <= $pager['pages']; $page++): ?>
              <?php if ($page === $pager['current']): ?>
                <span class="current"><?= $page ?></span>
              <?php else: ?>
                <a href="<?= e($query(['page' => $page])) ?>"><?= $page ?></a>
              <?php endif; ?>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
