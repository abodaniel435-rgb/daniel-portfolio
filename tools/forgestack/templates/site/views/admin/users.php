<?php $site = site_config(); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/admin_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>People</h1><p>Accounts created through registration, plus the administrator seeded from <code>.env</code>.</p></div>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Records</th><th>Orders</th><th>Last sign-in</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><strong><?= e($user['name']) ?></strong><br><span class="cell-muted"><?= e($user['phone']) ?></span></td>
              <td><?= e($user['email']) ?></td>
              <td><span class="badge badge-neutral"><?= e($user['role'] === 'admin' ? 'Administrator' : ucfirst($site['labels']['customer'])) ?></span></td>
              <td><?= (int)$user['records'] ?></td>
              <td><?= (int)$user['orders_count'] ?></td>
              <td><?= $user['last_login_at'] ? e(short_datetime($user['last_login_at'])) : 'Never' ?></td>
              <td><?= (int)$user['is_active'] === 1 ? '<span class="badge badge-ok">Active</span>' : '<span class="badge badge-bad">Suspended</span>' ?></td>
              <td>
                <?php if ($user['role'] !== 'admin'): ?>
                  <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/toggle')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                    <button class="btn btn-quiet btn-sm" type="submit"><?= (int)$user['is_active'] === 1 ? 'Suspend' : 'Reactivate' ?></button>
                  </form>
                <?php else: ?>
                  <span class="cell-muted">Protected</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
