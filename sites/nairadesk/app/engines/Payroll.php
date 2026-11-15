<?php
declare(strict_types=1);

/**
 * Payroll engine — progressive PAYE, CRA, rent relief, pension and NHF.
 *
 * Every figure is read from the `rates` table so an administrator can move a
 * statutory number without touching this file. The maths mirrors the published
 * PayRite calculator exactly; a change here must be a change there.
 */
final class Payroll
{
    /** @param array<string,float> $rates */
    public static function bands(array $rates): array
    {
        $widths = [
            self::num($rates, 'band_1_width'),
            self::num($rates, 'band_2_width'),
            self::num($rates, 'band_3_width'),
            self::num($rates, 'band_4_width'),
            self::num($rates, 'band_5_width'),
            INF,
        ];
        $rateKeys = ['band_1_rate', 'band_2_rate', 'band_3_rate', 'band_4_rate', 'band_5_rate', 'band_6_rate'];

        $bands = [];
        foreach ($rateKeys as $i => $key) {
            $bands[] = ['width' => $widths[$i], 'rate' => self::num($rates, $key), 'code' => $key];
        }
        return $bands;
    }

    /**
     * Tax each slice of annual taxable pay at its own rate.
     *
     * @return array{annual:float,marginal:float,steps:array<int,array<string,float|string>>}
     */
    public static function payeOn(float $taxable, array $bands): array
    {
        $remaining = max(0.0, $taxable);
        $tax = 0.0;
        $marginal = 0.0;
        $steps = [];

        foreach ($bands as $band) {
            if ($remaining <= 0.0) {
                break;
            }
            $slice = is_finite($band['width']) ? min($remaining, $band['width']) : $remaining;
            $charge = $slice * $band['rate'] / 100;
            $tax += $charge;
            $marginal = $band['rate'];
            $remaining -= $slice;
            $steps[] = [
                'width' => is_finite($band['width']) ? $band['width'] : null,
                'rate' => $band['rate'],
                'slice' => round($slice, 2),
                'charge' => round($charge, 2),
            ];
        }

        return ['annual' => $tax, 'marginal' => $marginal, 'steps' => $steps];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,float> $rates
     * @return array<string,mixed>
     */
    public static function compute(array $input, array $rates): array
    {
        $basic = self::money($input['basic'] ?? 0);
        $housing = self::money($input['housing'] ?? 0);
        $transport = self::money($input['transport'] ?? 0);
        $other = self::money($input['other'] ?? 0);
        $gross = $basic + $housing + $transport + $other;

        if ($gross <= 0) {
            return ['empty' => true, 'gross' => 0.0];
        }

        $annualGross = $gross * 12;
        $cra = self::num($rates, 'cra_fixed') + $annualGross * self::num($rates, 'cra_percent') / 100;

        $useRent = !empty($input['use_rent']);
        $annualRent = self::money($input['annual_rent'] ?? 0);
        $rentRelief = ($useRent && $annualRent > 0)
            ? min($annualRent * self::num($rates, 'rent_relief_percent') / 100, self::num($rates, 'rent_relief_cap'))
            : 0.0;

        $usePension = array_key_exists('use_pension', $input) ? !empty($input['use_pension']) : true;
        $pensionRate = isset($input['pension_rate']) && $input['pension_rate'] !== ''
            ? self::money($input['pension_rate'])
            : self::num($rates, 'pension_employee');
        $pensionMonthly = $usePension ? $gross * $pensionRate / 100 : 0.0;
        $pensionAnnual = $pensionMonthly * 12;

        $useNhf = array_key_exists('use_nhf', $input) ? !empty($input['use_nhf']) : true;
        $nhfMonthly = $useNhf ? $gross * self::num($rates, 'nhf_rate') / 100 : 0.0;

        $taxable = max(0.0, $annualGross - $cra - $rentRelief - $pensionAnnual);
        $bands = self::bands($rates);
        $paye = self::payeOn($taxable, $bands);
        $payeMonthly = $paye['annual'] / 12;

        $net = $gross - $payeMonthly - $pensionMonthly - $nhfMonthly;
        $employerPension = $usePension ? $gross * self::num($rates, 'pension_employer') / 100 : 0.0;
        $employerNhf = $useNhf ? $gross * self::num($rates, 'nhf_rate') / 100 : 0.0;
        $employerCost = $gross + $employerPension + $employerNhf;

        return [
            'empty' => false,
            'gross' => round($gross, 2),
            'annualGross' => round($annualGross, 2),
            'cra' => round($cra, 2),
            'rentRelief' => round($rentRelief, 2),
            'pensionMonthly' => round($pensionMonthly, 2),
            'pensionAnnual' => round($pensionAnnual, 2),
            'pensionRate' => $pensionRate,
            'nhfMonthly' => round($nhfMonthly, 2),
            'employerPension' => round($employerPension, 2),
            'employerNhf' => round($employerNhf, 2),
            'taxable' => round($taxable, 2),
            'taxableMonthly' => round($taxable / 12, 2),
            'payeMonthly' => round($payeMonthly, 2),
            'payeAnnual' => round($paye['annual'], 2),
            'marginal' => $paye['marginal'],
            'steps' => $paye['steps'],
            'net' => round($net, 2),
            'employerCost' => round($employerCost, 2),
            'annualNet' => round($net * 12, 2),
            'effRate' => round($payeMonthly / $gross * 100, 2),
            'components' => [
                'basic' => $basic, 'housing' => $housing,
                'transport' => $transport, 'other' => $other,
            ],
        ];
    }

    private static function num(array $rates, string $code): float
    {
        return (float)($rates[$code] ?? 0);
    }

    private static function money(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        return (float)preg_replace('/[^\d.\-]/', '', (string)$value);
    }
}
