<?php
declare(strict_types=1);

/**
 * The member workspace. Every handler here resolves the company from the
 * signed-in user id rather than from the request, so no route can be pointed
 * at another business's payroll by editing a URL.
 */

function work_guard(): array
{
    Auth::requireLogin();
    return Desk::myCompany() ?? [];
}

function work_dashboard(): void
{
    $company = work_guard();
    $userId = (int)Auth::id();

    view('member/dashboard', [
        'title' => 'Dashboard — NairaDesk',
        'company' => $company,
        'headcount' => $company ? (int)Db::value('SELECT COUNT(*) FROM employees WHERE company_id = ?', [(int)$company['id']]) : 0,
        'runs' => $company
            ? Db::all('SELECT * FROM pay_runs WHERE company_id = ? ORDER BY created_at DESC LIMIT 6', [(int)$company['id']])
            : [],
        'recent' => Db::all(
            'SELECT id, tool, label, headline, created_at FROM calculations WHERE user_id = ? ORDER BY id DESC LIMIT 8',
            [$userId]
        ),
    ]);
}

function work_company_create(): void
{
    verify_csrf();
    Auth::requireLogin();
    $name = trim((string)($_POST['name'] ?? ''));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 160) {
        remember_errors(['name' => 'Enter a business name between 2 and 160 characters.']);
        redirect('/dashboard');
    }
    if (Desk::myCompany() !== null) {
        flash('error', 'This account already has a workspace.');
        redirect('/dashboard');
    }
    $id = Db::insert('companies', [
        'owner_id' => (int)Auth::id(),
        'name' => $name,
        'tin' => trim((string)($_POST['tin'] ?? '')),
        'state' => trim((string)($_POST['state'] ?? 'Lagos')),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    Desk::audit('company.create', 'Company #' . $id, ['name' => $name]);
    flash('success', 'Workspace created.');
    redirect('/staff');
}

function work_staff(): void
{
    $company = work_guard();
    $employees = $company
        ? Db::all('SELECT * FROM employees WHERE company_id = ? ORDER BY full_name', [(int)$company['id']])
        : [];

    $cost = 0.0;
    foreach ($employees as $employee) {
        $cost += Payroll::compute(work_employee_input($employee), Desk::rates())['employerCost'] ?? 0;
    }

    view('member/staff', [
        'title' => 'Staff — NairaDesk',
        'company' => $company,
        'employees' => $employees,
        'monthlyCost' => $cost,
    ]);
}

function work_staff_add(): void
{
    verify_csrf();
    $company = work_guard();
    if ($company === []) {
        redirect('/dashboard');
    }

    $v = (new Validator($_POST))
        ->required('full_name', 'basic')
        ->max('full_name', 160)
        ->integer('basic', 1, 100000000);
    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($_POST);
        redirect('/staff');
    }

    $id = Db::insert('employees', [
        'company_id' => (int)$company['id'],
        'full_name' => (string)$v->value('full_name'),
        'staff_no' => mb_substr(trim((string)($_POST['staff_no'] ?? '')), 0, 40),
        'basic' => (float)$v->value('basic'),
        'housing' => work_amount($_POST['housing'] ?? 0),
        'transport' => work_amount($_POST['transport'] ?? 0),
        'other' => work_amount($_POST['other'] ?? 0),
        'use_pension' => isset($_POST['use_pension']) ? 1 : 0,
        'pension_rate' => min(24.0, max(0.0, work_amount($_POST['pension_rate'] ?? 8))),
        'use_nhf' => isset($_POST['use_nhf']) ? 1 : 0,
        'use_rent' => isset($_POST['use_rent']) ? 1 : 0,
        'annual_rent' => work_amount($_POST['annual_rent'] ?? 0),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    Desk::audit('employee.add', 'Employee #' . $id, ['company' => (int)$company['id']]);
    flash('success', (string)$v->value('full_name') . ' added to the roster.');
    redirect('/staff');
}

function work_staff_delete(): void
{
    verify_csrf();
    $company = work_guard();
    $id = (int)($_POST['id'] ?? 0);
    if ($company !== [] && Desk::employee($id, (int)$company['id']) !== null) {
        Db::run('DELETE FROM employees WHERE id = ? AND company_id = ?', [$id, (int)$company['id']]);
        Desk::audit('employee.delete', 'Employee #' . $id);
        flash('success', 'Removed from the roster.');
    }
    redirect('/staff');
}

function work_calculate(): void
{
    $company = work_guard();
    $single = [];
    if ($company !== [] && !empty($_GET['employee'])) {
        $employee = Desk::employee((int)$_GET['employee'], (int)$company['id']);
        if ($employee !== null) {
            $single = work_employee_input($employee);
            $single['full_name'] = $employee['full_name'];
        }
    }

    view('member/calculate', [
        'title' => 'Calculate PAYE — NairaDesk',
        'company' => $company,
        'input' => $single,
        'bands' => Payroll::bands(Desk::rates()),
    ]);
}

/**
 * POST /api/payroll — the same engine the run uses, exposed as JSON.
 * Requires a signed-in session and a valid CSRF token; the calculation is
 * written to `calculations` so it is auditable afterwards.
 */
function work_payroll_api(): void
{
    verify_csrf();
    Auth::requireLogin();

    $input = work_validate($_POST);
    if (!empty($input['_errors'])) {
        json_response(['ok' => false, 'errors' => $input['_errors']], 422);
    }

    $company = Desk::myCompany();
    $result = Payroll::compute($input, Desk::rates());
    $id = Desk::logCalculation('payroll', (string)($_POST['label'] ?? 'PAYE calculation'), $input, $result,
        $company ? (int)$company['id'] : null);

    json_response(['ok' => true, 'id' => $id, 'result' => $result]);
}

function work_runs(): void
{
    $company = work_guard();
    view('member/runs', [
        'title' => 'Pay runs — NairaDesk',
        'company' => $company,
        'runs' => $company
            ? Db::all('SELECT * FROM pay_runs WHERE company_id = ? ORDER BY period DESC', [(int)$company['id']])
            : [],
    ]);
}

function work_run_create(): void
{
    verify_csrf();
    $company = work_guard();
    if ($company === []) {
        redirect('/dashboard');
    }

    $period = trim((string)($_POST['period'] ?? ''));
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
        remember_errors(['period' => 'Choose a month first.']);
        redirect('/runs');
    }

    // (company, period) is unique in MySQL. Rather than let a double submit
    // surface as a database error, hand back the run that already exists.
    $existing = Db::one('SELECT id FROM pay_runs WHERE company_id = ? AND period = ?',
        [(int)$company['id'], $period]);
    if ($existing !== null) {
        flash('info', 'A run for ' . $period . ' is already stored — opening it.');
        redirect('/runs/' . (int)$existing['id']);
    }

    $employees = Db::all('SELECT * FROM employees WHERE company_id = ? ORDER BY full_name', [(int)$company['id']]);
    if (!$employees) {
        flash('error', 'Add at least one member of staff before running payroll.');
        redirect('/staff');
    }

    $rates = Desk::rates();
    $totals = ['gross' => 0.0, 'paye' => 0.0, 'pension' => 0.0, 'nhf' => 0.0, 'net' => 0.0, 'cost' => 0.0];
    $lines = [];

    foreach ($employees as $employee) {
        $result = Payroll::compute(work_employee_input($employee), $rates);
        if (!empty($result['empty'])) {
            continue;
        }
        $totals['gross'] += $result['gross'];
        $totals['paye'] += $result['payeMonthly'];
        $totals['pension'] += $result['pensionMonthly'] + $result['employerPension'];
        $totals['nhf'] += $result['nhfMonthly'] + $result['employerNhf'];
        $totals['net'] += $result['net'];
        $totals['cost'] += $result['employerCost'];
        $lines[] = [$employee, $result];
    }

    $runId = 0;
    Db::transaction(function () use ($company, $period, $totals, $lines, &$runId): void {
        $runId = Db::insert('pay_runs', [
            'company_id' => (int)$company['id'],
            'period' => $period,
            'gross_total' => round($totals['gross'], 2),
            'paye_total' => round($totals['paye'], 2),
            'pension_total' => round($totals['pension'], 2),
            'nhf_total' => round($totals['nhf'], 2),
            'net_total' => round($totals['net'], 2),
            'employer_cost_total' => round($totals['cost'], 2),
            'headcount' => count($lines),
            'created_by' => (int)Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($lines as [$employee, $result]) {
            Db::insert('pay_run_lines', [
                'run_id' => $runId,
                'employee_id' => (int)$employee['id'],
                'gross' => $result['gross'],
                'taxable_annual' => $result['taxable'],
                'paye_monthly' => $result['payeMonthly'],
                'pension_monthly' => $result['pensionMonthly'],
                'nhf_monthly' => $result['nhfMonthly'],
                'net' => $result['net'],
                'employer_cost' => $result['employerCost'],
                'marginal_rate' => $result['marginal'],
                'detail' => json_encode($result, JSON_UNESCAPED_UNICODE),
            ]);
        }
    });

    Desk::audit('run.create', 'Run #' . $runId, ['period' => $period, 'headcount' => count($lines)]);
    flash('success', 'Payroll for ' . $period . ' is ready.');
    redirect('/runs/' . $runId);
}

function work_run_show(): void
{
    $company = work_guard();
    $run = Db::one('SELECT * FROM pay_runs WHERE id = ? AND company_id = ?',
        [(int)($_GET['id'] ?? 0), (int)($company['id'] ?? 0)]);
    if ($run === null) {
        flash('error', 'That pay run does not exist in your workspace.');
        redirect('/runs');
    }

    view('member/run', [
        'title' => 'Pay run ' . $run['period'] . ' — NairaDesk',
        'company' => $company,
        'run' => $run,
        // LEFT JOIN, not JOIN: a frozen line outlives the roster row it came
        // from, and a run whose totals include a departed employee must still
        // show that salary rather than quietly drop it.
        'lines' => Db::all(
            'SELECT l.*, COALESCE(e.full_name, \'Former member of staff\') AS full_name, e.staff_no
             FROM pay_run_lines l LEFT JOIN employees e ON e.id = l.employee_id
             WHERE l.run_id = ? ORDER BY full_name',
            [(int)$run['id']]
        ),
    ]);
}

function work_run_delete(): void
{
    verify_csrf();
    $company = work_guard();
    $id = (int)($_POST['id'] ?? 0);
    $run = Db::one('SELECT id FROM pay_runs WHERE id = ? AND company_id = ?', [$id, (int)($company['id'] ?? 0)]);
    if ($run !== null) {
        Db::run('DELETE FROM pay_runs WHERE id = ?', [$id]);
        Desk::audit('run.delete', 'Run #' . $id);
        flash('success', 'Pay run deleted.');
    }
    redirect('/runs');
}

/** @param array<string,mixed> $employee */
function work_employee_input(array $employee): array
{
    return [
        'basic' => (float)$employee['basic'],
        'housing' => (float)$employee['housing'],
        'transport' => (float)$employee['transport'],
        'other' => (float)$employee['other'],
        'use_pension' => (bool)$employee['use_pension'],
        'pension_rate' => (float)$employee['pension_rate'],
        'use_nhf' => (bool)$employee['use_nhf'],
        'use_rent' => (bool)$employee['use_rent'],
        'annual_rent' => (float)$employee['annual_rent'],
    ];
}

/** @return array<string,mixed> */
function work_validate(array $post): array
{
    $v = (new Validator($post))
        ->required('basic')
        ->integer('basic', 1, 100000000, true);

    $errors = $v->passes() ? [] : $v->errors();
    if ($errors) {
        return ['_errors' => $errors];
    }

    return [
        'basic' => work_amount($post['basic']),
        'housing' => work_amount($post['housing'] ?? 0),
        'transport' => work_amount($post['transport'] ?? 0),
        'other' => work_amount($post['other'] ?? 0),
        'use_pension' => !empty($post['use_pension']),
        'pension_rate' => min(24.0, max(0.0, work_amount($post['pension_rate'] ?? 8))),
        'use_nhf' => !empty($post['use_nhf']),
        'use_rent' => !empty($post['use_rent']),
        'annual_rent' => work_amount($post['annual_rent'] ?? 0),
    ];
}

function work_amount(mixed $value): float
{
    if (is_numeric($value)) {
        return max(0.0, (float)$value);
    }
    $clean = preg_replace('/[^\d.\-]/', '', (string)$value);
    return $clean === null || $clean === '' ? 0.0 : max(0.0, (float)$clean);
}
