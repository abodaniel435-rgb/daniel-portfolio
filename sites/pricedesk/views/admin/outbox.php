<?php
declare(strict_types=1);
/** @var array<int,array<string,mixed>> $rows */
require BASE_PATH . '/views/admin/nav.php';
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Every message the board decided to send</p>
      <h1>Email outbox</h1>
      <p class="lede">Nothing is posted straight to a transport and forgotten. Each message is written to
        MySQL first, then handed to <span class="mono">mail()</span> if
        <span class="mono">MAIL_ENABLED=true</span>, and the row is updated with what happened — so a member
        who says "I never got the alert" can be answered from the database rather than from memory.</p>
    </div>
  </div>

  <?php if ($rows === []): ?>
    <div class="panel">
      <h3>Nothing sent yet</h3>
      <p class="tiny" style="margin:0">Watchers are notified when a price is queued on a line they follow and
        again when it is verified. The weekly sweep adds stale-line nudges, outlier flags and an
      administrator digest.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <caption>The 120 most recent messages, newest first.</caption>
        <thead>
          <tr>
            <th scope="col">Queued</th><th scope="col">To</th><th scope="col">Type</th>
            <th scope="col">Subject</th><th scope="col">Status</th><th scope="col">Sent</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="mono tiny"><?= e(short_datetime((string)$row['created_at'])) ?></td>
              <td class="mono tiny"><?= e((string)$row['recipient']) ?></td>
              <td><span class="badge badge-neutral"><?= e((string)$row['message_type']) ?></span></td>
              <td>
                <strong><?= e((string)$row['subject']) ?></strong>
                <span class="tiny" style="display:block"><?= e(mb_substr(trim((string)$row['body_text']), 0, 130)) ?><?= mb_strlen(trim((string)$row['body_text'])) > 130 ? '…' : '' ?></span>
              </td>
              <td><?= status_badge((string)$row['status']) ?>
                <?php if ((string)$row['status'] === 'failed' && !empty($row['error'])): ?>
                  <span class="tiny" style="display:block;color:var(--danger)"><?= e((string)$row['error']) ?></span>
                <?php endif; ?></td>
              <td class="tiny"><?= empty($row['sent_at']) ? '—' : e(short_datetime((string)$row['sent_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="panel" style="margin-top:26px">
    <h3>Two kinds of news, deliberately kept apart</h3>
    <div class="grid-2">
      <p class="tiny" style="margin:0"><span class="mono">report_submitted</span> says a member filed a price
        on a line you watch. It is queued. Telling watchers a price is live at that moment would be a lie
        about the board, and the whole value of the board is that its numbers have been looked at.</p>
      <p class="tiny" style="margin:0"><span class="mono">report_verified</span> says a reviewer put it on the
        board. That is the message worth acting on, and it is the one the weekly digest counts.</p>
    </div>
    <p class="tiny" style="margin:14px 0 0">On a cPanel host, set
      <span class="mono">MAIL_ENABLED=true</span> and
      <span class="mono">MAIL_FROM</span> to an address on your own domain, and these rows start arriving as
      real mail with no code change. Left false, the outbox is a faithful record of what would have been sent.</p>
  </div>
</section>
