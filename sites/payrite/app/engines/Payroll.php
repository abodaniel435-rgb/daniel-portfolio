<?php
declare(strict_types=1);

/**
 * PayRite payroll engine — pure arithmetic, no database, no request.
 *
 * Every figure the site shows comes through compute(), so the landing-page
 * calculator, a generated payslip and the smoke test cannot disagree with each
 * other. The maths is the progressive PAYE model the 2026 Act put in place:
 * reliefs are taken off annual gross first, then each remaining slice is taxed
 * at its own band rate.
 */
final class Payroll
{
    /** Published bands: slice width in naira, then the rate applied to that slice. */
    public const DEFAULT_BANDS = [
        ['slice_width' => 300000.0, 'rate' => 7.00],
        ['slice_width' => 300000.0, 'rate' => 11.00],
        ['slice_width' => 500000.0, 'rate' => 15.00],
        ['slice_width' => 500000.0, 'rate' => 19.00],
        ['slice_width' => 1600000.0, 'rate' => 21.00],
        ['slice_width' => null, 'rate' => 24.00],
    ];

    public const DEFAULT_RULES = [
        'cra_base' => 200000.0,
        'cra_pct' => 20.0,
        'rent_pct' => 20.0,
        'rent_cap' => 6250000.0,
        'pension_pct' => 8.0,
        'employer_pension_pct' => 10.0,
        'nhf_pct' => 2.5,
    ];

    /**
     * Progressive PAYE on annual taxable pay. Returns the yearly tax, the rate
     * of the top slice reached, and the slice-by-slice working so a payslip can
     * show its own sums instead of asking someone to trust a total.
     *
     * @param array<int,array{slice_width:?float,rate:float}> $bands
     * @return array{annual:float,marginal:float,steps:array<int,array{width:float,rate:float,tax:float}>}
     */
    public static function paye(float $taxable, array $bands): array
    {
        $remaining = max(0.0, $taxable);
        $tax = 0.0;
        $marginal = 0.0;
        $steps = [];

        foreach ($bands as $band) {
            if ($remaining <= 0.0) {
                break;
            }
            $width = $band['slice_width'] === null ? $remaining : min($remaining, (float)$band['slice_width']);
            $slice = $width * (float)$band['rate'] / 100.0;
            $tax += $slice;
            $marginal = (float)$band['rate'];
            $remaining -= $width;
            $steps[] = ['width' => round($width, 2), 'rate' => (float)$band['rate'], 'tax' => round($slice, 2)];
        }

        return ['annual' => $tax, 'marginal' => $marginal, 'steps' => $steps];
    }

    /**
     * @param array $package  basic, housing, transport, other, annual_rent, claim_rent, pension_rate
     * @param array $bands    from Payroll::DEFAULT_BANDS or the pay_bands table
     * @param array $rules    from Payroll::DEFAULT_RULES or the rates table
     */
    public static function compute(array $package, ?array $bands = null, ?array $rules = null): array
    {
        $bands = self::bands($bands ?? self::DEFAULT_BANDS);
        $rules = self::rules($rules ?? self::DEFAULT_RULES);

        $basic = self::num($package, 'basic');
        $housing = self::num($package, 'housing');
        $transport = self::num($package, 'transport');
        $other = self::num($package, 'other');
        $gross = $basic + $housing + $transport + $other;

        if ($gross <= 0.0) {
            return ['empty' => true, 'gross' => 0.0, 'package' => $package];
        }

        $annualGross = $gross * 12.0;
        /* The statute writes CRA as ₦200,000 plus 20% of gross, which on a very
           small salary is more than the salary itself. Taxable pay is floored at
           zero either way, so the relief is capped here — a slip must never
           print a deduction larger than the pay it is measured against. */
        $cra = min($annualGross, (float)$rules['cra_base'] + $annualGross * (float)$rules['cra_pct'] / 100.0);

        $rent = self::num($package, 'annual_rent');
        $claimsRent = self::bool($package, 'claim_rent');
        $rentRelief = ($claimsRent && $rent > 0.0)
            ? min($rent * (float)$rules['rent_pct'] / 100.0, (float)$rules['rent_cap'])
            : 0.0;

        // The per-person pension rate is what PenCom permits that employee's
        // category to contribute, so it travels with the employee, not the rules.
        $pensionRate = isset($package['pension_rate']) && $package['pension_rate'] !== ''
            ? self::clamp(self::num($package, 'pension_rate'), 0.0, 20.0)
            : (float)$rules['pension_pct'];
        $pensionMonthly = $gross * $pensionRate / 100.0;
        $pensionAnnual = $pensionMonthly * 12.0;
        $nhfMonthly = $gross * (float)$rules['nhf_pct'] / 100.0;

        $taxable = max(0.0, $annualGross - $cra - $rentRelief - $pensionAnnual);
        $paye = self::paye($taxable, $bands);
        $payeMonthly = $paye['annual'] / 12.0;

        $net = $gross - $payeMonthly - $pensionMonthly - $nhfMonthly;
        $employerPension = $gross * (float)$rules['employer_pension_pct'] / 100.0;
        $employerNhf = $gross * (float)$rules['nhf_pct'] / 100.0;

        return [
            'empty' => false,
            'package' => $package,
            'basic' => $basic, 'housing' => $housing, 'transport' => $transport, 'other' => $other,
            'gross' => $gross,
            'annual_gross' => $annualGross,
            'cra' => $cra,
            'rent' => $rent,
            'rent_relief' => $rentRelief,
            'pension_rate' => $pensionRate,
            'pension' => $pensionMonthly,
            'pension_annual' => $pensionAnnual,
            'nhf' => $nhfMonthly,
            'taxable_annual' => $taxable,
            'taxable_monthly' => $taxable / 12.0,
            'paye' => $payeMonthly,
            'paye_annual' => $paye['annual'],
            'marginal_rate' => $paye['marginal'],
            'steps' => $paye['steps'],
            'net' => $net,
            'annual_net' => $net * 12.0,
            'employer_pension' => $employerPension,
            'employer_nhf' => $employerNhf,
            'employer_cost' => $gross + $employerPension + $employerNhf,
            'effective_rate' => $payeMonthly / $gross * 100.0,
            'take_home_pct' => $net / $gross * 100.0,
        ];
    }

