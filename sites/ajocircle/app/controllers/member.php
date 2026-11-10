<?php
declare(strict_types=1);

function member_guard(): array
{
    Auth::requireLogin();
    return Auth::user();
}

function member_dashboard(): void
{
    $user = member_guard();
    if (Auth::role() === 'admin') {
        redirect('/admin');
    }
    $userId = (int)$user['id'];

    $circles = member_circles($userId);
    $totals = member_totals($userId);
    $nextDue = member_next_due($userId);
    $recent = member_contributions($userId, null, 6);

    $payouts = Db::all(
        "SELECT p.*, c.name AS circle_name
         FROM payouts p JOIN circles c ON c.id = p.circle_id
         WHERE p.recipient_user_id = ?
         ORDER BY FIELD(p.status, 'scheduled', 'paid'), p.scheduled_date ASC LIMIT 6",
        [$userId]
    );

    $nextPayout = Db::one(
        "SELECT p.*, c.name AS circle_name FROM payouts p JOIN circles c ON c.id = p.circle_id
         WHERE p.recipient_user_id = ? AND p.status = 'scheduled'
         ORDER BY p.scheduled_date ASC LIMIT 1",
        [$userId]
    );

    // Upcoming turns: memberships whose payout round has no payout record yet.
    $turns = [];
    foreach ($circles as $circle) {
        if ($circle['status'] === 'completed') {
            continue;
        }
        $position = member_payout_position($circle, ['position' => $circle['position'], 'circle_id' => $circle['id']]);
        if (!$position['payout']) {
            $turns[] = $circle + ['turn' => $position];
        }
    }

    view('member/dashboard', [
        'title' => 'My dashboard — AjoCircle',
        'user' => $user,
        'circles' => $circles,
        'totals' => $totals,
        'nextDue' => $nextDue,
        'recent' => $recent,
        'payouts' => $payouts,
        'nextPayout' => $nextPayout,
        'turns' => $turns,
        'monthSaved' => (float)Db::value(
            "SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE user_id = ? AND status = 'paid' AND paid_on >= ?",
            [$userId, date('Y-m-01')]
        ),
    ]);
}

function member_circles_page(): void
{
    $user = member_guard();
    $userId = (int)$user['id'];
    $open = Db::all(
        "SELECT c.*,
                (SELECT COUNT(*) FROM memberships m WHERE m.circle_id = c.id AND m.status = 'active') AS member_count,
                (SELECT COUNT(*) FROM memberships m WHERE m.circle_id = c.id AND m.user_id = ?) AS is_member
         FROM circles c WHERE c.status IN ('open','active')
         ORDER BY FIELD(c.status, 'open', 'active'), c.start_date ASC",
        [$userId]
    );

    view('member/circles', [
        'title' => 'My circles — AjoCircle',
        'circles' => member_circles($userId),
        'open' => $open,
    ]);
}

function member_circle_show(): void
{
    $user = member_guard();
    $id = (int)($_GET['id'] ?? 0);
    $circle = Db::one('SELECT * FROM circles WHERE id = ? LIMIT 1', [$id]);
    if (!$circle) {
        http_response_code(404);
        view('errors/404', ['title' => 'Circle not found']);
        return;
    }
    $membership = circle_has_member($id, (int)$user['id']);
    if (!$membership) {
        flash('error', 'Join this circle to see your personal ledger.');
        redirect('/circles/' . $id);
    }

    view('member/circle', [
        'title' => $circle['name'] . ' — AjoCircle',
        'circle' => $circle,
        'membership' => $membership,
        'members' => circle_members($id),
        'rounds' => circle_rounds($circle),
        'totals' => circle_totals($id),
        'position' => member_payout_position($circle, $membership),
        'myContributions' => Db::all(
            'SELECT * FROM contributions WHERE circle_id = ? AND user_id = ? ORDER BY round_no ASC',
            [$id, (int)$user['id']]
        ),
    ]);
}

