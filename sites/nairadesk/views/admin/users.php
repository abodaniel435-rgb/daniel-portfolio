<?php declare(strict_types=1); ?>
<p class="eyebrow">Administrator</p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)">Accounts</h1>
<p class="lede" style="max-width:64ch"><?= number_format($pager['total']) ?> registered<?= $pager['pages'] > 1 ? ' · page ' . $pager['current'] . ' of ' . $pager['pages'] : '' ?>. Passwords are stored as bcrypt hashes only, so there is nothing on this screen an administrator can read back out.</p>

<div class="table-wrap" style="margin-top:24px">
  <table>
    <thead>
      <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th class="n">Workspaces</th><th>Last seen</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
      <?php $self = (int)$user['id'] === (int)Auth::id(); ?>
      <tr>
        <td class="n"><?= (int)$user['id'] ?></td>
        <td><strong><?= e($user['name']) ?></strong><?= $self ? ' <span style="color:var(--ink-3);font-size:.8rem">(you)</span>' : '' ?></td>
        <td><?= e($user['email']) ?></td>
        <td><code><?= e($user['role']) ?></code></td>
        <td class="n"><?= number_format((int)$user['company_count']) ?></td>
        <td><?= e(short_datetime($user['last_login_at'])) ?></td>
        <td>
          <span class="badge <?= (int)$user['is_active'] === 1 ? 'badge-on' : 'badge-off' ?>">
            <?= (int)$user['is_active'] === 1 ? 'active' : 'disabled' ?>
          </span>
        </td>
        <td class="n">
          <?php if (!$self): ?>
          <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/status')) ?>" class="inlineform">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <button class="btn btn-ghost btn-sm" type="submit"><?= (int)$user['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$users): ?><tr><td colspan="8">No accounts on this page.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pager['pages'] > 1): ?>
<div class="panel" style="margin-top:20px;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap">
  <span style="font-size:.86rem;color:var(--ink-3)">Showing <?= number_format($pager['offset'] + 1) ?>–<?= number_format(min($pager['total'], $pager['offset'] + $pager['limit'])) ?> of <?= number_format($pager['total']) ?></span>
  <span style="display:flex;gap:10px">
    <?php if ($pager['current'] > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/users')) ?>?page=<?= $pager['current'] - 1 ?>">Previous</a><?php endif; ?>
    <?php if ($pager['current'] < $pager['pages']): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/users')) ?>?page=<?= $pager['current'] + 1 ?>">Next</a><?php endif; ?>
  </span>
</div>
<?php endif; ?>
