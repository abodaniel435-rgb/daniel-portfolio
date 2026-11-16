<?php
declare(strict_types=1);

/**
 * The payroll desk. Every route in here is behind Auth::requireRole('admin'),
 * which is what turns a payslip tool into software a business can hand to an
 * accountant: the roster, the band table, the relief constants and the runs all
 * belong to this role, and nothing here reads an id from the page without
 * checking the row exists.
 */

function admin_guard(): void
{
    Auth::requireLogin();
    Auth::requireRole('admin');
}

function admin_dashboard(): void
{
    admin_guard();
    $summary = Desk::summary();
    $last = Desk::runs(1)[0] ?? null;
    $draft = null;
    foreach (Desk::runs(12) as $run) {
        if ($run['status'] === 'draft') {
            $draft = $run;
            break;
        }
    }

    view('admin/dashboard', [
        'title' => 'Payroll desk — PayRite',
        'bodyClass' => 'admin',
        'summary' => $summary,
        'last' => $last,
        'draft' => $draft,
        'period' => date('Y-m'),
        'roster' => array_slice(Desk::roster('active'), 0, 6),
        'queries' => Db::all("SELECT id, staff_no, name, query_note, updated_at FROM employees
                               WHERE query_note <> '' ORDER BY updated_at DESC LIMIT 6"),
        'calculations' => Desk::recentCalculations(6),
        'bands' => Desk::bands(),
    ]);
}

/* ---------- roster ---------- */

function admin_employees(): void
{
    admin_guard();
    $status = (string)($_GET['status'] ?? '');
    $rows = Desk::roster($status);

    // The projected monthly figures are computed, not stored: a change to the
    // band table or the relief constants is reflected on the roster at once,
    // which is exactly why they must never be cached in the employee row.
    $cost = ['gross' => 0.0, 'net' => 0.0, 'cost' => 0.0, 'paye' => 0.0];
    foreach ($rows as $i => $row) {
        $r = Desk::compute($row);
        $rows[$i]['gross'] = $r['empty'] ? 0 : $r['gross'];
        $rows[$i]['paye'] = $r['empty'] ? 0 : $r['paye'];
        $rows[$i]['net'] = $r['empty'] ? 0 : $r['net'];
        $rows[$i]['cost'] = $r['empty'] ? 0 : $r['employer_cost'];
        $cost['gross'] += $rows[$i]['gross'];
        $cost['paye'] += $rows[$i]['paye'];
        $cost['net'] += $rows[$i]['net'];
        $cost['cost'] += $rows[$i]['cost'];
    }

    view('admin/employees', [
        'title' => 'Roster — PayRite',
        'bodyClass' => 'admin',
        'employees' => $rows,
        'cost' => $cost,
        'status' => $status,
        'counts' => [
            'active' => (int)Db::value("SELECT COUNT(*) FROM employees WHERE status = 'active'"),
            'paused' => (int)Db::value("SELECT COUNT(*) FROM employees WHERE status = 'paused'"),
            'left' => (int)Db::value("SELECT COUNT(*) FROM employees WHERE status = 'left'"),
        ],
    ]);
}

/** @return array<string,string> */
function admin_employee_validate(array $post): array
{
    $errors = [];
    $name = trim((string)($post['name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 120) {
        $errors['name'] = 'Give the person a name of up to 120 characters.';
    }
    $staffNo = strtoupper(trim((string)($post['staff_no'] ?? '')));
    if (preg_match('/^[A-Z0-9-]{2,24}$/', $staffNo) !== 1) {
        $errors['staff_no'] = 'Staff number: 2–24 letters, numbers or dashes.';
    } else {
        $clash = Db::one('SELECT id FROM employees WHERE staff_no = ? AND id <> ?', [$staffNo, (int)($post['id'] ?? 0)]);
        if ($clash !== null) {
            $errors['staff_no'] = 'That staff number is already on the roster.';
        }
    }
    $components = ['basic', 'housing', 'transport', 'other'];
    $total = 0.0;
    foreach ($components as $field) {
        $total += payrite_amount($post[$field] ?? 0);
    }
    if ($total <= 0) {
        $errors['basic'] = 'The monthly package must add up to more than zero.';
    }
    $pension = (string)($post['pension_rate'] ?? '');
    if ($pension !== '' && (!is_numeric($pension) || (float)$pension < 0 || (float)$pension > 20)) {
        $errors['pension_rate'] = 'Employee pension rate must be between 0% and 20%.';
    }
    if (!in_array((string)($post['status'] ?? 'active'), ['active', 'paused', 'left'], true)) {
        $errors['status'] = 'Unknown status.';
    }
    return $errors;
}

/** @return array<string,mixed> */
function admin_employee_data(array $post): array
{
    return [
        'staff_no' => strtoupper(trim((string)$post['staff_no'])),
        'name' => trim((string)$post['name']),
        'role_title' => trim(mb_substr((string)($post['role_title'] ?? ''), 0, 120)),
        'department' => trim(mb_substr((string)($post['department'] ?? 'General'), 0, 80)) ?: 'General',
        'basic' => payrite_amount($post['basic'] ?? 0),
        'housing' => payrite_amount($post['housing'] ?? 0),
        'transport' => payrite_amount($post['transport'] ?? 0),
        'other' => payrite_amount($post['other'] ?? 0),
        'annual_rent' => payrite_amount($post['annual_rent'] ?? 0),
        'claim_rent' => in_array((string)($post['claim_rent'] ?? '1'), ['1', 'on', 'yes', 'true'], true) ? 1 : 0,
        'pension_rate' => is_numeric($post['pension_rate'] ?? null) ? (float)$post['pension_rate'] : 8.0,
        'status' => (string)($post['status'] ?? 'active'),
        'joined_at' => !empty($post['joined_at']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$post['joined_at']) === 1
            ? (string)$post['joined_at'] : null,
        'updated_at' => date('Y-m-d H:i:s'),
    ];
}

function admin_employee_new(): void
{
    admin_guard();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        verify_csrf();
        $post = $_POST;
        $post['staff_no'] = trim((string)($post['staff_no'] ?? '')) ?: Desk::nextStaffNo();
        $errors = admin_employee_validate($post);
        if ($errors) {
            remember_errors($errors);
            remember_old($post);
            redirect('/admin/employees/new');
        }
        $data = admin_employee_data($post);
        $data['query_note'] = '';
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = Db::insert('employees', $data);
        Desk::audit('employee.create', 'Employee #' . $id, ['staff_no' => $data['staff_no'], 'name' => $data['name']]);
        flash('success', $data['name'] . ' is on the roster.');
        redirect('/admin/employees/' . $id . '/edit');
    }

    view('admin/employee_form', [
        'title' => 'Add to roster — PayRite',
        'bodyClass' => 'admin',
        'employee' => ['staff_no' => Desk::nextStaffNo(), 'claim_rent' => 1, 'pension_rate' => 8,
                       'status' => 'active', 'department' => 'General'],
        'creating' => true,
    ]);
}

function admin_employee_edit(): void
{
    admin_guard();
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $employee = Desk::employee($id);
    if ($employee === null) {
        http_response_code(404);
        view('errors/404', ['title' => 'Employee not found']);
        return;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        verify_csrf();
        $errors = admin_employee_validate(array_merge($_POST, ['id' => $id]));
        if ($errors) {
            remember_errors($errors);
            remember_old($_POST);
            redirect('/admin/employees/' . $id . '/edit');
        }
        $data = admin_employee_data($_POST);
        Db::update('employees', $data, 'id = ?', [$id]);
        Desk::audit('employee.update', 'Employee #' . $id, ['name' => $data['name'], 'basic' => $data['basic']]);
        flash('success', 'Package saved. The next run will use it.');
        redirect('/admin/employees/' . $id . '/edit');
    }

    view('admin/employee_form', [
        'title' => $employee['name'] . ' — PayRite',
        'bodyClass' => 'admin',
        'employee' => $employee,
        'creating' => false,
        'preview' => Desk::compute($employee),
        'linked' => $employee['user_id'] ? Db::one('SELECT name, email, is_active FROM users WHERE id = ?', [(int)$employee['user_id']]) : null,
    ]);
}

function admin_employee_status(): void
{
    admin_guard();
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $employee = Desk::employee($id);
    $status = (string)($_POST['status'] ?? '');
    if ($employee === null || !in_array($status, ['active', 'paused', 'left'], true)) {
        flash('error', 'That roster change could not be made.');
        redirect('/admin/employees');
    }
    Db::update('employees', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    Desk::audit('employee.status', 'Employee #' . $id, ['from' => $employee['status'], 'to' => $status]);
    flash('success', $employee['name'] . ' is now ' . $status . '.');
    redirect('/admin/employees');
}

/* ---------- runs ---------- */

function admin_runs(): void
{
    admin_guard();
    view('admin/runs', [
        'title' => 'Payroll runs — PayRite',
        'bodyClass' => 'admin',
        'runs' => Desk::runs(36),
        'period' => date('Y-m'),
        'headcount' => (int)Db::value("SELECT COUNT(*) FROM employees WHERE status = 'active'"),
    ]);
}

function admin_run_create(): void
{
    admin_guard();
    verify_csrf();
    $period = trim((string)($_POST['period'] ?? ''));
    if (!Desk::validPeriod($period)) {
        flash('error', 'Pick a month first — the format is YYYY-MM.');
        redirect('/admin/runs');
    }
    if (Desk::liveRunByPeriod($period) !== null) {
        flash('info', 'A run already exists for ' . Desk::periodLabel($period) .
            '. Open it, or void it first if the month has to be rebuilt.');
        redirect('/admin/runs');
    }
    $roster = Desk::roster('active');
    if (!$roster) {
        flash('error', 'There are no active employees to payslip. Add the roster first.');
        redirect('/admin/employees/new');
    }

    $bands = Desk::bands();
    $rules = Desk::rules();
    $note = trim(mb_substr((string)($_POST['note'] ?? ''), 0, 255));

    $runId = Db::transaction(function () use ($period, $roster, $bands, $rules, $note) {
        $id = Db::insert('payroll_runs', [
            'period' => $period,
            'status' => 'draft',
            'note' => $note,
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $now = date('Y-m-d H:i:s');
        foreach ($roster as $employee) {
            $r = Payroll::compute($employee, $bands, $rules);
            if (!empty($r['empty'])) {
                continue;
            }
            Db::insert('payslips', [
                'run_id' => $id,
                'employee_id' => (int)$employee['id'],
                'period' => $period,
                'employee_name' => (string)$employee['name'],
                'staff_no' => (string)$employee['staff_no'],
                'role_title' => (string)$employee['role_title'],
                'basic' => round($r['basic'], 2),
                'housing' => round($r['housing'], 2),
                'transport' => round($r['transport'], 2),
                'other' => round($r['other'], 2),
                'gross' => round($r['gross'], 2),
                'annual_gross' => round($r['annual_gross'], 2),
                'cra' => round($r['cra'], 2),
                'rent_relief' => round($r['rent_relief'], 2),
                'taxable_annual' => round($r['taxable_annual'], 2),
                'taxable_monthly' => round($r['taxable_monthly'], 2),
                'paye' => round($r['paye'], 2),
                'pension' => round($r['pension'], 2),
                'nhf' => round($r['nhf'], 2),
                'net' => round($r['net'], 2),
                'employer_pension' => round($r['employer_pension'], 2),
                'employer_nhf' => round($r['employer_nhf'], 2),
                'employer_cost' => round($r['employer_cost'], 2),
                'marginal_rate' => $r['marginal_rate'],
                'bands_json' => json_encode($bands),
                'rules_json' => json_encode($rules),
                'created_at' => $now,
            ]);
        }
        $totals = Desk::totals($id);
        Db::update('payroll_runs', [
            'headcount' => (int)$totals['headcount'],
            'gross_total' => round((float)$totals['gross_total'], 2),
            'paye_total' => round((float)$totals['paye_total'], 2),
            'pension_total' => round((float)$totals['pension_total'], 2),
            'nhf_total' => round((float)$totals['nhf_total'], 2),
            'net_total' => round((float)$totals['net_total'], 2),
            'cost_total' => round((float)$totals['cost_total'], 2),
        ], 'id = ?', [$id]);
        return $id;
    });

    Desk::audit('run.create', 'Run #' . $runId, ['period' => $period, 'headcount' => count($roster)]);
    flash('success', 'Draft run built for ' . Desk::periodLabel($period) . '. Check it, then lock it.');
    redirect('/admin/runs/' . $runId);
}

function admin_run_view(): void
{
    admin_guard();
    $id = (int)($_GET['id'] ?? 0);
    $run = Desk::run($id);
    if ($run === null) {
        http_response_code(404);
        view('errors/404', ['title' => 'Run not found']);
        return;
    }
    view('admin/run', [
        'title' => Desk::periodLabel((string)$run['period']) . ' — PayRite',
        'bodyClass' => 'admin',
        'run' => $run,
        'slips' => Desk::slips($id),
        'totals' => Desk::totals($id),
        'stored' => ['gross_total' => $run['gross_total'], 'paye_total' => $run['paye_total'],
                     'net_total' => $run['net_total'], 'cost_total' => $run['cost_total']],
    ]);
}

function admin_run_lock(): void
{
    admin_guard();
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $run = Desk::run($id);
    if ($run === null || $run['status'] !== 'draft') {
        flash('error', 'Only a draft run can be locked.');
        redirect($run === null ? '/admin/runs' : '/admin/runs/' . $id);
    }
    if ((int)$run['headcount'] === 0) {
        flash('error', 'This run has no payslips, so there is nothing to lock.');
        redirect('/admin/runs/' . $id);
    }
    Db::update('payroll_runs', ['status' => 'locked', 'locked_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    Desk::audit('run.lock', 'Run #' . $id, ['period' => $run['period'], 'net' => $run['net_total']]);
    flash('success', Desk::periodLabel((string)$run['period']) . ' is locked. Payslips are now visible to staff.');
    redirect('/admin/runs/' . $id);
}

function admin_run_void(): void
{
    admin_guard();
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $run = Desk::run($id);
    if ($run === null || $run['status'] === 'void') {
        flash('error', 'That run cannot be voided.');
        redirect($run === null ? '/admin/runs' : '/admin/runs/' . $id);
    }
    // The slips stay so the audit trail shows what was drafted; the run is
    // marked void and stops appearing on anyone's payslip list.
    Db::update('payroll_runs', ['status' => 'void', 'note' => trim((string)($_POST['note'] ?? '')) ?: 'Voided'], 'id = ?', [$id]);
    Desk::audit('run.void', 'Run #' . $id, ['period' => $run['period']]);
    flash('info', 'Run voided. A new draft can be built for the same month.');
    redirect('/admin/runs/' . $id);
}

function admin_run_notify(): void
{
    admin_guard();
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $run = Desk::run($id);
    if ($run === null) {
        flash('error', 'Run not found.');
        redirect('/admin/runs');
    }
    /* The page only offers this button on a locked run, and the rule belongs
       here too: telling someone their payslip is ready when the month is still
       a draft — or has been withdrawn — sends them to a page they cannot open. */
    if ($run['status'] !== 'locked') {
        flash('error', 'Only a locked run can be sent to staff.');
        redirect('/admin/runs/' . $id);
    }
    $rows = Db::all(
        'SELECT u.email, u.name, p.employee_name, p.period, p.gross, p.paye, p.pension, p.nhf, p.net
           FROM payslips p
           JOIN employees e ON e.id = p.employee_id
           JOIN users u ON u.id = e.user_id
          WHERE p.run_id = ? AND u.is_active = 1',
        [$id]
    );
    if (!$rows) {
        flash('info', 'No one on this run has a linked staff login yet, so there was nobody to notify.');
        redirect('/admin/runs/' . $id);
    }
    foreach ($rows as $row) {
        Mailer::send((string)$row['email'], 'Your ' . Desk::periodLabel((string)$row['period']) . ' payslip is ready',
            Mailer::template('Payslip available',
                '<p>' . e($row['name']) . ',</p><p>Your payslip for <strong>' . e(Desk::periodLabel((string)$row['period'])) .
                '</strong> has been locked and is waiting in PayRite.</p>' .
                '<table><tr><td>Gross</td><td>' . e(money((float)$row['gross'])) . '</td></tr>' .
                '<tr><td>PAYE</td><td>' . e(money((float)$row['paye'])) . '</td></tr>' .
                '<tr><td>Pension</td><td>' . e(money((float)$row['pension'])) . '</td></tr>' .
                '<tr><td>NHF</td><td>' . e(money((float)$row['nhf'])) . '</td></tr>' .
                '<tr><td><strong>Net pay</strong></td><td><strong>' . e(money((float)$row['net'])) . '</strong></td></tr></table>',
                'Open my payslip', (string)env('APP_URL', '') . '/payslips'),
            '', 'payslip_ready');
    }
    Desk::audit('run.notify', 'Run #' . $id, ['notified' => count($rows)]);
    flash('success', count($rows) . ' staff notified.');
    redirect('/admin/runs/' . $id);
}

/* ---------- rules ---------- */

function admin_rules(): void
{
    admin_guard();
    view('admin/rules', [
        'title' => 'Bands and reliefs — PayRite',
        'bodyClass' => 'admin',
        'bands' => Db::all('SELECT * FROM pay_bands ORDER BY band_order'),
        'rates' => Db::all("SELECT * FROM rates WHERE tool = 'payroll' ORDER BY id"),
        'ladder' => home_ladder(),
    ]);
}

function admin_rules_save(): void
{
    admin_guard();
    verify_csrf();
    $errors = [];

    $orders = array_map('intval', (array)($_POST['band_order'] ?? []));
    $widths = (array)($_POST['slice_width'] ?? []);
    $rates = (array)($_POST['rate'] ?? []);
    if (count($orders) < 2) {
        $errors['bands'] = 'A PAYE table needs at least two bands.';
    } else {
        foreach ($orders as $i => $order) {
            $rate = (float)($rates[$i] ?? -1);
            $width = trim((string)($widths[$i] ?? ''));
            if ($rate < 0 || $rate > 60) {
                $errors['bands'] = 'Every band rate has to sit between 0% and 60%.';
            }
            if ($width !== '' && (!is_numeric($width) || (float)$width <= 0)) {
                $errors['bands'] = 'Band widths must be whole naira amounts, or blank for the last band.';
            }
        }
        if (isset($errors['bands'])) {
            // The last slice must be open-ended or a top earner falls off the table untaxed.
            $lastWidth = trim((string)($widths[count($orders) - 1] ?? ''));
            if ($lastWidth !== '') {
                $errors['bands'] = 'Leave the width of the final band blank — it means "everything above".';
            }
        }
    }

    $constants = [];
    foreach ((array)($_POST['rule'] ?? []) as $code => $value) {
        if (!array_key_exists($code, Payroll::DEFAULT_RULES)) {
            continue;
        }
        if (!is_numeric($value) || (float)$value < 0) {
            $errors['rules'] = 'Relief and contribution constants must be zero or positive.';
            continue;
        }
        $constants[$code] = (float)$value;
    }

    if ($errors) {
        remember_errors($errors);
        Desk::audit('rules.rejected', '', $errors);
        flash('error', 'Nothing was changed — the table still holds the figures it had.');
        redirect('/admin/rules');
    }

    Db::transaction(function () use ($orders, $widths, $rates, $constants) {
        $now = date('Y-m-d H:i:s');
        foreach ($orders as $i => $order) {
            $width = trim((string)($widths[$i] ?? ''));
            Db::update('pay_bands', [
                'band_order' => $order,
                'slice_width' => $width === '' ? null : round((float)$width, 2),
                'rate' => round((float)$rates[$i], 2),
                'updated_at' => $now,
                'updated_by' => Auth::id(),
            ], 'id = ?', [(int)($_POST['band_id'][$i] ?? 0)]);
        }
        foreach ($constants as $code => $value) {
            Db::run("UPDATE rates SET value = ?, updated_at = ?, updated_by = ?
                      WHERE tool = 'payroll' AND code = ?", [$value, $now, Auth::id(), $code]);
        }
    });

    Desk::refresh();
    Desk::audit('rules.save', 'Bands and reliefs', ['bands' => count($orders), 'rules' => count($constants)]);
    flash('success', 'Saved. Every payslip built from now on uses these figures — locked runs keep theirs.');
    redirect('/admin/rules');
}

/* ---------- accounts ---------- */

function admin_staff(): void
{
    admin_guard();
    view('admin/staff', [
        'title' => 'Staff logins — PayRite',
        'bodyClass' => 'admin',
        'accounts' => Desk::staffAccounts(),
        'unlinked' => Desk::unlinkedEmployees(),
        'employees' => Db::all('SELECT id, staff_no, name FROM employees ORDER BY name'),
    ]);
}

function admin_staff_status(): void
{
    admin_guard();
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $user = Db::one('SELECT * FROM users WHERE id = ? AND role <> ?', [$id, 'admin']);
    if ($user === null) {
        flash('error', 'That account could not be found.');
        redirect('/admin/staff');
    }
    $active = (int)$user['is_active'] === 1 ? 0 : 1;
    Db::update('users', ['is_active' => $active], 'id = ?', [$id]);
    Desk::audit('staff.status', 'User #' . $id, ['is_active' => $active]);
    flash('success', $user['name'] . ' can ' . ($active ? '' : 'no longer ') . 'sign in.');
    redirect('/admin/staff');
}

function admin_staff_link(): void
{
    admin_guard();
    verify_csrf();
    $userId = (int)($_POST['id'] ?? 0);
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $user = Db::one('SELECT * FROM users WHERE id = ? AND role <> ?', [$userId, 'admin']);
    $employee = Desk::employee($employeeId);
    if ($user === null || $employee === null) {
        flash('error', 'Pick a real staff account and a roster row to link them.');
        redirect('/admin/staff');
    }

    // One person, one record: clear any previous owner of that roster row first.
    Db::run('UPDATE employees SET user_id = NULL WHERE user_id = ? OR id = ?', [$userId, $employeeId]);
    Db::update('employees', ['user_id' => $userId, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$employeeId]);
    Desk::audit('staff.link', 'User #' . $userId, ['employee' => $employee['staff_no']]);
    flash('success', $employee['name'] . ' is now linked to ' . $user['email'] . '.');
    redirect('/admin/staff');
}

/* ---------- trail ---------- */

function admin_audit(): void
{
    admin_guard();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per = 30;
    $total = (int)Db::value('SELECT COUNT(*) FROM audit_log');
    $p = paginate($total, $per, $page);
    view('admin/audit', [
        'title' => 'Audit trail — PayRite',
        'bodyClass' => 'admin',
        'rows' => Db::all('SELECT a.*, u.name AS actor FROM audit_log a
                             LEFT JOIN users u ON u.id = a.actor_id
                            ORDER BY a.id DESC LIMIT ' . $p['limit'] . ' OFFSET ' . $p['offset']),
        'pager' => $p,
    ]);
}

function admin_outbox(): void
{
    admin_guard();
    view('admin/outbox', [
        'title' => 'Message log — PayRite',
        'bodyClass' => 'admin',
        'rows' => Db::all('SELECT * FROM email_outbox ORDER BY id DESC LIMIT 40'),
        'counts' => Db::all("SELECT status, COUNT(*) AS n FROM email_outbox GROUP BY status"),
        'mailing' => (bool)env('MAIL_ENABLED', false),
    ]);
}
