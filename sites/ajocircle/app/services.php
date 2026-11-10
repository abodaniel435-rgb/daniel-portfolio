<?php
declare(strict_types=1);

/**
 * AjoCircle domain services: circles, rotating schedules, contributions,
 * payouts and the notification emails that keep every member informed.
 */

function ajo_frequencies(): array
{
    return ['weekly' => 'Weekly', 'monthly' => 'Monthly'];
}

function ajo_categories(): array
{
    return ['Community', 'Market association', 'Church group', 'Cooperative', 'Friends & family', 'Workplace'];
}

/** Due date of a round: start date + (round - 1) x frequency. */
function round_due_date(string $startDate, string $frequency, int $round): string
{
    $offset = max(0, $round - 1);
    $base = new DateTimeImmutable($startDate);
    $modifier = $frequency === 'weekly' ? "+{$offset} weeks" : "+{$offset} months";
    return $base->modify($modifier)->format('Y-m-d');
}

/** First round still open today (at least 1). */
function circle_current_round(array $circle): int
{
    $today = date('Y-m-d');
    $current = 1;
    for ($round = 1; $round <= (int)$circle['member_limit']; $round++) {
        if (round_due_date($circle['start_date'], $circle['frequency'], $round) <= $today) {
            $current = $round;
        } else {
            break;
        }
    }
    return $current;
}

/** Ordered membership list with the member's account details. */
function circle_members(int $circleId, bool $activeOnly = true): array
{
    $sql = 'SELECT m.*, u.name, u.email, u.phone
            FROM memberships m JOIN users u ON u.id = m.user_id
            WHERE m.circle_id = ?';
    if ($activeOnly) {
        $sql .= " AND m.status = 'active'";
    }
    $sql .= ' ORDER BY m.position ASC';
    return Db::all($sql, [$circleId]);
}

function circle_member_count(int $circleId): int
{
    return (int)Db::value("SELECT COUNT(*) FROM memberships WHERE circle_id = ? AND status = 'active'", [$circleId]);
}

function circle_has_member(int $circleId, int $userId): ?array
{
    return Db::one('SELECT * FROM memberships WHERE circle_id = ? AND user_id = ? LIMIT 1', [$circleId, $userId]);
}

/**
 * The full round-by-round ledger for a circle: recipient, pool target,
 * collected total, completion state and any payout record.
 */
function circle_rounds(array $circle): array
{
    $circleId = (int)$circle['id'];
    $limit = (int)$circle['member_limit'];
    $amount = (float)$circle['contribution_amount'];

    $members = [];
    foreach (circle_members($circleId) as $member) {
        $members[(int)$member['position']] = $member;
    }
    $memberCount = count($members);

    $collected = [];
    foreach (Db::all(
        "SELECT round_no,
                COUNT(*) AS rows_total,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS paid_amount,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count
         FROM contributions WHERE circle_id = ? GROUP BY round_no",
        [$circleId]
    ) as $row) {
        $collected[(int)$row['round_no']] = $row;
    }

    $payouts = [];
    foreach (Db::all('SELECT * FROM payouts WHERE circle_id = ? ORDER BY round_no', [$circleId]) as $payout) {
        $payouts[(int)$payout['round_no']] = $payout;
    }

    $today = date('Y-m-d');
    $rounds = [];
    for ($round = 1; $round <= $limit; $round++) {
        $due = round_due_date($circle['start_date'], $circle['frequency'], $round);
        $stats = $collected[$round] ?? null;
        $paidCount = $stats ? (int)$stats['paid_count'] : 0;
        $paidAmount = $stats ? (float)$stats['paid_amount'] : 0.0;
        $expected = $amount * $memberCount;
        $payout = $payouts[$round] ?? null;

        if ($payout && $payout['status'] === 'paid') {
            $state = 'paid_out';
        } elseif ($memberCount > 0 && $paidCount >= $memberCount) {
            $state = 'collected';
        } elseif ($due < $today) {
            $state = 'overdue';
        } elseif ($due === $today) {
            $state = 'due';
        } else {
            $state = 'upcoming';
        }

        $rounds[] = [
            'round_no' => $round,
            'due_date' => $due,
            'recipient' => $members[$round] ?? null,
            'member_count' => $memberCount,
            'expected' => $expected,
            'paid_count' => $paidCount,
            'paid_amount' => $paidAmount,
            'late_count' => $stats ? (int)$stats['late_count'] : 0,
            'payout' => $payout,
            'state' => $state,
            'percent' => $memberCount > 0 ? min(100, (int)round($paidCount / $memberCount * 100)) : 0,
        ];
    }
    return $rounds;
}

