<?php
$qs = static function (array $overrides) use ($q, $role): string {
    $params = array_filter(array_merge(['q' => $q, 'role' => $role], $overrides), static fn ($v) => $v !== '' && $v !== null);
    return url('/admin/users' . ($params ? '?' . http_build_query($params) : ''));
};
$roleBadge = static fn (string $r): string => match ($r) {
    'farmer' => '<span class="badge badge-gold">Farmer</span>',
    'admin' => '<span class="badge badge-neutral">Admin</span>',
    default => '<span class="badge badge-info">Buyer</span>',
};
?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Users &amp; farms</h1>
        <p>Verify farmer storefronts, suspend accounts and keep the market clean.</p>
      </div>
      <span class="pill-note"><?= (int)$page['total'] ?> account<?= (int)$page['total'] === 1 ? '' : 's' ?></span>
    </div>

    <form class="toolbar" method="get" action="<?= e(url('/admin/users')) ?>">
      <input name="q" type="search" value="<?= e($q) ?>" placeholder="Search name, email or phone" aria-label="Search users">
      <select name="role" aria-label="Filter by role">
        <option value="">All roles</option>
        <option value="farmer" <?= $role === 'farmer' ? 'selected' : '' ?>>Farmers</option>
        <option value="buyer" <?= $role === 'buyer' ? 'selected' : '' ?>>Buyers</option>
        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrators</option>
      </select>
      <button class="btn btn-primary btn-sm" type="submit">Filter</button>
      <?php if ($q !== '' || $role !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/users')) ?>">Reset</a><?php endif; ?>
    </form>

    <?php if (!$users): ?>
      <div class="empty"><strong>No accounts match</strong>Adjust the search or clear the filter.</div>
    <?php else: ?>
      <div class="panel" style="padding:0;overflow:hidden">
        <div class="table-wrap" style="border:0">
          <table class="data">
            <thead><tr><th>Account</th><th>Role</th><th>Farm</th><th>Activity</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td>
                  <span class="strong"><?= e($user['name']) ?></span><br>
                  <small class="muted"><?= e($user['email']) ?><?= $user['phone'] ? ' · ' . e($user['phone']) : '' ?></small>
                </td>
                <td><?= $roleBadge($user['role']) ?></td>
                <td>
                  <?php if ($user['farm_name']): ?>
                    <span class="small strong" style="font-weight:600"><?= e($user['farm_name']) ?></span><br>
                    <small class="muted"><?= e($user['farm_state'] ?: 'No state') ?></small>
                  <?php else: ?>
                    <small class="muted">—</small>
                  <?php endif; ?>
                </td>
                <td><small class="muted"><?= (int)$user['listings'] ?> listing<?= (int)$user['listings'] === 1 ? '' : 's' ?><br><?= (int)$user['orders_placed'] ?> order<?= (int)$user['orders_placed'] === 1 ? '' : 's' ?> placed</small></td>
                <td>
                  <?php if ((int)$user['is_active'] === 1): ?>
                    <span class="badge badge-ok">Active</span>
                  <?php else: ?>
                    <span class="badge badge-bad">Suspended</span>
                  <?php endif; ?>
                  <?php if ($user['role'] === 'farmer'): ?>
                    <br><small class="muted"><?= (int)$user['is_verified'] === 1 ? 'Farm verified' : 'Farm unverified' ?></small>
                  <?php endif; ?>
                </td>
                <td><small class="muted"><?= e(short_date($user['created_at'])) ?></small></td>
                <td>
                  <div class="table-actions">
                    <?php if ($user['role'] === 'farmer'): ?>
                      <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/verify')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn <?= (int)$user['is_verified'] === 1 ? 'btn-ghost' : 'btn-soft' ?> btn-sm" type="submit"><?= (int)$user['is_verified'] === 1 ? 'Unverify' : 'Verify farm' ?></button>
                      </form>
                    <?php endif; ?>
                    <?php if ((int)$user['id'] !== (int)Auth::id()): ?>
                      <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/toggle')) ?>" data-confirm="<?= (int)$user['is_active'] === 1 ? 'Suspend this account? They will be signed out immediately.' : 'Reactivate this account?' ?>">
                        <?= csrf_field() ?>
                        <button class="btn <?= (int)$user['is_active'] === 1 ? 'btn-danger' : 'btn-ghost' ?> btn-sm" type="submit"><?= (int)$user['is_active'] === 1 ? 'Suspend' : 'Reactivate' ?></button>
                      </form>
                    <?php else: ?>
                      <span class="small muted">You</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if ($page['pages'] > 1): ?>
        <nav class="pagination" aria-label="User pages">
          <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
            <?php if ($i === $page['current']): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= e($qs(['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head"><h2>Verification standard</h2><span class="badge badge-gold">Operations</span></div>
      <div class="grid grid-3">
        <div><strong class="small" style="display:block">Farm identity</strong><span class="small muted">Farm name matches the farmer's phone and location.</span></div>
        <div><strong class="small" style="display:block">Listing accuracy</strong><span class="small muted">Published quantity and unit price are realistic for the season.</span></div>
        <div><strong class="small" style="display:block">Buyer reports</strong><span class="small muted">No unresolved rejections caused by avoidable stock-outs.</span></div>
      </div>
    </div>
  </div>
</div>
