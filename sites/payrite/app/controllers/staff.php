<?php
declare(strict_types=1);

/**
 * The staff side. Everything here is scoped to the signed-in person's own
 * employee record — there is no id in a URL that lets one worker read another
 * payslip, because every query is joined through employees.user_id.
 */

function staff_guard(): void
{
    Auth::requireLogin();
    if (Auth::is('admin')) {
        redirect('/admin');
    }
}

function staff_payslips(): void
{
    staff_guard();
    $employee = Desk::employeeByUser((int)Auth::id());
    if ($employee === null) {
        view('staff/pending', ['title' => 'Not linked yet — PayRite', 'bodyClass' => 'staff']);
        return;
    }

    $slips = Desk::slipsForUser((int)Auth::id());
    $totals = ['gross' => 0.0, 'paye' => 0.0, 'net' => 0.0];
    foreach ($slips as $slip) {
        $totals['gross'] += (float)$slip['gross'];
        $totals['paye'] += (float)$slip['paye'];
        $totals['net'] += (float)$slip['net'];
    }

    view('staff/payslips', [
        'title' => 'My payslips — PayRite',
        'bodyClass' => 'staff',
        'employee' => $employee,
        'slips' => $slips,
        'totals' => $totals,
    ]);
}

function staff_payslip(): void
{
    staff_guard();
    $id = (int)($_GET['id'] ?? 0);
    $slip = Desk::slipForUser((int)Auth::id(), $id);
    if ($slip === null) {
        http_response_code(404);
        view('errors/404', ['title' => 'Payslip not found']);
        return;
    }

    view('staff/payslip', [
        'title' => Desk::periodLabel((string)$slip['period']) . ' payslip — PayRite',
        'bodyClass' => 'slip',
        'slip' => $slip,
        'steps' => json_decode((string)$slip['bands_json'], true) ?: [],
        'run' => Desk::run((int)$slip['run_id']),
    ]);
}

function staff_profile(): void
{
    staff_guard();
    $user = Auth::user();
    $employee = Desk::employeeByUser((int)Auth::id());

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        verify_csrf();
        $phone = trim(mb_substr((string)($_POST['phone'] ?? ''), 0, 40));
        $note = trim(mb_substr((string)($_POST['query_note'] ?? ''), 0, 255));

        Db::update('users', ['phone' => $phone], 'id = ?', [(int)Auth::id()]);
        if ($employee !== null && $note !== (string)$employee['query_note']) {
            Db::update('employees', ['query_note' => $note, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [(int)$employee['id']]);
            Desk::audit('staff.query', 'Employee #' . $employee['id'], ['note' => $note]);
        }
        flash('success', 'Saved. The payroll officer sees your note on the roster.');
        redirect('/profile');
    }

    view('staff/profile', [
        'title' => 'My package — PayRite',
        'bodyClass' => 'staff',
        'user' => $user,
        'employee' => $employee,
        'projection' => $employee === null ? null : Desk::compute($employee),
        'rules' => Desk::rules(),
    ]);
}
