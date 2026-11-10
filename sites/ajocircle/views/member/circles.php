<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>My circles</h1>
        <p>Every circle you belong to, with your position and running total.</p>
      </div>
      <a class="btn btn-primary btn-sm" href="<?= e(url('/circles')) ?>">Browse directory</a>
    </div>

    <?php if (!$circles): ?>
      <div class="panel">
        <div class="empty"><strong>You have not joined a circle yet</strong>Pick an open circle below and take a position.</div>
      </div>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($circles as $circle): ?>
          <?php
          $target = (float)$circle['contribution_amount'] * (int)$circle['member_limit'];
          $share = $target > 0 ? min(100, (int)round((float)$circle['my_saved'] / $target * 100)) : 0;
          $expectedPayout = (float)$circle['contribution_amount'] * max(1, (int)$circle['member_count']);
          ?>
          <article class="circle-card">
            <div class="circle-top">
              <div>
                <h3><a href="<?= e(url('/member/circles/' . $circle['id'])) ?>" style="color:inherit"><?= e($circle['name']) ?></a></h3>
                <span class="circle-cat"><?= e($circle['category']) ?> · <?= e(frequency_label($circle['frequency'])) ?></span>
              </div>
              <?= pill($circle['status']) ?>
            </div>
            <div class="circle-money">
              <b><?= money((float)$circle['contribution_amount']) ?></b>
              <span>/ <?= e(strtolower(frequency_label($circle['frequency']))) ?></span>
            </div>
            <div>
              <div class="bar-label"><span>Saved <?= money((float)$circle['my_saved']) ?></span><span><?= $share ?>% of your schedule</span></div>
              <div class="bar"><i style="width:<?= $share ?>%"></i></div>
            </div>
            <div class="meta-row">
              <span>Position #<?= (int)$circle['position'] ?></span>
              <span>Payout round <?= (int)$circle['position'] ?></span>
              <span><?= (int)$circle['member_count'] ?> members</span>
              <?php if ((int)$circle['my_overdue'] > 0): ?>
                <span style="background:var(--rose-soft);border-color:#f3cfcb;color:#9c312b"><?= (int)$circle['my_overdue'] ?> overdue</span>
              <?php endif; ?>
            </div>
            <div class="circle-foot">
              <span style="font-size:.78rem;color:var(--muted);font-weight:700">Expected payout: <?= money($expectedPayout) ?></span>
              <a class="btn btn-soft btn-sm" href="<?= e(url('/member/circles/' . $circle['id'])) ?>">Open ledger</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="panel" style="margin-top:22px">
      <div class="panel-head">
        <h2>Circles accepting members</h2>
        <span class="badge badge-neutral"><?= count($open) ?> available</span>
      </div>
      <?php if (!$open): ?>
        <div class="empty"><strong>No open circles right now</strong>Check back soon — organisers open new circles regularly.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Circle</th><th class="right">Contribution</th><th>Frequency</th><th>Members</th><th>Starts</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($open as $circle): ?>
              <?php $full = (int)$circle['member_count'] >= (int)$circle['member_limit']; ?>
              <tr>
                <td><strong><?= e($circle['name']) ?></strong><span class="sub"><?= e($circle['category']) ?></span></td>
                <td class="right num"><?= money((float)$circle['contribution_amount']) ?></td>
                <td><?= e(frequency_label($circle['frequency'])) ?></td>
                <td><?= (int)$circle['member_count'] ?>/<?= (int)$circle['member_limit'] ?></td>
                <td class="nowrap"><?= e(short_date($circle['start_date'])) ?></td>
                <td><?= pill($circle['status']) ?></td>
                <td class="right">
                  <?php if ((int)$circle['is_member'] > 0): ?>
                    <span class="badge badge-ok">Joined</span>
                  <?php elseif ($full): ?>
                    <span class="badge badge-neutral">Full</span>
                  <?php else: ?>
                    <form method="post" action="<?= e(url('/circles/' . $circle['id'] . '/join')) ?>" data-confirm="Join <?= e($circle['name']) ?>?">
                      <?= csrf_field() ?>
                      <button class="btn btn-soft btn-sm" type="submit">Join</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
