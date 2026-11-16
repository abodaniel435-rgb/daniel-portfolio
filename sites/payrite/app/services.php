<?php
declare(strict_types=1);

/**
 * PayRite services — everything that touches the database lives here, so the
 * controllers stay about requests and app/engines/Payroll.php stays about
 * arithmetic.
 */

require_once __DIR__ . '/engines/Payroll.php';

final class Desk
{
    /** @var array<int,array{slice_width:?float,rate:float}>|null */
    private static ?array $bands = null;

    /** @var array<string,float>|null */
    private static ?array $rules = null;

    /** @return array<int,array{slice_width:?float,rate:float}> */
    public static function bands(): array
    {
        if (self::$bands === null) {
            $rows = Db::all('SELECT slice_width, rate FROM pay_bands ORDER BY band_order');
            $found = [];
            foreach ($rows as $row) {
                $found[] = ['slice_width' => $row['slice_width'] === null ? null : (float)$row['slice_width'],
                            'rate' => (float)$row['rate']];
            }
            // A fresh install has been seeded, but if the table was emptied the
            // engine's published bands keep the arithmetic correct anyway.
            self::$bands = Payroll::bands($found ?: Payroll::DEFAULT_BANDS);
        }
        return self::$bands;
    }

    /** @return array<string,float> */
    public static function rules(): array
    {
        if (self::$rules === null) {
            $rows = Db::all("SELECT code, value FROM rates WHERE tool = 'payroll'");
            $found = [];
            foreach ($rows as $row) {
                $found[(string)$row['code']] = (float)$row['value'];
            }
            self::$rules = Payroll::rules($found);
        }
        return self::$rules;
    }

    public static function refresh(): void
    {
        self::$bands = null;
        self::$rules = null;
    }

    public static function compute(array $package): array
    {
        return Payroll::compute($package, self::bands(), self::rules());
    }