function member_join(): void
{
    verify_csrf();
    $user = member_guard();
    $id = (int)($_POST['id'] ?? 0);
    $circle = Db::one('SELECT * FROM circles WHERE id = ? LIMIT 1', [$id]);
    if (!$circle) {
        flash('error', 'That circle could not be found.');
        redirect('/circles');
    }

    try {
        join_circle($circle, (int)$user['id']);
        $membership = circle_has_member($id, (int)$user['id']);
        $position = $membership ? (int)$membership['position'] : 0;
        audit('circle.joined', 'Circle #' . $id . ' position ' . $position);
        try {
            notify_join($circle, $user, $position);
            notify_admin(
                'New member in ' . $circle['name'],
                'A new member joined',
                '<p><strong>' . e($user['name']) . '</strong> (' . e($user['email']) . ') joined '
                . e($circle['name']) . ' as position <strong>#' . $position . '</strong>.</p>'
                . '<p>Their contribution schedule has been generated for rounds ' . $position . ' onwards.</p>',
                'join_admin',
                'Review circle',
                '/admin/circles/' . $id
            );
        } catch (Throwable) {
            // Notification failures never undo a successful join.
        }
        flash('success', 'You joined ' . $circle['name'] . ' at position #' . $position . '. Your contribution schedule is ready.');
        redirect('/member/circles/' . $id);
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('/circles/' . $id);
    }
}

function member_contributions_page(): void
{
    $user = member_guard();
    $userId = (int)$user['id'];
    $circleId = (int)($_GET['circle_id'] ?? 0) ?: null;
    $status = trim((string)($_GET['status'] ?? ''));

    $sql = 'SELECT ct.*, c.name AS circle_name, c.frequency
            FROM contributions ct JOIN circles c ON c.id = ct.circle_id
            WHERE ct.user_id = ?';
    $params = [$userId];
    if ($circleId !== null) {
        $sql .= ' AND ct.circle_id = ?';
        $params[] = $circleId;
    }
    if (in_array($status, ['pending', 'paid', 'late'], true)) {
        $sql .= ' AND ct.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY ct.due_date DESC, ct.id DESC LIMIT 250';

    view('member/contributions', [
        'title' => 'My contributions — AjoCircle',
        'contributions' => Db::all($sql, $params),
        'circles' => member_circles($userId),
        'totals' => member_totals($userId),
        'circleId' => $circleId,
        'status' => $status,
        'preselect' => (int)($_GET['pay'] ?? 0),
        'outstanding' => Db::all(
            "SELECT ct.id, ct.round_no, ct.due_date, ct.amount, ct.status, c.name AS circle_name
             FROM contributions ct JOIN circles c ON c.id = ct.circle_id
             WHERE ct.user_id = ? AND ct.status <> 'paid'
               AND (ct.payment_reference IS NULL OR ct.payment_reference = '')
             ORDER BY ct.due_date ASC LIMIT 60",
            [$userId]
        ),
    ]);
}

function member_contribution_pay(): void
{
    verify_csrf();
    $user = member_guard();
    $id = (int)($_POST['id'] ?? 0);
    $row = Db::one(
        'SELECT ct.*, c.name AS circle_name, c.frequency FROM contributions ct
         JOIN circles c ON c.id = ct.circle_id
         WHERE ct.id = ? AND ct.user_id = ? LIMIT 1',
        [$id, (int)$user['id']]
    );
    if (!$row) {
        flash('error', 'That contribution could not be found on your ledger.');
        redirect('/member/contributions');
    }
    if ($row['status'] === 'paid') {
        flash('error', 'This contribution is already verified as paid.');
        redirect('/member/contributions');
    }
    if (!empty($row['payment_reference'])) {
        flash('error', 'A payment is already recorded for round ' . (int)$row['round_no'] . '. Your organiser will verify it.');
        redirect('/member/contributions');
    }

    $reference = strtoupper(trim((string)($_POST['payment_reference'] ?? '')));
    $method = (string)($_POST['method'] ?? 'bank_transfer');
    $paidOn = trim((string)($_POST['paid_on'] ?? date('Y-m-d')));

    if ($paidOn > date('Y-m-d')) {
        flash('error', 'The date paid cannot be in the future.');
        redirect('/member/contributions#pay');
    }

    $v = (new Validator(['payment_reference' => $reference, 'paid_on' => $paidOn, 'method' => $method]))
        ->required('payment_reference')
        ->min('payment_reference', 4)
        ->max('payment_reference', 80)
        ->date('paid_on')
        ->in('method', ['bank_transfer', 'cash', 'mobile_money']);

    if (!$v->passes()) {
        foreach ($v->errors() as $message) {
            flash('error', $message);
        }
        redirect('/member/contributions');
    }

    $updated = Db::run(
        "UPDATE contributions
         SET payment_reference = ?, method = ?, paid_on = ?, submitted_at = NOW(),
             note = ?, status = CASE WHEN status = 'late' THEN 'late' ELSE 'pending' END
         WHERE id = ? AND user_id = ? AND (payment_reference IS NULL OR payment_reference = '')",
        [
            $reference,
            $method,
            $paidOn,
            mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255),
            $id,
            (int)$user['id'],
        ]
    )->rowCount();

    if ($updated === 0) {
        flash('error', 'That round already has a payment recorded. Duplicate payments are blocked.');
        redirect('/member/contributions');
    }

    audit('contribution.recorded', 'Contribution #' . $id . ' ref ' . $reference);
    $fresh = Db::one('SELECT * FROM contributions WHERE id = ?', [$id]);
    $circle = Db::one('SELECT * FROM circles WHERE id = ?', [(int)$row['circle_id']]);
    try {
        notify_contribution_recorded($fresh + ['name' => $user['name'], 'email' => $user['email']], $circle);
        notify_admin(
            'Contribution recorded — ' . $circle['name'],
            'A contribution needs verification',
            '<p><strong>' . e($user['name']) . '</strong> recorded ' . money((float)$row['amount'])
            . ' for round ' . (int)$row['round_no'] . ' of ' . e($circle['name']) . '.</p>'
            . '<p><strong>Reference:</strong> ' . e($reference) . '<br><strong>Method:</strong> '
            . e(str_replace('_', ' ', $method)) . '</p>',
            'contribution_admin',
            'Verify contribution',
            '/admin/contributions?status=pending'
        );
    } catch (Throwable) {
        // Keep the ledger write even if the mail queue fails.
    }

    flash('success', 'Payment recorded for round ' . (int)$row['round_no'] . '. Your organiser will verify it shortly.');
    redirect('/member/contributions');
}

