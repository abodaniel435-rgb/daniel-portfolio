<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Users</h1><p>Patients, doctors and administrators on this installation.</p></div>
    </div>

    <div class="panel">
      <form class="toolbar" method="get" action="<?= e(url('/admin/users')) ?>">
        <input name="q" value="<?= e($q) ?>" placeholder="Search name or email">
        <select name="role">
          <option value="">All roles</option>
          <?php foreach (['patient', 'doctor', 'admin'] as $option): ?>
            <option value="<?= e($option) ?>" <?= $role === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Filter</button>
        <?php if ($q !== '' || $role !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/users')) ?>">Reset</a><?php endif; ?>
        <span style="margin-left:auto;font-size:.82rem;color:var(--muted)"><?= (int)$page['total'] ?> user<?= $page['total'] === 1 ? '' : 's' ?></span>
      </form>

      <?php if (!$users): ?>
        <div class="empty"><strong>No users match this filter</strong>Try a different search.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last sign-in</th><th>Joined</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?= e($user['name']) ?></td>
                <td><?= e($user['email']) ?></td>
                <td><span class="badge badge-neutral"><?= e($user['role']) ?></span></td>
                <td><?= $user['is_active'] ? '<span class="badge badge-ok">Active</span>' : '<span class="badge badge-bad">Disabled</span>' ?></td>
                <td><small><?= e($user['last_login_at'] ? short_datetime($user['last_login_at']) : 'Never') ?></small></td>
                <td><small><?= e(short_date($user['created_at'])) ?></small></td>
                <td>
                  <?php if ((int)$user['id'] !== Auth::id()): ?>
                    <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/toggle')) ?>" data-confirm="<?= $user['is_active'] ? 'Deactivate' : 'Reactivate' ?> this account?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                      <button class="btn btn-ghost btn-sm" type="submit"><?= $user['is_active'] ? 'Deactivate' : 'Reactivate' ?></button>
                    </form>
                  <?php else: ?>
                    <span class="badge badge-info">You</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($page['pages'] > 1): ?>
          <div class="pagination">
            <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
              <?php if ($i === $page['current']): ?><span class="current"><?= $i ?></span>
              <?php else: ?><a href="<?= e(url('/admin/users?page=' . $i . '&role=' . urlencode($role) . '&q=' . urlencode($q))) ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
