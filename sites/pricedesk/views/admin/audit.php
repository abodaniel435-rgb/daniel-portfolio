<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $rows */
require BASE_PATH . '/views/admin/nav.php';
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Two hundred most recent entries</p>
      <h1>Audit trail</h1>
      <p class="lede">Every filing, verification, rejection, rate change, account edit and sign-in is written
        here with the actor who did it. A price board without this is a group chat with a login form.</p>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th scope="col">When</th><th scope="col">Action</th><th scope="col">Actor</th>
          <th scope="col">Target</th><th scope="col">Detail</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php $meta = json_decode((string)($row['meta'] ?? ''), true); ?>
          <tr>
            <td class="mono tiny"><?= e(short_datetime((string)$row['created_at'])) ?></td>
            <td><span class="mono"><?= e((string)$row['action']) ?></span></td>
            <td><?= e((string)($row['actor'] ?? 'system')) ?></td>
            <td class="tiny"><?= e((string)$row['target']) ?: '—' ?></td>
            <td class="tiny">
              <?php if (is_array($meta) && $meta !== []): ?>
                <?= e(mb_substr(json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 180)) ?>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
          <tr><td colspan="5" class="muted">Nothing recorded yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="tiny" style="margin-top:16px">The trail is append-only from the application's point of view: no
    screen here edits or deletes a row. Clearing it is an SQL statement, deliberately, so it happens on
    purpose and not by accident.</p>
</section>
