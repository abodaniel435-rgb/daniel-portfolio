<?php
declare(strict_types=1);

function home_index(): void
{
    $open = Db::all(
        "SELECT c.*,
                (SELECT COUNT(*) FROM memberships m WHERE m.circle_id = c.id AND m.status = 'active') AS member_count
         FROM circles c WHERE c.status = 'open'
         ORDER BY c.start_date ASC LIMIT 3"
    );
    $stats = [
        'pooled' => (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE status = 'paid'"),
        'members' => (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'member' AND is_active = 1"),
        'circles' => (int)Db::value("SELECT COUNT(*) FROM circles WHERE status IN ('open','active')"),
        'payouts' => (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE status = 'paid'"),
    ];
    $recent = Db::all(
        "SELECT c.name, c.contribution_amount, c.frequency, p.amount, p.paid_on, u.name AS recipient
         FROM payouts p JOIN circles c ON c.id = p.circle_id JOIN users u ON u.id = p.recipient_user_id
         WHERE p.status = 'paid' ORDER BY p.paid_on DESC, p.id DESC LIMIT 4"
    );

    $feature = Db::one(
        "SELECT c.*, (SELECT COUNT(*) FROM memberships m WHERE m.circle_id = c.id AND m.status = 'active') AS member_count
         FROM circles c WHERE c.status = 'active' ORDER BY c.start_date DESC LIMIT 1"
    );
    $featureRounds = [];
    $featurePercent = 0;
    if ($feature) {
        $featureRounds = array_slice(circle_rounds($feature), 0, 4);
        $totals = circle_totals((int)$feature['id']);
        $target = (float)$feature['contribution_amount'] * max(1, (int)$feature['member_count']) * (int)$feature['member_limit'];
        $featurePercent = $target > 0 ? min(100, (int)round($totals['paid'] / $target * 100)) : 0;
    }

    view('home', [
        'title' => 'AjoCircle — the trusted ledger for your ajo group',
        'openCircles' => $open,
        'stats' => $stats,
        'recentPayouts' => $recent,
        'feature' => $feature,
        'featureRounds' => $featureRounds,
        'featurePercent' => $featurePercent,
    ]);
}

function circles_index(): void
{
    $search = trim((string)($_GET['q'] ?? ''));
    $frequency = trim((string)($_GET['frequency'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));

    $sql = "SELECT c.*,
                   (SELECT COUNT(*) FROM memberships m WHERE m.circle_id = c.id AND m.status = 'active') AS member_count
            FROM circles c WHERE 1 = 1";
    $params = [];
    if ($search !== '') {
        $sql .= ' AND (c.name LIKE ? OR c.description LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    if (in_array($frequency, ['weekly', 'monthly'], true)) {
        $sql .= ' AND c.frequency = ?';
        $params[] = $frequency;
    }
    if (in_array($status, ['open', 'active', 'completed'], true)) {
        $sql .= ' AND c.status = ?';
        $params[] = $status;
    }
    $sql .= " ORDER BY FIELD(c.status, 'open', 'active', 'completed'), c.start_date ASC";

    view('circles', [
        'title' => 'Circle directory — AjoCircle',
        'circles' => Db::all($sql, $params),
        'search' => $search,
        'frequency' => $frequency,
        'status' => $status,
    ]);
}

function circle_show(): void
{
    $id = (int)($_GET['id'] ?? 0);
    $circle = Db::one('SELECT * FROM circles WHERE id = ? LIMIT 1', [$id]);
    if (!$circle) {
        http_response_code(404);
        view('errors/404', ['title' => 'Circle not found']);
        return;
    }

    $members = circle_members($id);
    $membership = Auth::check() ? circle_has_member($id, (int)Auth::id()) : null;
    $position = $membership && $membership['status'] === 'active' ? member_payout_position($circle, $membership) : null;

    view('circle', [
        'title' => $circle['name'] . ' — AjoCircle',
        'circle' => $circle,
        'members' => $members,
        'rounds' => circle_rounds($circle),
        'totals' => circle_totals($id),
        'membership' => $membership,
        'position' => $position,
    ]);
}
