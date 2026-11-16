<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $users @var array<string,int> $pager */
require BASE_PATH . '/views/admin/nav.php';
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Page <?= (int)$pager['current'] ?> of <?= (int)$pager['pages'] ?> · <?= (int)$pager['total'] ?> accounts</p>
      <h1>Accounts</h1>
      <p class="lede">Who can file, who can verify, and who has stopped doing either. Passwords are not on
        this page because they are not stored in a form that could be shown here — only a bcrypt hash is,
        and it is never selected into a view.</p>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th scope="col">#</th><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Phone</th>
          <th scope="col">Role</th><th scope="col" class="n">Reports filed</th>
          <th scope="col">Last seen</th><th scope="col"><span class="visually-hidden">Status</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td class="mono"><?= (int)$user['id'] ?></td>
            <td><strong><?= e((string)$user['name']) ?></strong></td>
            <td class="mono tiny"><?= e((string)$user['email']) ?></td>
            <td class="tiny"><?= e((string)$user['phone']) ?: '—' ?></td>
            <td><?= status_badge((string)$user['role'] === 'admin' ? 'approved' : 'active') ?>
              <span class="tiny" style="display:block"><?= e((string)$user['role']) ?></span></td>
            <td class="n"><?= (int)$user['report_count'] ?></td>
            <td class="tiny"><?= empty($user['last_login_at']) ? 'never signed in' : e(short_datetime((string)$user['last_login_at'])) ?></td>
            <td class="n">
              <?php $active = (int)$user['is_active'] === 1; ?>
              <form method="post" action="<?= e(url('/admin/users/' . (int)$user['id'] . '/status')) ?>"
                    onsubmit="return confirm('<?= $active ? 'Disable' : 'Re-enable' ?> this account?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                <button class="btn btn-sm <?= $active ? 'btn-bad' : 'btn-ghost' ?>" type="submit">
                  <?= $active ? 'Disable' : 'Re-enable' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($users === []): ?>
          <tr><td colspan="8" class="muted">No accounts on this page.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ((int)$pager['pages'] > 1): ?>
    <p class="legend" style="margin-top:16px">
      <?php for ($page = 1; $page <= (int)$pager['pages']; $page++): ?>
        <a class="btn btn-sm <?= $page === (int)$pager['current'] ? 'btn-primary' : 'btn-ghost' ?>"
           href="<?= e(url('/admin/users')) ?>?page=<?= $page ?>"><?= $page ?></a>
      <?php endfor; ?>
    </p>
  <?php endif; ?>

  <div class="panel" style="margin-top:26px">
    <h3>Disabling is not deleting</h3>
    <p class="tiny" style="margin:0">A disabled account cannot sign in and its quotes stay on the board with
      its name attached — the record of who said what is the point of the board, and it survives the person.
      Your own account cannot be disabled from this screen, so a console can never be locked out by its own
      last administrator.</p>
  </div>
</section>
