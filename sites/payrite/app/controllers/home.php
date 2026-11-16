<?php
declare(strict_types=1);

/**
 * PayRite public pages. The calculator on the landing page is not a script
 * pretending to be a payroll system: the form posts to PHP, the engine in
 * app/engines/Payroll.php does the arithmetic, and the answer is stored in the
 * calculations table so the figure a visitor saw can be reproduced later.
 */

function payrite_amount(mixed $value): float
{
    if (is_string($value)) {
        $value = trim(str_replace([',', ' '], '', $value));
    }
    $number = is_numeric($value) ? (float)$value : 0.0;
    return max(0.0, min($number, 1_000_000_000.0));
}

/** @return array<string,mixed> */
function payrite_package(array $source): array
{
    $pension = $source['pension_rate'] ?? '';
    return [
        'basic' => payrite_amount($source['basic'] ?? 0),
        'housing' => payrite_amount($source['housing'] ?? 0),
        'transport' => payrite_amount($source['transport'] ?? 0),
        'other' => payrite_amount($source['other'] ?? 0),
        'annual_rent' => payrite_amount($source['annual_rent'] ?? 0),
        'claim_rent' => in_array((string)($source['claim_rent'] ?? '1'), ['1', 'on', 'yes', 'true'], true),
        'pension_rate' => max(0.0, min(is_numeric($pension) ? (float)$pension : 8.0, 20.0)),
    ];
}

function home_index(): void
{
    $package = [
        'basic' => 250000, 'housing' => 100000, 'transport' => 80000, 'other' => 0,
        'annual_rent' => 2400000, 'claim_rent' => true, 'pension_rate' => 8,
    ];
    $posted = false;
    $errors = [];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        verify_csrf();
        $posted = true;
        $package = payrite_package($_POST);
        if ($package['basic'] + $package['housing'] + $package['transport'] + $package['other'] <= 0) {
            $errors['basic'] = 'Enter at least one salary component so there is something to payslip.';
            // field_error() reads the flashed errors, which is what every other
            // form in the app does — without this the message is built and never
            // reaches the page.
            remember_errors($errors);
        }
        $name = trim(mb_substr((string)($_POST['who'] ?? ''), 0, 60));
        $result = $package['basic'] + $package['housing'] + $package['transport'] + $package['other'] > 0
            ? Desk::compute($package)
            : ['empty' => true, 'gross' => 0.0, 'package' => $package];
        if (!$result['empty']) {
            Desk::logCalculation('paye', $name ?: 'Public calculator', $package, [
                'gross' => round($result['gross'], 2), 'paye' => round($result['paye'], 2),
                'net' => round($result['net'], 2), 'employer_cost' => round($result['employer_cost'], 2),
                'effective_rate' => round($result['effective_rate'], 2),
            ], money($result['net']));
        }
        // An empty submission is a question that was not completed, not an answer
        // worth keeping — it would flood the log on a stray Enter.
    } else {
        $result = Desk::compute($package);
    }

    $verdict = Payroll::verdict($result);
    $whatif = $result['empty'] ? null : Desk::compute(array_merge($package, ['basic' => $package['basic'] + 50000]));

    view('home', [
        'title' => 'PayRite — Nigerian payroll, PAYE and net pay in one pass',
        'bodyClass' => 'home',
        'package' => $package,
        'result' => $result,
        'verdict' => $verdict,
        'whatif' => $whatif,
        'posted' => $posted,
        'errors' => $errors,
        'bands' => Desk::bands(),
        'rules' => Desk::rules(),
        'asked' => (int)Db::value("SELECT COUNT(*) FROM calculations WHERE tool = 'paye'"),
        'ladder' => home_ladder(),
    ]);
}

/**
 * A ladder of packages computed on request. It is the same engine the payslips
 * use, so the table on the landing page cannot drift away from what a run would
 * actually produce.
 *
 * @return array<int,array>
 */
function home_ladder(): array
{
    $rows = [];
    foreach ([120000, 250000, 400000, 650000, 1200000, 2500000] as $gross) {
        $package = [
            'basic' => $gross * 0.6, 'housing' => $gross * 0.2, 'transport' => $gross * 0.2,
            'other' => 0, 'annual_rent' => $gross * 12 * 0.5, 'claim_rent' => true, 'pension_rate' => 8,
        ];
        $rows[] = Desk::compute($package) + ['label' => money($gross)];
    }
    return $rows;
}

function home_rates(): void
{
    view('rates', [
        'title' => 'PAYE bands and reliefs — PayRite',
        'bodyClass' => 'rates',
        'bands' => Desk::bands(),
        'rules' => Desk::rules(),
        'ladder' => home_ladder(),
        'changed' => Db::all('SELECT b.*, u.name AS updater FROM pay_bands b
                                LEFT JOIN users u ON u.id = b.updated_by ORDER BY b.band_order'),
    ]);
}

/** The same engine, as data — for anyone who wants to check our figures. */
function home_api_paye(): void
{
    /* The published examples use the short names (`rent`, `pension`) and the
       landing form posts the long ones (`annual_rent`, `pension_rate`). Both are
       accepted: a visitor copies whichever of the two they were shown. */
    $source = $_GET;
    foreach (['annual_rent' => 'rent', 'pension_rate' => 'pension'] as $long => $short) {
        if (!isset($source[$long]) && isset($source[$short])) {
            $source[$long] = $source[$short];
        }
    }
    $package = payrite_package($source);

    // A single `gross` is the shorthand most people reach for: split it the way
    // a typical Nigerian package does, 60/20/20.
    if ($package['basic'] + $package['housing'] + $package['transport'] + $package['other'] <= 0
        && isset($_GET['gross']) && is_numeric($_GET['gross'])) {
        $gross = payrite_amount($_GET['gross']);
        $package['basic'] = round($gross * 0.6, 2);
        $package['housing'] = round($gross * 0.2, 2);
        $package['transport'] = round($gross * 0.2, 2);
    }

    $result = Desk::compute($package);
    if ($result['empty']) {
        json_response(['error' => 'Provide gross or the salary components.'], 422);
        return;
    }

    json_response([
        'monthly' => [
            'gross' => round($result['gross'], 2),
            'cra' => round($result['cra'] / 12, 2),
            'rent_relief' => round($result['rent_relief'] / 12, 2),
            'taxable' => round($result['taxable_monthly'], 2),
            'paye' => round($result['paye'], 2),
            'pension' => round($result['pension'], 2),
            'nhf' => round($result['nhf'], 2),
            'net' => round($result['net'], 2),
        ],
        'annual' => [
            'gross' => round($result['annual_gross'], 2),
            'taxable' => round($result['taxable_annual'], 2),
            'paye' => round($result['paye_annual'], 2),
            'net' => round($result['annual_net'], 2),
        ],
        'employer_cost' => round($result['employer_cost'], 2),
        'marginal_rate' => $result['marginal_rate'],
        'effective_rate' => round($result['effective_rate'], 2),
        'take_home_pct' => round($result['take_home_pct'], 2),
        'bands' => Desk::bands(),
        'rules' => Desk::rules(),
        'generatedAt' => date('c'),
    ]);
}