    /**
     * The verdict a payroll officer would give: what is being left on the table,
     * and what the deduction rate implies. Derived from the same numbers, never
     * stored, so it cannot go stale.
     */
    public static function verdict(array $result): array
    {
        if (!empty($result['empty'])) {
            return ['tone' => 'neutral', 'title' => 'Nothing to compute yet',
                'body' => 'Enter the monthly components and the payslip builds itself.'];
        }
        $package = $result['package'];

        if (!$result['rent_relief'] && self::num($package, 'annual_rent') > 0.0) {
            $claimed = self::compute(array_merge($package, ['claim_rent' => true]), null, null);
            $saving = $result['paye'] - $claimed['paye'];
            return ['tone' => 'warn', 'title' => 'Rent relief is switched off',
                'body' => 'Claiming it on ' . number_format(self::num($package, 'annual_rent')) .
                    ' of annual rent would cut PAYE by about ' . number_format($saving, 2) . ' a month.'];
        }
        if (self::clamp(self::num($package, 'pension_rate'), 0.0, 20.0) <= 0.0) {
            return ['tone' => 'bad', 'title' => 'No pension is being deducted',
                'body' => 'Pension is mandatory for formal employment. Skipping it raises taxable pay and exposes the business to PenCom penalties.'];
        }
        if ($result['effective_rate'] < 6.0) {
            return ['tone' => 'ok', 'title' => 'Lightly taxed — expected at this band',
                'body' => 'Effective ' . number_format($result['effective_rate'], 2) . '% of gross, top slice taxed at ' .
                    rtrim(rtrim(number_format($result['marginal_rate'], 2), '0'), '.') . '%. Relief is doing most of the work here.'];
        }
        if ($result['effective_rate'] < 12.0) {
            $extra = 100000.0 * $result['marginal_rate'] / 100.0 / 12.0;
            return ['tone' => 'info', 'title' => 'Typical for this salary range',
                'body' => 'Effective ' . number_format($result['effective_rate'], 2) . '% of gross. The marginal rate is ' .
                    rtrim(rtrim(number_format($result['marginal_rate'], 2), '0'), '.') . '%, so the next ₦100,000 of basic costs roughly ' .
                    number_format($extra, 2) . ' more PAYE a month.'];
        }
        return ['tone' => 'warn', 'title' => 'Heavy take',
            'body' => 'Effective ' . number_format($result['effective_rate'], 2) .
                '% of gross. Re-check the rent relief against the annual cap and whether every allowance is correctly classified before filing.'];
    }

    /** @param array<int,array> $bands */
    public static function bands(array $bands): array
    {
        $out = [];
        foreach ($bands as $band) {
            $rate = max(0.0, (float)($band['rate'] ?? 0.0));
            $width = $band['slice_width'] ?? null;
            $out[] = ['slice_width' => ($width === null || (float)$width <= 0.0) ? null : (float)$width, 'rate' => $rate];
        }
        if (!$out) {
            $out = self::DEFAULT_BANDS;
        }
        // Whatever an administrator types, the last slice has to reach infinity,
        // otherwise a high earner would fall off the end of the table untaxed.
        $last = count($out) - 1;
        $out[$last]['slice_width'] = null;
        return $out;
    }

    public static function rules(array $rules): array
    {
        $out = [];
        foreach (self::DEFAULT_RULES as $key => $default) {
            $value = $rules[$key] ?? $default;
            $out[$key] = is_numeric($value) ? (float)$value : $default;
        }
        return $out;
    }

    private static function num(array $row, string $key): float
    {
        $value = $row[$key] ?? 0;
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }
        return is_numeric($value) ? (float)$value : 0.0;
    }

    private static function bool(array $row, string $key): bool
    {
        if (!array_key_exists($key, $row)) {
            return true;
        }
        $value = $row[$key];
        if (is_bool($value)) {
            return $value;
        }
        return !in_array((string)$value, ['', '0', 'false', 'off', 'no'], true);
    }

    private static function clamp(float $value, float $low, float $high): float
    {
        return max($low, min($high, $value));
    }
}
