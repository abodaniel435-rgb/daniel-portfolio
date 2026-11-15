<?php
declare(strict_types=1);

function admin_dashboard(): void
{
    Auth::requireRole('admin');
    view('admin/dashboard', [
        'title' => 'Administrator — NairaDesk',
        'users' => (int)Db::value('SELECT COUNT(*) FROM users'),
        'companies' => (int)Db::value('SELECT COUNT(*) FROM companies'),
        'runs' => (int)Db::value('SELECT COUNT(*) FROM pay_runs'),
        'calcs' => (int)Db::value('SELECT COUNT(*) FROM calculations'),
        'recentAudit' => Db::all('SELECT * FROM audit_log ORDER BY id DESC LIMIT 8'),
        'topTools' => Db::all('SELECT tool, COUNT(*) AS n FROM calculations GROUP BY tool ORDER BY n DESC LIMIT 5'),
    ]);
}

function admin_users(): void
{
    Auth::requireRole('admin');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 25;
    $total = (int)Db::value('SELECT COUNT(*) FROM users');
    $offset = ($page - 1) * $perPage;

    view('admin/users', [
        'title' => 'Accounts — NairaDesk',
        'users' => Db::all(
            'SELECT u.*, (SELECT COUNT(*) FROM companies c WHERE c.owner_id = u.id) AS company_count
             FROM users u ORDER BY u.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
        ),
        'pager' => paginate($total, $perPage, $page),
    ]);
}

function admin_user_status(): void
{
    verify_csrf();
    Auth::requireRole('admin');
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
    Desk::audit('user.status', 'User #' . $id, ['is_active' => $active]);
    flash('success', $active ? 'Account re-enabled.' : 'Account disabled.');
    redirect('/admin/users');
}

function admin_rates(): void
{
    Auth::requireRole('admin');
    view('admin/rates', [
        'title' => 'Statutory rates — NairaDesk',
        'rates' => Db::all('SELECT * FROM rates WHERE tool = ? ORDER BY id', ['payroll']),
    ]);
}

function admin_rates_save(): void
{
    verify_csrf();
    Auth::requireRole('admin');

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
        $value = work_amount($raw);
        $row = Db::one('SELECT id, value FROM rates WHERE tool = ? AND code = ?', ['payroll', $code]);
        if ($row === null || abs((float)$row['value'] - $value) < 0.0001) {
            continue;
        }
        Db::update('rates', [
            'value' => $value,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => Auth::id(),
        ], 'id = ?', [(int)$row['id']]);
        $changed++;
    }

    if ($changed > 0) {
        Desk::refreshRates();
        Desk::audit('rates.update', 'payroll', ['changed' => $changed]);
    }
    flash('success', $changed ? $changed . ' statutory figure updated. New runs use it immediately.' : 'Nothing changed.');
    redirect('/admin/rates');
}

function admin_audit(): void
{
    Auth::requireRole('admin');
    view('admin/audit', [
        'title' => 'Audit trail — NairaDesk',
        'rows' => Db::all('SELECT a.*, u.name AS actor FROM audit_log a
            LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.id DESC LIMIT 200'),
    ]);
}

function admin_runs(): void
{
    Auth::requireRole('admin');
    view('admin/runs', [
        'title' => 'All pay runs — NairaDesk',
        'rows' => Db::all('SELECT r.*, c.name AS company, u.name AS owner
            FROM pay_runs r JOIN companies c ON c.id = r.company_id
            JOIN users u ON u.id = r.created_by ORDER BY r.id DESC LIMIT 100'),
    ]);
}