    public static function audit(string $action, string $target = '', array $meta = []): void
    {
        try {
            Db::insert('audit_log', [
                'actor_id' => Auth::id(),
                'action' => $action,
                'target' => mb_substr($target, 0, 120),
                'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            // An audit write must never be the reason a payroll run failed.
        }
    }

    /* ---------- roster ---------- */

    public static function roster(string $status = ''): array
    {
        if ($status === '') {
            return Db::all('SELECT * FROM employees ORDER BY FIELD(status, ?, ?, ?), name', ['active', 'paused', 'left']);
        }
        return Db::all('SELECT * FROM employees WHERE status = ? ORDER BY name', [$status]);
    }

    public static function employee(int $id): ?array
    {
        return Db::one('SELECT * FROM employees WHERE id = ?', [$id]);
    }

    public static function employeeByUser(int $userId): ?array
    {
        return Db::one('SELECT * FROM employees WHERE user_id = ?', [$userId]);
    }

    public static function nextStaffNo(): string
    {
        $last = Db::value("SELECT staff_no FROM employees WHERE staff_no LIKE 'PR-%' ORDER BY id DESC LIMIT 1");
        $n = $last ? (int)substr((string)$last, 3) + 1 : 1;
        return 'PR-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
    }

    /* ---------- payroll runs ---------- */

    public static function periodLabel(string $period): string
    {
        $time = strtotime($period . '-01');
        return $time ? date('F Y', $time) : $period;
    }

    public static function validPeriod(string $period): bool
    {
        return preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/', $period) === 1;
    }

    public static function run(int $id): ?array
    {
        return Db::one('SELECT * FROM payroll_runs WHERE id = ?', [$id]);
    }

    /** The most recent attempt at a month, voided ones included. */
    public static function runByPeriod(string $period): ?array
    {
        return Db::one('SELECT * FROM payroll_runs WHERE period = ? ORDER BY id DESC LIMIT 1', [$period]);
    }

    /**
     * A month can be paid once. `period` is not UNIQUE in the table because a
     * voided run has to stay as evidence while the same month is rebuilt — so
     * "is there already a live run for this month" is asked here instead, and
     * the index on (period, status) makes it a cheap lookup.
     */
    public static function liveRunByPeriod(string $period): ?array
    {
        return Db::one("SELECT * FROM payroll_runs WHERE period = ? AND status <> 'void' ORDER BY id DESC LIMIT 1", [$period]);
    }

    public static function runs(int $limit = 24): array
    {
        return Db::all('SELECT * FROM payroll_runs ORDER BY period DESC, id DESC LIMIT ' . (int)$limit);
    }

    public static function slips(int $runId): array
    {
        return Db::all('SELECT * FROM payslips WHERE run_id = ? ORDER BY employee_name, id', [$runId]);
    }

    public static function slip(int $id): ?array
    {
        return Db::one('SELECT * FROM payslips WHERE id = ?', [$id]);
    }

    /**
     * A staff member may only ever see a slip that belongs to their own record,
     * and only once the month has been locked. A draft is an internal working —
     * the figures are still being checked — so publishing it to the employee
     * would announce a payslip that may not survive the review.
     */
    public static function slipForUser(int $userId, int $slipId): ?array
    {
        return Db::one(
            'SELECT p.* FROM payslips p
               JOIN employees e ON e.id = p.employee_id
               JOIN payroll_runs r ON r.id = p.run_id
              WHERE p.id = ? AND e.user_id = ? AND r.status = ?',
            [$slipId, $userId, 'locked']
        );
    }

    /** Everything a signed-in worker is owed: their own slips, locked months only. */
    public static function slipsForUser(int $userId, int $limit = 36): array
    {
        return Db::all(
            'SELECT p.* FROM payslips p
               JOIN employees e ON e.id = p.employee_id
               JOIN payroll_runs r ON r.id = p.run_id
              WHERE e.user_id = ? AND r.status = ?
              ORDER BY p.period DESC, p.id DESC
              LIMIT ' . (int)$limit,
            [$userId, 'locked']
        );
    }

    /**
     * Recompute a run's totals from the stored slips. The dashboard shows these
     * rather than trusting the columns written at run time, so a hand-edited row
     * cannot silently change what the business thinks it owes.
     */
    public static function totals(int $runId): array
    {
        $row = Db::one(
            'SELECT COUNT(*) AS headcount, COALESCE(SUM(gross),0) AS gross_total,
                    COALESCE(SUM(paye),0) AS paye_total, COALESCE(SUM(pension),0) AS pension_total,
                    COALESCE(SUM(nhf),0) AS nhf_total, COALESCE(SUM(net),0) AS net_total,
                    COALESCE(SUM(employer_cost),0) AS cost_total
               FROM payslips WHERE run_id = ?',
            [$runId]
        ) ?: [];
        return $row + ['headcount' => 0, 'gross_total' => 0, 'paye_total' => 0, 'pension_total' => 0,
                       'nhf_total' => 0, 'net_total' => 0, 'cost_total' => 0];
    }

    /* ---------- accounts ---------- */

    public static function staffAccounts(): array
    {
        return Db::all(
            'SELECT u.*, e.id AS employee_id, e.staff_no, e.name AS employee_name, e.status AS employee_status
               FROM users u
               LEFT JOIN employees e ON e.user_id = u.id
              WHERE u.role <> ?
              ORDER BY u.name',
            ['admin']
        );
    }

    public static function unlinkedEmployees(): array
    {
        return Db::all('SELECT id, staff_no, name FROM employees WHERE user_id IS NULL ORDER BY name');
    }

    /* ---------- calculator log ---------- */

    public static function logCalculation(string $tool, string $label, array $inputs, array $result, string $headline): void
    {
        try {
            Db::insert('calculations', [
                'user_id' => Auth::id(),
                'tool' => $tool,
                'label' => mb_substr($label, 0, 160),
                'inputs' => json_encode($inputs, JSON_UNESCAPED_UNICODE),
                'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
                'headline' => mb_substr($headline, 0, 60),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            // The figure has already been computed; logging it is secondary.
        }
    }

    public static function recentCalculations(int $limit = 12): array
    {
        return Db::all('SELECT * FROM calculations ORDER BY id DESC LIMIT ' . (int)$limit);
    }

    /* ---------- dashboard figures ---------- */

    public static function summary(): array
    {
        return [
            'roster' => (int)Db::value("SELECT COUNT(*) FROM employees WHERE status = 'active'"),
            'linked' => (int)Db::value('SELECT COUNT(*) FROM employees WHERE user_id IS NOT NULL'),
            'runs' => (int)Db::value('SELECT COUNT(*) FROM payroll_runs'),
            'drafts' => (int)Db::value("SELECT COUNT(*) FROM payroll_runs WHERE status = 'draft'"),
            'paye_ytd' => (float)Db::value("SELECT COALESCE(SUM(paye_total),0) FROM payroll_runs WHERE status <> 'void'"),
            'net_ytd' => (float)Db::value("SELECT COALESCE(SUM(net_total),0) FROM payroll_runs WHERE status <> 'void'"),
            'queries' => (int)Db::value("SELECT COUNT(*) FROM employees WHERE query_note <> ''"),
            'band_count' => (int)Db::value('SELECT COUNT(*) FROM pay_bands'),
        ];
    }
}
