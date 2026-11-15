<?php declare(strict_types=1); ?>
<p class="eyebrow">Administrator</p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)">Audit trail</h1>
<p class="lede" style="max-width:64ch">Every write the application performed: who did it, what it touched, and the payload that mattered. The newest 200 are shown.</p>

<div class="table-wrap" style="margin-top:24px">
  <table>
    <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Target</th><th>Detail</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php $meta = json_decode((string)($row['meta'] ?? ''), true); ?>
      <tr>
        <td style="white-space:nowrap"><?= e(short_datetime($row['created_at'])) ?></td>
        <td><?= e($row['actor'] ?? '') !== '' ? e($row['actor']) : '<span style="color:var(--ink-3)">system</span>' ?></td>
        <td><code><?= e($row['action']) ?></code></td>
        <td><?= e($row['target']) ?></td>
        <td style="font-size:.84rem;color:var(--ink-3)">
          <?php if (is_array($meta) && $meta !== []): ?>
            <?php foreach ($meta as $key => $val): ?>
              <span style="white-space:nowrap"><strong><?= e((string)$key) ?></strong> <?= e(is_scalar($val) ? (string)$val : json_encode($val)) ?></span><br>
            <?php endforeach; ?>
          <?php else: ?>
            <span style="color:var(--ink-3)">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5">Nothing has been written yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<div class="panel" style="margin-top:22px">
  <h3>What is deliberately missing</h3>
  <p style="font-size:.9rem;color:var(--ink-2)">No password, no hash and no session identifier is ever recorded here. Sign-in failures live in <code>login_attempts</code>, which the throttle reads and which holds no credential at all — an email and an IP are enough to count a burst of guesses, and anything more would be a liability.</p>
</div>