function circle_totals(int $circleId): array
{
    $row = Db::one(
        "SELECT
            COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS paid,
            COALESCE(SUM(CASE WHEN status IN ('pending','late') THEN amount ELSE 0 END), 0) AS outstanding,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count
         FROM contributions WHERE circle_id = ?",
        [$circleId]
    );
    $payouts = Db::one(
        "SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS paid,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled
         FROM payouts WHERE circle_id = ?",
        [$circleId]
    );
    return [
        'paid' => (float)($row['paid'] ?? 0),
        'outstanding' => (float)($row['outstanding'] ?? 0),
        'late_count' => (int)($row['late_count'] ?? 0),
        'paid_out' => (float)($payouts['paid'] ?? 0),
        'scheduled_payouts' => (int)($payouts['scheduled'] ?? 0),
    ];
}

/** Adds a member at the next free position and builds their contribution schedule. */
function join_circle(array $circle, int $userId): int
{
    if ($circle['status'] !== 'open' && $circle['status'] !== 'active') {
        throw new RuntimeException('This circle is not accepting new members.');
    }
    if (circle_has_member((int)$circle['id'], $userId)) {
        throw new RuntimeException('You are already a member of this circle.');
    }

    return Db::transaction(function () use ($circle, $userId): int {
        $circleId = (int)$circle['id'];
        $locked = Db::one('SELECT * FROM circles WHERE id = ? FOR UPDATE', [$circleId]);
        if (!$locked) {
            throw new RuntimeException('This circle no longer exists.');
        }
        $count = (int)Db::value("SELECT COUNT(*) FROM memberships WHERE circle_id = ? AND status = 'active'", [$circleId]);
        if ($count >= (int)$locked['member_limit']) {
            throw new RuntimeException('This circle is full — all ' . (int)$locked['member_limit'] . ' slots are taken.');
        }
        $position = (int)Db::value('SELECT COALESCE(MAX(position), 0) + 1 FROM memberships WHERE circle_id = ?', [$circleId]);
        if ($position > (int)$locked['member_limit']) {
            throw new RuntimeException('This circle has no free payout positions left.');
        }

        $membershipId = Db::insert('memberships', [
            'circle_id' => $circleId,
            'user_id' => $userId,
            'position' => $position,
            'status' => 'active',
            'joined_at' => date('Y-m-d H:i:s'),
        ]);

        // Open circles start from round one. A member joining a running circle
        // picks up the schedule from the current round onwards.
        $fromRound = $locked['status'] === 'active' ? circle_current_round($locked) : 1;
        create_contribution_schedule($locked, $membershipId, $userId, $fromRound);

        return $membershipId;
    });
}

