<?php
declare(strict_types=1);

/**
 * NairaDesk services — the small amount of shared logic the controllers need:
 * statutory rates, the audit trail, and the rule that a member only ever sees
 * their own company.
 */

final class Desk
{
    private static ?array $rates = null;

    /** @return array<string,float> code => value, straight from the rates table */
    public static function rates(string $tool = 'payroll'): array
    {
        if (self::$rates === null) {
            $rows = Db::all('SELECT code, value FROM rates WHERE tool = ? ORDER BY id', [$tool]);
            self::$rates = [];
            foreach ($rows as $row) {
                self::$rates[(string)$row['code']] = (float)$row['value'];
            }
        }
        return self::$rates;
    }

    public static function refreshRates(): void
    {
        self::$rates = null;
    }

    public static function audit(string $action, string $target = '', array $meta = []): void
    {
        Db::insert('audit_log', [
            'actor_id' => Auth::id(),
            'action' => $action,
            'target' => $target,
            'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * The company the signed-in member owns. Ownership is resolved from the
     * session user id every request, so a hand-edited company_id in the URL
     * cannot reach another business's payroll.
     */
    public static function myCompany(): ?array
    {
        $id = Auth::id();
        if ($id === null) {
            return null;
        }
        $row = Db::one('SELECT * FROM companies WHERE owner_id = ? ORDER BY id LIMIT 1', [$id]);
        if ($row === null && Auth::is('admin')) {
            $row = Db::one('SELECT * FROM companies ORDER BY id LIMIT 1');
        }
        return $row;
    }

    public static function companyFor(int $companyId): ?array
    {
        $company = Db::one('SELECT * FROM companies WHERE id = ?', [$companyId]);
        if ($company === null) {
            return null;
        }
        if (!Auth::is('admin') && (int)$company['owner_id'] !== Auth::id()) {
            return null;
        }
        return $company;
    }

    public static function employee(int $employeeId, int $companyId): ?array
    {
        return Db::one('SELECT * FROM employees WHERE id = ? AND company_id = ?', [$employeeId, $companyId]);
    }

    /** @param array<string,mixed> $input */
    public static function logCalculation(string $tool, string $label, array $input, array $result, ?int $companyId = null): int
    {
        return Db::insert('calculations', [
            'user_id' => Auth::id(),
            'company_id' => $companyId,
            'tool' => $tool,
            'label' => mb_substr($label, 0, 160),
            'inputs' => json_encode($input, JSON_UNESCAPED_UNICODE),
            'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'headline' => self::headline($result),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function headline(array $result): string
    {
        if (!empty($result['empty'])) {
            return '—';
        }
        foreach (['net', 'payeMonthly', 'monthly', 'total'] as $key) {
            if (isset($result[$key])) {
                return money((float)$result[$key]);
            }
        }
        return '';
    }
}
