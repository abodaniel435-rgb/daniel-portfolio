<?php
declare(strict_types=1);

/**
 * PriceDesk administrator — the verification queue, the reference data the
 * board is built from, the haul rates, accounts and the audit trail.
 *
 * The verification queue is the product. Everything else here exists to keep
 * it honest: unit weights decide what a crate is worth in kilograms, the haul
 * rates decide what a trip costs, and the audit trail records who moved which
 * number and when.
 */

function admin_guard(): void
{
    Auth::requireRole('admin');
}

/** Posted money and weights arrive as human text: thousands separators, spaces. */
function admin_amount(mixed $raw): float
{
    $clean = preg_replace('/[^\d.\-]/', '', (string)$raw) ?? '';
    return (float)$clean;
}

function admin_dashboard(): void
{
    admin_guard();
    view('admin/dashboard', [
        'title' => 'Administrator — PriceDesk',
        'counts' => Db::one('SELECT
              (SELECT COUNT(*) FROM users) AS users,
              (SELECT COUNT(*) FROM markets WHERE is_active = 1) AS markets,
              (SELECT COUNT(*) FROM commodities WHERE is_active = 1) AS commodities,
              (SELECT COUNT(*) FROM price_reports WHERE status = \'pending\') AS pending,
              (SELECT COUNT(*) FROM price_reports WHERE status = \'verified\') AS verified,
              (SELECT COUNT(*) FROM price_reports WHERE status = \'rejected\') AS rejected,
              (SELECT COUNT(*) FROM email_outbox WHERE status = \'queued\') AS queued') ?: [],
        'coverage' => Db::all('SELECT c.name, COUNT(DISTINCT r.market_id) AS markets,
                                     MAX(r.report_date) AS latest,
                                     SUM(r.status = \'verified\') AS verified
                                FROM commodities c
                                LEFT JOIN price_reports r ON r.commodity_id = c.id
                               WHERE c.is_active = 1
                               GROUP BY c.id, c.name
                               ORDER BY verified DESC, c.name LIMIT 12'),
        'recentAudit' => Db::all('SELECT a.*, u.name AS actor FROM audit_log a
            LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.id DESC LIMIT 8'),
    ]);
}

/** The queue: oldest first, because a price nobody looks at is a price rotting. */
function admin_queue(): void
{
    admin_guard();
    $rows = Board::pending();
    foreach ($rows as $i => $row) {
        $commodity = Board::commodity((int)$row['commodity_id']);
        $weights = $commodity === null ? [] : Board::weights($commodity);
        $rows[$i]['perKg'] = Pricing::perKg(
            (float)$row['price'], (string)$row['unit'], (int)$row['quantity'],
            (float)($row['unit_weight_kg'] ?? 1), $weights
        );
        // The reviewer's whole job is spotting the quote that does not belong,
        // so show it how far out it sits instead of making it do the division.
        $peers = Db::all('SELECT price, unit, quantity, report_date, reporter_id FROM price_reports
                           WHERE commodity_id = ? AND market_id = ? AND status = \'verified\'
                             AND report_date >= DATE_SUB(?, INTERVAL 14 DAY) AND id <> ?',
            [(int)$row['commodity_id'], (int)$row['market_id'], (string)$row['report_date'], (int)$row['id']]);
        $peerValues = array_map(static fn (array $p): array => [
            'perKg' => Pricing::perKg((float)$p['price'], (string)$p['unit'], (int)$p['quantity'],
                (float)($row['unit_weight_kg'] ?? 1), $weights),
            'reportDate' => (string)$p['report_date'], 'reporterId' => (int)$p['reporter_id'],
        ], $peers);
        $agg = Pricing::aggregate($peerValues);
        $rows[$i]['peerMedian'] = $agg['median'];
        $rows[$i]['peerCount'] = $agg['count'];
        $rows[$i]['deviationPct'] = $agg['median'] > 0
            ? round(($rows[$i]['perKg'] - $agg['median']) / $agg['median'] * 100, 1) : null;
    }

    view('admin/queue', [
        'title' => 'Verification queue — PriceDesk',
        'rows' => $rows,
        // A wrong verification has to be correctable from the same screen that
        // made it, or the undo path is a database console.
        'recent' => Db::all('SELECT r.*, m.name AS market_name, c.name AS commodity_name,
                                   u.name AS reporter_name, v.name AS verifier_name
                              FROM price_reports r
                              JOIN markets m ON m.id = r.market_id
                              JOIN commodities c ON c.id = r.commodity_id
                              LEFT JOIN users u ON u.id = r.reporter_id
                              LEFT JOIN users v ON v.id = r.verified_by
                             WHERE r.status = \'verified\'
                             ORDER BY r.verified_at DESC, r.id DESC
                             LIMIT 20'),
    ]);
}

function admin_verify(): void
{
    verify_csrf();
    admin_guard();
    $id = (int)($_POST['id'] ?? 0);
    $row = Db::one('SELECT r.*, c.name AS commodity_name, m.name AS market_name
                      FROM price_reports r
                      JOIN commodities c ON c.id = r.commodity_id
                      JOIN markets m ON m.id = r.market_id WHERE r.id = ?', [$id]);
    if ($row === null) {
        http_response_code(404);
        view('errors/404', ['title' => 'Report not found']);
        return;
    }
    if ((string)$row['status'] === 'verified') {
        flash('info', 'That report is already on the board.');
        redirect('/admin/queue');
    }

    Db::update('price_reports', [
        'status' => 'verified',
        'verified_by' => Auth::id(),
        'verified_at' => date('Y-m-d H:i:s'),
        'reject_reason' => '',
    ], 'id = ?', [$id]);
    Board::audit('report.verify', 'Report #' . $id, [
        'commodity' => $row['commodity_name'], 'market' => $row['market_name'], 'price' => $row['price'],
    ]);

    // The watcher asked to be told when a price landed, so tell them when one
    // actually did — not when somebody merely proposed it.
    $watchers = Db::all('SELECT u.email, u.name FROM watches w JOIN users u ON u.id = w.user_id
                          WHERE w.market_id = ? AND w.commodity_id = ? AND u.is_active = 1',
        [(int)$row['market_id'], (int)$row['commodity_id']]);
    foreach ($watchers as $watcher) {
        Mailer::send((string)$watcher['email'], $row['commodity_name'] . ' at ' . $row['market_name'] . ' is on the board',
            Mailer::template('A verified price is live',
                '<p>' . e($watcher['name']) . ',</p><p><strong>' . e($row['commodity_name']) . '</strong> at <strong>'
                . e($row['market_name']) . '</strong> for ' . e((string)$row['report_date']) . ' was verified at '
                . e(money((float)$row['price'])) . ' per ' . e((string)$row['unit']) . '.</p>'),
            '', 'report_verified');
    }

    flash('success', 'Verified — it is on the board.');
    redirect('/admin/queue');
}

function admin_reject(): void
{
    verify_csrf();
    admin_guard();
    $id = (int)($_POST['id'] ?? 0);
    $reason = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 255);
    if ($reason === '') {
        flash('error', 'A rejection needs a reason. The reporter is entitled to know why.');
        redirect('/admin/queue');
    }

    $row = Db::one('SELECT * FROM price_reports WHERE id = ?', [$id]);
    if ($row === null) {
        http_response_code(404);
        view('errors/404', ['title' => 'Report not found']);
        return;
    }

    Db::update('price_reports', ['status' => 'rejected', 'reject_reason' => $reason,
        'verified_by' => Auth::id(), 'verified_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    Board::audit('report.reject', 'Report #' . $id, ['reason' => $reason]);
    flash('success', 'Rejected and the reason recorded.');
    redirect('/admin/queue');
}

/** Undo a bad verification. The board is public money, so a mistake is correctable. */
function admin_unverify(): void
{
    verify_csrf();
    admin_guard();
    $id = (int)($_POST['id'] ?? 0);
    $row = Db::one('SELECT * FROM price_reports WHERE id = ?', [$id]);
    if ($row === null) {
        http_response_code(404);
        view('errors/404', ['title' => 'Report not found']);
        return;
    }
    Db::update('price_reports', ['status' => 'pending', 'reject_reason' => '',
        'verified_by' => null, 'verified_at' => null], 'id = ?', [$id]);
    Board::audit('report.unverify', 'Report #' . $id, ['was' => $row['status']]);
    flash('success', 'Pulled off the board and back into the queue.');
    redirect('/admin/queue');
}

function admin_reference(): void
{
    admin_guard();
    view('admin/reference', [
        'title' => 'Markets and commodities — PriceDesk',
        'markets' => Board::markets(false),
        'commodities' => Db::all('SELECT * FROM commodities ORDER BY category, name'),
    ]);
}

function admin_market_save(): void
{
    verify_csrf();
    admin_guard();
    $v = (new Validator($_POST))->required('name')->max('name', 120)->max('state', 60);
    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($_POST);
        redirect('/admin/reference');
    }
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'name' => (string)$v->value('name'),
        'state' => trim((string)($v->value('state') ?? '')) ?: 'Lagos',
        'kind' => in_array((string)($_POST['kind'] ?? ''), ['farmgate', 'wholesale', 'retail'], true)
            ? (string)$_POST['kind'] : 'retail',
        'haul_km' => round(admin_amount($_POST['haul_km'] ?? 0), 2),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($id > 0) {
        Db::update('markets', $data, 'id = ?', [$id]);
        Board::audit('market.update', 'Market #' . $id, $data);
        flash('success', 'Market updated.');
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = Db::insert('markets', $data);
        Board::audit('market.create', 'Market #' . $id, $data);
        flash('success', 'Market added to the board.');
    }
    redirect('/admin/reference');
}

function admin_commodity_save(): void
{
    verify_csrf();
    admin_guard();
    $v = (new Validator($_POST))->required('name')->max('name', 90)->max('category', 40);
    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($_POST);
        redirect('/admin/reference');
    }

    // Unit weights are typed as "crate=2.4, mudu=0.12". Anything unparseable is
    // dropped rather than stored, so a stray comma cannot poison the axis the
    // whole board is normalised onto.
    $weights = [];
    foreach (preg_split('/[,\n;]+/', (string)($_POST['units_text'] ?? '')) ?: [] as $pair) {
        if (!preg_match('/^\s*([a-zA-Z]{1,20})\s*=\s*([0-9.]+)\s*$/', $pair, $m)) {
            continue;
        }
        $kg = (float)$m[2];
        if ($kg > 0 && $kg <= 10000) {
            $weights[mb_strtolower($m[1])] = round($kg, 3);
        }
    }
    $fallback = round(admin_amount($_POST['unit_weight_kg'] ?? 1), 3);
    if ($fallback <= 0 || $fallback > 10000) {
        $fallback = 1.0;
    }

    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'name' => (string)$v->value('name'),
        'category' => trim((string)($v->value('category') ?? '')) ?: 'crop',
        'units' => json_encode((object)$weights, JSON_UNESCAPED_UNICODE),
        'unit_weight_kg' => $fallback,
        'unit_label' => mb_substr(trim((string)($_POST['unit_label'] ?? '')) ?: 'kg', 0, 40),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($id > 0) {
        Db::update('commodities', $data, 'id = ?', [$id]);
        Board::audit('commodity.update', 'Commodity #' . $id, ['units' => $weights]);
        flash('success', 'Commodity updated. The board re-normalises on the next read.');
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = Db::insert('commodities', $data);
        Board::audit('commodity.create', 'Commodity #' . $id, ['units' => $weights]);
        flash('success', 'Commodity added.');
    }
    redirect('/admin/reference');
}

function admin_rates(): void
{
    admin_guard();
    view('admin/rates', [
        'title' => 'Haulage rates — PriceDesk',
        'rates' => Db::all('SELECT * FROM rates WHERE tool = ? ORDER BY id', ['haul']),
    ]);
}

function admin_rates_save(): void
{
    verify_csrf();
    admin_guard();

    $posted = $_POST['rates'] ?? [];
    if (!is_array($posted)) {
        redirect('/admin/rates');
    }

    $changed = 0;
    foreach ($posted as $code => $raw) {
        $code = (string)$code;
        if (!preg_match('/^[a-z0-9_]{1,48}$/', $code)) {
            continue;
        }
        $value = admin_amount($raw);
        $row = Db::one('SELECT id, value FROM rates WHERE tool = ? AND code = ?', ['haul', $code]);
        if ($row === null || abs((float)$row['value'] - $value) < 0.0001) {
            continue;
        }
        Db::update('rates', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => Auth::id()], 'id = ?', [(int)$row['id']]);
        $changed++;
    }

    if ($changed > 0) {
        Board::refreshRates();
        Board::audit('rates.update', 'haul', ['changed' => $changed]);
    }
    flash('success', $changed ? $changed . ' rate updated. Every comparison uses it immediately.' : 'Nothing changed.');
    redirect('/admin/rates');
}

function admin_users(): void
{
    admin_guard();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 25;
    $total = (int)Db::value('SELECT COUNT(*) FROM users');
    $offset = ($page - 1) * $perPage;

    view('admin/users', [
        'title' => 'Accounts — PriceDesk',
        // Explicit columns: a password hash has no business being inside the
        // array a view receives, however carefully that view prints it.
        'users' => Db::all(
            'SELECT u.id, u.name, u.email, u.phone, u.role, u.is_active, u.last_login_at, u.created_at,
                    (SELECT COUNT(*) FROM price_reports r WHERE r.reporter_id = u.id) AS report_count
             FROM users u ORDER BY u.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
        ),
        'pager' => paginate($total, $perPage, $page),
    ]);
}

function admin_user_status(): void
{
    verify_csrf();
    admin_guard();
    $id = (int)($_POST['id'] ?? 0);
    $user = Db::one('SELECT * FROM users WHERE id = ?', [$id]);
    if ($user === null) {
        redirect('/admin/users');
    }
    if ($id === (int)Auth::id()) {
        flash('error', 'You cannot disable your own account.');
        redirect('/admin/users');
    }
    $active = (int)$user['is_active'] === 1 ? 0 : 1;
    Db::update('users', ['is_active' => $active], 'id = ?', [$id]);
    Board::audit('user.status', 'User #' . $id, ['is_active' => $active]);
    flash('success', $active ? 'Account re-enabled.' : 'Account disabled.');
    redirect('/admin/users');
}

function admin_audit(): void
{
    admin_guard();
    view('admin/audit', [
        'title' => 'Audit trail — PriceDesk',
        'rows' => Db::all('SELECT a.*, u.name AS actor FROM audit_log a
            LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.id DESC LIMIT 200'),
    ]);
}

function admin_outbox(): void
{
    admin_guard();
    view('admin/outbox', [
        'title' => 'Email outbox — PriceDesk',
        'rows' => Db::all('SELECT * FROM email_outbox ORDER BY id DESC LIMIT 120'),
    ]);
}

function admin_automation(): void
{
    admin_guard();
    verify_csrf();
    $ran = Automation::run('PriceDesk');
    flash('info', $ran ? "Automation ran {$ran} tasks." : 'Automation ran but did nothing — check storage/automation.log.');
    redirect('/admin');
}