function member_statement_page(): void
{
    $user = member_guard();
    $userId = (int)$user['id'];
    $circleId = (int)($_GET['circle_id'] ?? 0) ?: null;

    $circles = member_circles($userId);
    $rows = member_contributions($userId, $circleId, 500);
    $paid = array_filter($rows, static fn (array $r): bool => $r['status'] === 'paid');
    $outstanding = array_filter($rows, static fn (array $r): bool => $r['status'] !== 'paid');

    $payouts = Db::all(
        "SELECT p.*, c.name AS circle_name FROM payouts p JOIN circles c ON c.id = p.circle_id
         WHERE p.recipient_user_id = ? ORDER BY p.created_at DESC",
        [$userId]
    );

    view('member/statement', [
        'title' => 'Statement — AjoCircle',
        'user' => $user,
        'circles' => $circles,
        'rows' => $rows,
        'paidTotal' => array_sum(array_map(static fn (array $r): float => (float)$r['amount'], $paid)),
        'outstandingTotal' => array_sum(array_map(static fn (array $r): float => (float)$r['amount'], $outstanding)),
        'payouts' => $payouts,
        'payoutTotal' => array_sum(array_map(static fn (array $r): float => $r['status'] === 'paid' ? (float)$r['amount'] : 0, $payouts)),
        'circleId' => $circleId,
        'generated' => date('Y-m-d H:i:s'),
    ], null);
}
