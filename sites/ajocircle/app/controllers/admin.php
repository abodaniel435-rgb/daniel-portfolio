<?php
declare(strict_types=1);

function admin_guard(): array
{
    Auth::requireRole('admin');
    return Auth::user();
}

function admin_dashboard(): void
{
    admin_guard();
    $stats = [
        'pooled' => (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE status = 'paid'"),
        'active_circles' => (int)Db::value("SELECT COUNT(*) FROM circles WHERE status = 'active'"),
        'open_circles' => (int)Db::value("SELECT COUNT(*) FROM circles WHERE status = 'open'"),
        'members' => (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'member' AND is_active = 1"),
        'overdue' => (int)Db::value("SELECT COUNT(*) FROM contributions WHERE status <> 'paid' AND due_date < CURDATE()"),
        'overdue_value' => (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE status <> 'paid' AND due_date < CURDATE()"),
        'payouts_due' => (int)Db::value("SELECT COUNT(*) FROM payouts WHERE status = 'scheduled' AND scheduled_date <= CURDATE()"),
        'payouts_paid' => (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE status = 'paid'"),
        'pending_verification' => (int)Db::value("SELECT COUNT(*) FROM contributions WHERE status <> 'paid' AND payment_reference IS NOT NULL AND payment_reference <> ''"),
    ];

    $queue = Db::all(
        "SELECT ct.*, c.name AS circle_name, u.name AS member_name
         FROM contributions ct JOIN circles c ON c.id = ct.circle_id JOIN users u ON u.id = ct.user_id
         WHERE ct.status <> 'paid' AND ct.payment_reference IS NOT NULL AND ct.payment_reference <> ''
         ORDER BY ct.submitted_at ASC LIMIT 6"
    );

    $overdue = Db::all(
        "SELECT ct.*, c.name AS circle_name, u.name AS member_name
         FROM contributions ct JOIN circles c ON c.id = ct.circle_id JOIN users u ON u.id = ct.user_id
         WHERE ct.status <> 'paid' AND ct.due_date < CURDATE()
         ORDER BY ct.due_date ASC LIMIT 6"
    );

    $circles = Db::all(
        "SELECT c.*,
                (SELECT COUNT(*) FROM memberships m WHERE m.circle_id = c.id AND m.status = 'active') AS member_count,
                (SELECT COALESCE(SUM(x.amount), 0) FROM contributions x WHERE x.circle_id = c.id AND x.status = 'paid') AS collected,
                (SELECT COALESCE(SUM(x.amount), 0) FROM payouts x WHERE x.circle_id = c.id AND x.status = 'paid') AS paid_out
         FROM circles c ORDER BY FIELD(c.status, 'active', 'open', 'completed'), c.start_date DESC LIMIT 6"
    );

    $payoutsDue = Db::all(
        "SELECT p.*, c.name AS circle_name, u.name AS recipient_name
         FROM payouts p JOIN circles c ON c.id = p.circle_id JOIN users u ON u.id = p.recipient_user_id
         WHERE p.status = 'scheduled' ORDER BY p.scheduled_date ASC LIMIT 5"
    );

    // Seven-day collection trend from paid contributions.
    $series = [];
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-{$i} days"));
        $total = (float)Db::value(
            "SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE status = 'paid' AND paid_on = ?",
            [$day]
        );
        $series[] = ['label' => date('D', strtotime($day)), 'value' => $total];
    }

    view('admin/dashboard', [
        'title' => 'Organiser overview — AjoCircle',
        'stats' => $stats,
        'queue' => $queue,
        'overdue' => $overdue,
        'circles' => $circles,
        'payoutsDue' => $payoutsDue,
        'series' => $series,
    ]);
}

function admin_circles_page(): void
{
    admin_guard();
    $status = trim((string)($_GET['status'] ?? ''));
    $sql = "SELECT c.*,
                   (SELECT COUNT(*) FROM memberships m WHERE m.circle_id = c.id AND m.status = 'active') AS member_count,
                   (SELECT COALESCE(SUM(x.amount), 0) FROM contributions x WHERE x.circle_id = c.id AND x.status = 'paid') AS collected
            FROM circles c";
    $params = [];
    if (in_array($status, ['open', 'active', 'completed'], true)) {
        $sql .= ' WHERE c.status = ?';
        $params[] = $status;
    }
    $sql .= " ORDER BY FIELD(c.status, 'active', 'open', 'completed'), c.start_date DESC";

    view('admin/circles', [
        'title' => 'Circles — AjoCircle',
        'circles' => Db::all($sql, $params),
        'status' => $status,
        'owners' => Db::all("SELECT id, name, email FROM users WHERE is_active = 1 ORDER BY name"),
    ]);
}

function admin_circle_create(): void
{
    verify_csrf();
    $admin = admin_guard();
    $input = $_POST;
    $v = (new Validator($input))
        ->required('name', 'description', 'contribution_amount', 'frequency', 'member_limit', 'start_date')
        ->max('name', 140)
        ->max('description', 2000)
        ->integer('contribution_amount', 500, 10000000)
        ->in('frequency', ['weekly', 'monthly'])
        ->integer('member_limit', 2, 52)
        ->date('start_date')
        ->in('category', ajo_categories(), false);

    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($input);
        flash('error', 'Please correct the highlighted fields.');
        redirect('/admin/circles');
    }

    $ownerId = (int)($_POST['owner_id'] ?? 0) ?: null;
    $name = (string)$v->value('name');
    $id = Db::insert('circles', [
        'name' => $name,
        'slug' => unique_circle_slug($name),
        'description' => (string)$v->value('description'),
        'category' => (string)$v->value('category', 'Community'),
        'contribution_amount' => (float)$v->value('contribution_amount'),
        'frequency' => (string)$v->value('frequency'),
        'member_limit' => (int)$v->value('member_limit'),
        'start_date' => (string)$v->value('start_date'),
        'payout_mode' => 'fixed',
        'status' => 'open',
        'owner_id' => $ownerId,
        'created_by' => (int)$admin['id'],
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    audit('circle.created', 'Circle #' . $id . ' ' . $name);
    flash('success', $name . ' is open for members.');
    redirect('/admin/circles/' . $id);
}

function admin_circle_show(): void
{
    admin_guard();
    $id = (int)($_GET['id'] ?? 0);
    $circle = Db::one('SELECT * FROM circles WHERE id = ? LIMIT 1', [$id]);
    if (!$circle) {
        http_response_code(404);
        view('errors/404', ['title' => 'Circle not found']);
        return;
    }
    if (!can_manage_circle($circle)) {
        http_response_code(403);
        view('errors/403', ['title' => 'Access denied']);
        return;
    }

    $contributions = Db::all(
        'SELECT ct.*, u.name AS member_name, m.position
         FROM contributions ct
         JOIN users u ON u.id = ct.user_id
         JOIN memberships m ON m.id = ct.membership_id
         WHERE ct.circle_id = ?
         ORDER BY ct.due_date DESC, m.position ASC LIMIT 400',
        [$id]
    );

    view('admin/circle', [
        'title' => $circle['name'] . ' — AjoCircle',
        'circle' => $circle,
        'members' => circle_members($id, false),
        'rounds' => circle_rounds($circle),
        'totals' => circle_totals($id),
        'contributions' => $contributions,
        'payouts' => Db::all(
            'SELECT p.*, u.name AS recipient_name FROM payouts p JOIN users u ON u.id = p.recipient_user_id
             WHERE p.circle_id = ? ORDER BY p.round_no ASC',
            [$id]
        ),
        'currentRound' => circle_current_round($circle),
    ]);
}

function admin_circle_status(): void
{
    verify_csrf();
    $admin = admin_guard();
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $circle = Db::one('SELECT * FROM circles WHERE id = ? LIMIT 1', [$id]);
    if (!$circle) {
        flash('error', 'That circle could not be found.');
        redirect('/admin/circles');
    }
    if (!can_manage_circle($circle)) {
        flash('error', 'You cannot manage this circle.');
        redirect('/admin/circles');
    }

    $map = [
        'open' => ['status' => 'open', 'activated_at' => null, 'completed_at' => null],
        'activate' => ['status' => 'active', 'activated_at' => date('Y-m-d H:i:s'), 'completed_at' => null],
        'complete' => ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')],
    ];
    if (!isset($map[$action])) {
        flash('error', 'Unknown circle action.');
        redirect('/admin/circles/' . $id);
    }
    if ($map[$action]['status'] === 'active' && circle_member_count($id) < 2) {
        flash('error', 'A circle needs at least two members before it can be activated.');
        redirect('/admin/circles/' . $id);
    }

    Db::update('circles', $map[$action], 'id = :id', ['id' => $id]);
    audit('circle.' . $action, 'Circle #' . $id . ' ' . $circle['name']);

    if ($action === 'activate') {
        try {
            notify_admin(
                'Circle activated — ' . $circle['name'],
                'The rotation has started',
                '<p><strong>' . e($circle['name']) . '</strong> is now active with ' . circle_member_count($id)
                . ' members. Round 1 is due ' . e(short_date(round_due_date($circle['start_date'], $circle['frequency'], 1))) . '.</p>',
                'circle_activated',
                'Open circle',
                '/admin/circles/' . $id
            );
        } catch (Throwable) {
        }
    }

    flash('success', 'Circle marked ' . $map[$action]['status'] . '.');
    redirect('/admin/circles/' . $id);
}

function admin_contributions_page(): void
{
    admin_guard();
    $status = trim((string)($_GET['status'] ?? ''));
    $circleId = (int)($_GET['circle_id'] ?? 0);
    $search = trim((string)($_GET['q'] ?? ''));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 25;

    $where = '1 = 1';
    $params = [];
    if (in_array($status, ['pending', 'paid', 'late'], true)) {
        $where .= ' AND ct.status = ?';
        $params[] = $status;
    }
    if ($status === 'submitted') {
        $where .= " AND ct.status <> 'paid' AND ct.payment_reference IS NOT NULL AND ct.payment_reference <> ''";
    }
    if ($circleId > 0) {
        $where .= ' AND ct.circle_id = ?';
        $params[] = $circleId;
    }
    if ($search !== '') {
        $where .= ' AND (u.name LIKE ? OR u.email LIKE ? OR ct.payment_reference LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $total = (int)Db::value(
        'SELECT COUNT(*) FROM contributions ct JOIN users u ON u.id = ct.user_id WHERE ' . $where,
        $params
    );
    $pager = paginate($total, $perPage, $page);

    $rows = Db::all(
        'SELECT ct.*, c.name AS circle_name, u.name AS member_name, u.email, m.position
         FROM contributions ct
         JOIN circles c ON c.id = ct.circle_id
         JOIN users u ON u.id = ct.user_id
         JOIN memberships m ON m.id = ct.membership_id
         WHERE ' . $where . '
         ORDER BY (ct.status <> \'paid\' AND ct.due_date < CURDATE()) DESC, ct.due_date ASC, ct.id DESC
         LIMIT ' . $pager['limit'] . ' OFFSET ' . $pager['offset'],
        $params
    );

    view('admin/contributions', [
        'title' => 'Contributions — AjoCircle',
        'rows' => $rows,
        'pager' => $pager,
        'status' => $status,
        'circleId' => $circleId,
        'search' => $search,
        'circles' => Db::all('SELECT id, name FROM circles ORDER BY name'),
        'queued' => (int)Db::value("SELECT COUNT(*) FROM contributions WHERE status <> 'paid' AND payment_reference IS NOT NULL AND payment_reference <> ''"),
    ]);
}

function admin_contribution_verify(): void
{
    verify_csrf();
    $admin = admin_guard();
    $id = (int)($_POST['id'] ?? 0);
    $row = Db::one(
        'SELECT ct.*, u.name, u.email, c.name AS circle_name
         FROM contributions ct JOIN users u ON u.id = ct.user_id JOIN circles c ON c.id = ct.circle_id
         WHERE ct.id = ? LIMIT 1',
        [$id]
    );
    if (!$row) {
        flash('error', 'That contribution could not be found.');
        redirect('/admin/contributions');
    }
    if ($row['status'] === 'paid') {
        flash('error', 'That contribution is already verified as paid.');
        redirect('/admin/contributions');
    }
    if (empty($row['payment_reference'])) {
        flash('error', 'The member has not recorded a payment reference for this round yet.');
        redirect('/admin/contributions');
    }

    Db::update('contributions', [
        'status' => 'paid',
        'paid_on' => $row['paid_on'] ?: date('Y-m-d'),
        'verified_by' => (int)$admin['id'],
        'verified_at' => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $id]);

    audit('contribution.verified', 'Contribution #' . $id . ' ' . $row['payment_reference']);
    $fresh = Db::one('SELECT * FROM contributions WHERE id = ?', [$id]);
    $circle = Db::one('SELECT * FROM circles WHERE id = ?', [(int)$row['circle_id']]);
    try {
        notify_contribution_verified($fresh + ['name' => $row['name'], 'email' => $row['email']], $circle, false);
    } catch (Throwable) {
    }

    flash('success', 'Contribution from ' . $row['name'] . ' verified — ' . money((float)$row['amount']) . ' added to the pool.');
    redirect('/admin/contributions?status=submitted');
}

function admin_contribution_late(): void
{
    verify_csrf();
    $admin = admin_guard();
    $id = (int)($_POST['id'] ?? 0);
    $row = Db::one(
        'SELECT ct.*, u.name, u.email, c.name AS circle_name
         FROM contributions ct JOIN users u ON u.id = ct.user_id JOIN circles c ON c.id = ct.circle_id
         WHERE ct.id = ? LIMIT 1',
        [$id]
    );
    if (!$row) {
        flash('error', 'That contribution could not be found.');
        redirect('/admin/contributions');
    }
    if ($row['status'] === 'paid') {
        flash('error', 'Verified contributions cannot be marked late.');
        redirect('/admin/contributions');
    }

    Db::update('contributions', [
        'status' => 'late',
        'note' => mb_substr(trim((string)($_POST['note'] ?? $row['note'] ?? '')), 0, 255),
        'verified_by' => (int)$admin['id'],
        'verified_at' => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $id]);

    audit('contribution.late', 'Contribution #' . $id);
    $fresh = Db::one('SELECT * FROM contributions WHERE id = ?', [$id]);
    $circle = Db::one('SELECT * FROM circles WHERE id = ?', [(int)$row['circle_id']]);
    try {
        notify_contribution_verified($fresh + ['name' => $row['name'], 'email' => $row['email']], $circle, true);
    } catch (Throwable) {
    }

    flash('success', 'Round ' . (int)$row['round_no'] . ' for ' . $row['name'] . ' marked late.');
    redirect('/admin/contributions');
}

function admin_payouts_page(): void
{
    admin_guard();
    $status = trim((string)($_GET['status'] ?? ''));
    $sql = "SELECT p.*, c.name AS circle_name, u.name AS recipient_name, u.email AS recipient_email
            FROM payouts p JOIN circles c ON c.id = p.circle_id JOIN users u ON u.id = p.recipient_user_id";
    $params = [];
    if (in_array($status, ['scheduled', 'paid'], true)) {
        $sql .= ' WHERE p.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY FIELD(p.status, \'scheduled\', \'paid\'), p.scheduled_date ASC LIMIT 200';

    view('admin/payouts', [
        'title' => 'Payouts — AjoCircle',
        'payouts' => Db::all($sql, $params),
        'status' => $status,
        'dueCount' => (int)Db::value("SELECT COUNT(*) FROM payouts WHERE status = 'scheduled' AND scheduled_date <= CURDATE()"),
        'scheduledTotal' => (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE status = 'scheduled'"),
        'paidTotal' => (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE status = 'paid'"),
        'circles' => Db::all("SELECT id, name, status FROM circles WHERE status IN ('open','active') ORDER BY name"),
    ]);
}

function admin_payout_schedule(): void
{
    verify_csrf();
    $admin = admin_guard();
    $circleId = (int)($_POST['id'] ?? $_POST['circle_id'] ?? 0);
    $roundNo = (int)($_POST['round_no'] ?? 0);
    $circle = Db::one('SELECT * FROM circles WHERE id = ? LIMIT 1', [$circleId]);
    if (!$circle) {
        flash('error', 'That circle could not be found.');
        redirect('/admin/payouts');
    }
    if (!can_manage_circle($circle)) {
        flash('error', 'You cannot schedule payouts for this circle.');
        redirect('/admin/payouts');
    }
    if ($circle['status'] !== 'active') {
        flash('error', 'Only active circles can pay out. Activate the circle first.');
        redirect('/admin/circles/' . $circleId);
    }
    if ($roundNo < 1 || $roundNo > (int)$circle['member_limit']) {
        flash('error', 'That round is outside this circle\'s schedule.');
        redirect('/admin/circles/' . $circleId);
    }
    if (Db::value('SELECT COUNT(*) FROM payouts WHERE circle_id = ? AND round_no = ?', [$circleId, $roundNo])) {
        flash('error', 'Round ' . $roundNo . ' already has a payout. One payout per round.');
        redirect('/admin/circles/' . $circleId);
    }

    $membership = Db::one(
        'SELECT m.*, u.name, u.email FROM memberships m JOIN users u ON u.id = m.user_id
         WHERE m.circle_id = ? AND m.position = ? AND m.status = \'active\' LIMIT 1',
        [$circleId, $roundNo]
    );
    if (!$membership) {
        flash('error', 'No member holds position #' . $roundNo . ' in this circle, so that round has no recipient.');
        redirect('/admin/circles/' . $circleId);
    }

    $memberCount = circle_member_count($circleId);
    $amount = (float)$circle['contribution_amount'] * max(1, $memberCount);
    $scheduled = trim((string)($_POST['scheduled_date'] ?? ''));
    if ($scheduled === '') {
        $scheduled = round_due_date($circle['start_date'], $circle['frequency'], $roundNo);
    }

    $payoutId = Db::insert('payouts', [
        'reference' => reference('PAY'),
        'circle_id' => $circleId,
        'round_no' => $roundNo,
        'membership_id' => (int)$membership['id'],
        'recipient_user_id' => (int)$membership['user_id'],
        'amount' => $amount,
        'scheduled_date' => $scheduled,
        'status' => 'scheduled',
        'note' => mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255),
        'created_by' => (int)$admin['id'],
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    audit('payout.scheduled', 'Payout #' . $payoutId . ' circle #' . $circleId . ' round ' . $roundNo);
    $payout = Db::one('SELECT * FROM payouts WHERE id = ?', [$payoutId]);
    try {
        notify_payout_scheduled($payout, $circle, $membership);
    } catch (Throwable) {
    }

    flash('success', 'Payout of ' . money($amount) . ' scheduled for ' . $membership['name'] . ' (round ' . $roundNo . ').');
    redirect('/admin/circles/' . $circleId);
}

function admin_payout_paid(): void
{
    verify_csrf();
    $admin = admin_guard();
    $id = (int)($_POST['id'] ?? 0);
    $payout = Db::one(
        'SELECT p.*, c.name AS circle_name, c.id AS circle_id FROM payouts p
         JOIN circles c ON c.id = p.circle_id WHERE p.id = ? LIMIT 1',
        [$id]
    );
    if (!$payout) {
        flash('error', 'That payout could not be found.');
        redirect('/admin/payouts');
    }
    if ($payout['status'] === 'paid') {
        flash('error', 'That payout is already settled.');
        redirect('/admin/payouts');
    }

    $paidOn = trim((string)($_POST['paid_on'] ?? '')) ?: date('Y-m-d');
    Db::update('payouts', [
        'status' => 'paid',
        'paid_on' => $paidOn,
        'flagged_at' => $payout['flagged_at'] ?: date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $id]);

    audit('payout.paid', 'Payout #' . $id . ' ' . $payout['reference']);
    $fresh = Db::one('SELECT * FROM payouts WHERE id = ?', [$id]);
    $circle = Db::one('SELECT * FROM circles WHERE id = ?', [(int)$payout['circle_id']]);
    $recipient = Db::one('SELECT name, email FROM users WHERE id = ?', [(int)$payout['recipient_user_id']]);
    if ($recipient) {
        try {
            notify_payout_paid($fresh, $circle, $recipient);
        } catch (Throwable) {
        }
    }

    flash('success', 'Payout ' . $payout['reference'] . ' marked paid.');
    redirect('/admin/payouts');
}

function admin_users_page(): void
{
    admin_guard();
    $search = trim((string)($_GET['q'] ?? ''));
    $sql = "SELECT u.*,
                   (SELECT COUNT(*) FROM memberships m WHERE m.user_id = u.id AND m.status = 'active') AS circle_count,
                   (SELECT COALESCE(SUM(ct.amount), 0) FROM contributions ct WHERE ct.user_id = u.id AND ct.status = 'paid') AS saved
            FROM users u WHERE 1 = 1";
    $params = [];
    if ($search !== '') {
        $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        $params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
    }
    $sql .= ' ORDER BY FIELD(u.role, \'admin\', \'member\'), u.name ASC LIMIT 300';

    view('admin/users', [
        'title' => 'Members — AjoCircle',
        'users' => Db::all($sql, $params),
        'search' => $search,
    ]);
}

function admin_user_toggle(): void
{
    verify_csrf();
    $admin = admin_guard();
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$admin['id']) {
        flash('error', 'You cannot deactivate your own account.');
        redirect('/admin/users');
    }
    $user = Db::one('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    if (!$user) {
        flash('error', 'That account could not be found.');
        redirect('/admin/users');
    }
    $next = (int)$user['is_active'] === 1 ? 0 : 1;
    Db::update('users', ['is_active' => $next], 'id = :id', ['id' => $id]);
    audit('user.toggled', 'User #' . $id . ' active=' . $next);
    flash('success', $user['name'] . ' is now ' . ($next === 1 ? 'active' : 'suspended') . '.');
    redirect('/admin/users');
}

function admin_outbox_page(): void
{
    admin_guard();
    $type = trim((string)($_GET['type'] ?? ''));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 20;

    $where = '1 = 1';
    $params = [];
    if ($type !== '') {
        $where .= ' AND message_type = ?';
        $params[] = $type;
    }
    $total = (int)Db::value('SELECT COUNT(*) FROM email_outbox WHERE ' . $where, $params);
    $pager = paginate($total, $perPage, $page);
    $rows = Db::all(
        'SELECT * FROM email_outbox WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . $pager['limit'] . ' OFFSET ' . $pager['offset'],
        $params
    );

    view('admin/outbox', [
        'title' => 'Email outbox — AjoCircle',
        'rows' => $rows,
        'pager' => $pager,
        'type' => $type,
        'types' => array_column(Db::all('SELECT DISTINCT message_type FROM email_outbox ORDER BY message_type'), 'message_type'),
        'counts' => Db::one("SELECT COUNT(*) AS total, SUM(status = 'queued') AS queued, SUM(status = 'sent') AS sent, SUM(status = 'failed') AS failed FROM email_outbox"),
    ]);
}

function admin_run_automation(): void
{
    verify_csrf();
    admin_guard();
    $ran = Automation::run('AjoCircle');
    audit('automation.run', $ran . ' tasks');
    flash('success', 'Automation finished: ' . $ran . ' task(s) executed. See storage/automation.log for details.');
    redirect('/admin');
}
