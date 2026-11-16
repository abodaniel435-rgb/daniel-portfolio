<?php
declare(strict_types=1);

/**
 * Demo domain data: a roster of eight, three of them linked to the staff
 * accounts, last month's payroll locked so there is something to read, and this
 * month's left as a draft so the lock step is still to be done by hand.
 *
 * The slips are produced by the same Payroll::compute() the application uses —
 * nothing here is typed in by hand, so the demo cannot contradict the engine.
 */

return function (): void {
    $now = date('Y-m-d H:i:s');

    $roster = [
        ['PR-001', 'Ada Okonkwo', 'Head of Operations', 'Management', 900000, 250000, 200000, 50000, 6000000, 1, 8, 'active', 'ada@payrite.test'],
        ['PR-002', 'Tunde Bakare', 'Field Supervisor', 'Operations', 380000, 110000, 90000, 20000, 2400000, 1, 8, 'active', 'tunde@payrite.test'],
        ['PR-003', 'Ibrahim Musa', 'Warehouse Lead', 'Logistics', 260000, 70000, 60000, 0, 1800000, 0, 8, 'active', 'ibrahim@payrite.test'],
        ['PR-004', 'Chiamaka Eze', 'Accountant', 'Finance', 520000, 140000, 110000, 30000, 3600000, 1, 10, 'active', ''],
        ['PR-005', 'Segun Adewale', 'Driver', 'Logistics', 150000, 40000, 60000, 0, 900000, 1, 8, 'active', ''],
        ['PR-006', 'Fatima Bello', 'Support Lead', 'Customers', 310000, 85000, 70000, 15000, 2100000, 1, 8, 'active', ''],
        ['PR-007', 'Emeka Nwosu', 'Storekeeper', 'Operations', 175000, 45000, 55000, 0, 0, 0, 8, 'paused', ''],
        ['PR-008', 'Grace Adeyemi', 'Recruiter (contract)', 'People', 400000, 0, 150000, 0, 4800000, 1, 5, 'left', ''],
    ];

    $ids = [];
    foreach ($roster as $row) {
        [$no, $name, $role, $dept, $basic, $housing, $transport, $other, $rent, $claim, $pension, $status] = $row;
        $ids[$no] = Db::insert('employees', [
            'staff_no' => $no,
            'name' => $name,
            'role_title' => $role,
            'department' => $dept,
            'basic' => $basic,
            'housing' => $housing,
            'transport' => $transport,
            'other' => $other,
            'annual_rent' => $rent,
            'claim_rent' => $claim,
            'pension_rate' => $pension,
            'status' => $status,
            'query_note' => $no === 'PR-003' ? 'My rent receipt says ₦1.98m, not ₦1.8m — can the relief be corrected?' : '',
            'joined_at' => date('Y-m-d', strtotime('-' . (400 + array_search($no, array_column($roster, 0), true) * 30) . ' days')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    foreach ($roster as $index => $row) {
        $email = $row[12] ?? '';
        if ($email === '') {
            continue;
        }
        $userId = Db::value('SELECT id FROM users WHERE email = ?', [$email]);
        if ($userId) {
            Db::update('employees', ['user_id' => (int)$userId], 'id = ?', [$ids[$row[0]]]);
        }
    }

    $bands = Desk::bands();
    $rules = Desk::rules();

    foreach ([date('Y-m', strtotime('first day of last month')), date('Y-m')] as $i => $period) {
        if (Desk::runByPeriod($period) !== null) {
            continue;
        }
        $status = $i === 0 ? 'locked' : 'draft';
        $runId = Db::insert('payroll_runs', [
            'period' => $period,
            'status' => $status,
            'note' => $status === 'locked' ? 'Demo run — salaries as rostered.' : 'Draft: check the rent relief correction before locking.',
            'created_by' => Db::value("SELECT id FROM users WHERE role = 'admin'"),
            'created_at' => $now,
        ]);

        $people = Db::all('SELECT * FROM employees WHERE status = ? ORDER BY name', ['active']);
        foreach ($people as $employee) {
            $r = Payroll::compute($employee, $bands, $rules);
            if (!empty($r['empty'])) {
                continue;
            }
            Db::insert('payslips', [
                'run_id' => $runId,
                'employee_id' => (int)$employee['id'],
                'period' => $period,
                'employee_name' => $employee['name'],
                'staff_no' => $employee['staff_no'],
                'role_title' => $employee['role_title'],
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

        $totals = Desk::totals((int)$runId);
        Db::update('payroll_runs', [
            'headcount' => (int)$totals['headcount'],
            'gross_total' => round((float)$totals['gross_total'], 2),
            'paye_total' => round((float)$totals['paye_total'], 2),
            'pension_total' => round((float)$totals['pension_total'], 2),
            'nhf_total' => round((float)$totals['nhf_total'], 2),
            'net_total' => round((float)$totals['net_total'], 2),
            'cost_total' => round((float)$totals['cost_total'], 2),
            'locked_at' => $status === 'locked' ? $now : null,
        ], 'id = ?', [(int)$runId]);
    }

    /* A handful of public calculator questions, so the counter on the landing
       page and the recent list on the desk are not empty on a fresh demo. They
       are ordinary packages put through the same engine, not typed-in figures. */
    foreach (array_slice($roster, 0, 4) as $row) {
        $package = [
            'basic' => $row[4], 'housing' => $row[5], 'transport' => $row[6], 'other' => $row[7],
            'annual_rent' => $row[8], 'claim_rent' => (bool)$row[9], 'pension_rate' => $row[10],
        ];
        $asked = Payroll::compute($package, $bands, $rules);
        if (!empty($asked['empty'])) {
            continue;
        }
        Desk::logCalculation('paye', $row[1] . ' (demo question)', $package, [
            'gross' => round($asked['gross'], 2), 'paye' => round($asked['paye'], 2),
            'net' => round($asked['net'], 2), 'employer_cost' => round($asked['employer_cost'], 2),
            'effective_rate' => round($asked['effective_rate'], 2),
        ], '₦' . number_format($asked['net'], 2));
    }

    Desk::audit('demo.seed', 'Roster and two runs');
};