function create_contribution_schedule(array $circle, int $membershipId, int $userId, int $fromRound = 1): int
{
    $created = 0;
    for ($round = max(1, $fromRound); $round <= (int)$circle['member_limit']; $round++) {
        $exists = Db::value(
            'SELECT COUNT(*) FROM contributions WHERE circle_id = ? AND membership_id = ? AND round_no = ?',
            [(int)$circle['id'], $membershipId, $round]
        );
        if ($exists) {
            continue;
        }
        Db::insert('contributions', [
            'circle_id' => (int)$circle['id'],
            'membership_id' => $membershipId,
            'user_id' => $userId,
            'round_no' => $round,
            'due_date' => round_due_date($circle['start_date'], $circle['frequency'], $round),
            'amount' => (float)$circle['contribution_amount'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $created++;
    }
    return $created;
}

function member_circles(int $userId): array
{
    return Db::all(
        "SELECT c.*, m.position, m.joined_at, m.status AS membership_status,
                (SELECT COUNT(*) FROM memberships mm WHERE mm.circle_id = c.id AND mm.status = 'active') AS member_count,
                (SELECT COALESCE(SUM(ct.amount), 0) FROM contributions ct
                   WHERE ct.circle_id = c.id AND ct.user_id = ? AND ct.status = 'paid') AS my_saved,
                (SELECT COUNT(*) FROM contributions ct
                   WHERE ct.circle_id = c.id AND ct.user_id = ? AND ct.status IN ('pending','late')
                   AND ct.due_date < CURDATE()) AS my_overdue
         FROM memberships m JOIN circles c ON c.id = m.circle_id
         WHERE m.user_id = ? AND m.status = 'active'
         ORDER BY FIELD(c.status, 'active', 'open', 'completed'), c.start_date DESC",
        [$userId, $userId, $userId]
    );
}

function member_contributions(int $userId, ?int $circleId = null, int $limit = 250): array
{
    $sql = 'SELECT ct.*, c.name AS circle_name, c.frequency
            FROM contributions ct JOIN circles c ON c.id = ct.circle_id
            WHERE ct.user_id = ?';
    $params = [$userId];
    if ($circleId !== null) {
        $sql .= ' AND ct.circle_id = ?';
        $params[] = $circleId;
    }
    $sql .= ' ORDER BY ct.due_date DESC, ct.round_no DESC LIMIT ' . max(1, $limit);
    return Db::all($sql, $params);
}

function member_next_due(int $userId): ?array
{
    return Db::one(
        "SELECT ct.*, c.name AS circle_name, c.frequency, c.slug
         FROM contributions ct JOIN circles c ON c.id = ct.circle_id
         WHERE ct.user_id = ? AND ct.status IN ('pending','late')
         ORDER BY ct.due_date ASC, ct.round_no ASC LIMIT 1",
        [$userId]
    );
}

function member_totals(int $userId): array
{
    $row = Db::one(
        "SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS saved,
                COALESCE(SUM(CASE WHEN status IN ('pending','late') THEN amount ELSE 0 END), 0) AS outstanding,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count
         FROM contributions WHERE user_id = ?",
        [$userId]
    );
    $received = (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE recipient_user_id = ? AND status = 'paid'", [$userId]);
    $scheduled = (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE recipient_user_id = ? AND status = 'scheduled'", [$userId]);
    $circleCount = (int)Db::value("SELECT COUNT(*) FROM memberships WHERE user_id = ? AND status = 'active'", [$userId]);

    return [
        'saved' => (float)($row['saved'] ?? 0),
        'outstanding' => (float)($row['outstanding'] ?? 0),
        'late_count' => (int)($row['late_count'] ?? 0),
        'paid_count' => (int)($row['paid_count'] ?? 0),
        'received' => $received,
        'scheduled' => $scheduled,
        'circles' => $circleCount,
    ];
}

/** Expected payout date for a member inside a circle (their position = their round). */
function member_payout_position(array $circle, array $membership): array
{
    $position = (int)$membership['position'];
    $due = round_due_date($circle['start_date'], $circle['frequency'], $position);
    $payout = Db::one('SELECT * FROM payouts WHERE circle_id = ? AND round_no = ? LIMIT 1', [(int)$circle['id'], $position]);
    $memberCount = circle_member_count((int)$circle['id']);
    return [
        'position' => $position,
        'expected_date' => $due,
        'amount' => (float)$circle['contribution_amount'] * max(1, $memberCount),
        'payout' => $payout,
    ];
}

function can_manage_circle(array $circle): bool
{
    if (!Auth::check()) {
        return false;
    }
    return Auth::role() === 'admin' || (int)($circle['owner_id'] ?? 0) === Auth::id();
}

function admin_emails(): array
{
    return array_column(Db::all("SELECT email FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id"), 'email');
}

/** Status pill that also understands 'late'. */
function pill(string $status): string
{
    $map = [
        'open' => 'info', 'active' => 'ok', 'completed' => 'dark',
        'pending' => 'warn', 'paid' => 'ok', 'late' => 'bad',
        'scheduled' => 'info', 'cancelled' => 'bad',
        'collected' => 'ok', 'paid_out' => 'ok', 'overdue' => 'bad',
        'due' => 'warn', 'upcoming' => 'neutral', 'skipped' => 'neutral',
    ];
    $tone = $map[$status] ?? 'neutral';
    $label = $status === 'paid_out' ? 'paid out' : str_replace('_', ' ', $status);
    return '<span class="badge badge-' . e($tone) . '">' . e(ucfirst($label)) . '</span>';
}

function frequency_label(string $frequency): string
{
    return ajo_frequencies()[$frequency] ?? ucfirst($frequency);
}

function slugify(string $value): string
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? '', '-'));
    return $slug !== '' ? $slug : 'circle';
}

function unique_circle_slug(string $name, ?int $ignoreId = null): string
{
    $base = slugify($name);
    $slug = $base;
    $suffix = 2;
    while (true) {
        $sql = 'SELECT COUNT(*) FROM circles WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        if (!(int)Db::value($sql, $params)) {
            return $slug;
        }
        $slug = $base . '-' . $suffix++;
    }
}

/* ------------------------------------------------------------------ *
 * Notification emails (always queued in email_outbox, never blocking)
 * ------------------------------------------------------------------ */

function circle_context_line(array $circle): string
{
    return '<strong>' . e($circle['name']) . '</strong><br>'
        . money((float)$circle['contribution_amount']) . ' · ' . e(frequency_label($circle['frequency']))
        . ' · ' . (int)$circle['member_limit'] . ' members';
}

function notify_welcome(array $user): void
{
    $body = '<p>Hello ' . e($user['name']) . ',</p>'
        . '<p>Welcome to AjoCircle. Your account is ready — join a circle, record your contributions '
        . 'and follow every payout from one transparent ledger.</p>'
        . '<p>What happens next:</p><ul>'
        . '<li>Browse open circles and request a slot.</li>'
        . '<li>Record each contribution with its payment reference.</li>'
        . '<li>Your organiser verifies it, and the ledger updates for everyone.</li></ul>';
    Mailer::send($user['email'], 'Welcome to AjoCircle', Mailer::template(
        'Your circle ledger starts here',
        $body,
        'Browse open circles',
        url('/circles')
    ), '', 'welcome');
}

function notify_join(array $circle, array $user, int $position): void
{
    $due = round_due_date($circle['start_date'], $circle['frequency'], 1);
    $body = '<p>Hello ' . e($user['name']) . ',</p>'
        . '<p>You are now member <strong>#' . $position . '</strong> of ' . e($circle['name']) . '.</p>'
        . '<p>' . circle_context_line($circle) . '</p>'
        . '<p>Your payout position is <strong>' . $position . '</strong>, so your turn in the rotation is round '
        . $position . '. First contribution due <strong>' . e(short_date($due)) . '</strong>.</p>';
    Mailer::send($user['email'], 'You joined ' . $circle['name'], Mailer::template(
        'Welcome to the circle',
        $body,
        'Open my dashboard',
        url('/dashboard')
    ), '', 'join_confirmation');
}

function notify_contribution_recorded(array $contribution, array $circle): void
{
    $body = '<p>Hello ' . e($contribution['name'] ?? 'there') . ',</p>'
        . '<p>We recorded your contribution of <strong>' . money((float)$contribution['amount']) . '</strong> for '
        . e($circle['name']) . ' (round ' . (int)$contribution['round_no'] . ').</p>'
        . '<p><strong>Reference:</strong> ' . e((string)$contribution['payment_reference']) . '<br>'
        . '<strong>Due date:</strong> ' . e(short_date($contribution['due_date'])) . '<br>'
        . '<strong>Status:</strong> awaiting organiser verification</p>'
        . '<p>Your ledger entry is visible to every member of the circle.</p>';
    Mailer::send($contribution['email'], 'Contribution recorded — ' . $circle['name'], Mailer::template(
        'Payment recorded',
        $body,
        'View my contributions',
        url('/member/contributions')
    ), '', 'contribution_receipt');
}

function notify_contribution_verified(array $contribution, array $circle, bool $late): void
{
    $heading = $late ? 'Contribution marked late' : 'Contribution verified';
    $line = $late
        ? 'Your contribution for round ' . (int)$contribution['round_no'] . ' has been marked late because it was not confirmed by the due date. Please contact your organiser.'
        : 'Your contribution for round ' . (int)$contribution['round_no'] . ' is verified and now counts towards the pool.';
    $body = '<p>Hello ' . e($contribution['name']) . ',</p><p>' . e($line) . '</p>'
        . '<p>' . circle_context_line($circle) . '</p>'
        . '<p><strong>Amount:</strong> ' . money((float)$contribution['amount']) . '<br>'
        . '<strong>Due date:</strong> ' . e(short_date($contribution['due_date'])) . '</p>';
    Mailer::send($contribution['email'], $heading . ' — ' . $circle['name'], Mailer::template(
        $heading,
        $body,
        'View my ledger',
        url('/member/contributions')
    ), '', $late ? 'contribution_late' : 'contribution_verified');
}

function notify_payout_scheduled(array $payout, array $circle, array $recipient): void
{
    $body = '<p>Hello ' . e($recipient['name']) . ',</p>'
        . '<p>Your payout for ' . e($circle['name']) . ' is scheduled.</p>'
        . '<p><strong>Round:</strong> ' . (int)$payout['round_no'] . '<br>'
        . '<strong>Amount:</strong> ' . money((float)$payout['amount']) . '<br>'
        . '<strong>Scheduled for:</strong> ' . e(short_date($payout['scheduled_date'])) . '<br>'
        . '<strong>Reference:</strong> ' . e($payout['reference']) . '</p>';
    Mailer::send($recipient['email'], 'Payout scheduled — ' . $circle['name'], Mailer::template(
        'Your payout is scheduled',
        $body,
        'Open my dashboard',
        url('/dashboard')
    ), '', 'payout_scheduled');
}

function notify_payout_paid(array $payout, array $circle, array $recipient): void
{
    $body = '<p>Hello ' . e($recipient['name']) . ',</p>'
        . '<p>Your payout of <strong>' . money((float)$payout['amount']) . '</strong> for '
        . e($circle['name']) . ' has been marked paid.</p>'
        . '<p><strong>Round:</strong> ' . (int)$payout['round_no'] . '<br>'
        . '<strong>Paid on:</strong> ' . e(short_date($payout['paid_on'] ?? date('Y-m-d'))) . '<br>'
        . '<strong>Reference:</strong> ' . e($payout['reference']) . '</p>';
    Mailer::send($recipient['email'], 'Payout paid — ' . $circle['name'], Mailer::template(
        'Payout completed',
        $body,
        'Download my statement',
        url('/member/statement')
    ), '', 'payout_paid');
}

function notify_admin(string $subject, string $heading, string $bodyHtml, string $type, string $ctaLabel = '', string $ctaPath = '/admin'): int
{
    $queued = 0;
    $ctaUrl = $ctaPath !== '' ? url($ctaPath) : '';
    foreach (admin_emails() as $email) {
        Mailer::send($email, $subject, Mailer::template($heading, $bodyHtml, $ctaLabel, $ctaUrl), '', $type);
        $queued++;
    }
    return $queued;
}
