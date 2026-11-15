<?php
declare(strict_types=1);

/**
 * Engine parity test — the PHP payroll engine against the PayRite browser engine.
 *
 *   php tools/engine_parity.php
 *
 * Nothing is copied into this file. The calculator slice is lifted out of
 * demos/payrite/app.js at run time and executed under Node, so the two engines
 * cannot drift apart silently: if the demo is refactored the anchors disappear
 * and the test fails instead of quietly comparing an old snapshot.
 */

$demo = getenv('PAYRITE_JS') ?: dirname(__DIR__, 3) . '/demos/payrite/app.js';
if (!is_readable($demo)) {
    fwrite(STDERR, "SKIP: {$demo} is not readable.\n");
    fwrite(STDERR, "Run this from the portfolio repository checkout, or set PAYRITE_JS.\n");
    exit(2);
}
$src = (string) file_get_contents($demo);

/**
 * Cut the text between two anchors. The demo's storage wiring sits between the
 * two regions below and calls kit.store(), which Node has no, so it is skipped.
 */
function region(string $src, string $from, string $to, string $demo): string
{
    $a = strpos($src, $from);
    $b = $a === false ? false : strpos($src, $to, $a);
    if ($a === false || $b === false) {
        fwrite(STDERR, "FAIL: could not find '{$from}' through '{$to}' in {$demo}.\n");
        fwrite(STDERR, "The PayRite calculator moved — re-point this test at it.\n");
        exit(1);
    }
    return substr($src, $a, $b - $a);
}

$bands = region($src, 'var DEFAULT_BANDS = [', 'var bandsStore', $demo);
$engine = region($src, 'function normalise(list) {', 'function readForm() {', $demo);

$rates = [
    'band_1_width' => 300000, 'band_1_rate' => 7,
    'band_2_width' => 300000, 'band_2_rate' => 11,
    'band_3_width' => 500000, 'band_3_rate' => 15,
    'band_4_width' => 500000, 'band_4_rate' => 19,
    'band_5_width' => 1600000, 'band_5_rate' => 21,
    'band_6_rate' => 24,
    'cra_fixed' => 200000, 'cra_percent' => 20,
    'rent_relief_percent' => 20, 'rent_relief_cap' => 6250000,
    'pension_employee' => 8, 'pension_employer' => 10, 'nhf_rate' => 2.5,
];

/* A null width means "unbounded" on both sides: normalise() turns it into
   Infinity, Payroll::bands() turns it into INF. */
$table = [];
foreach (['1', '2', '3', '4', '5'] as $n) {
    $table[] = ['width' => $rates['band_' . $n . '_width'], 'rate' => $rates['band_' . $n . '_rate']];
}
$table[] = ['width' => null, 'rate' => $rates['band_6_rate']];

$cases = [];
foreach ([0, 28000, 45000, 67500, 85000, 120000, 150000, 185000, 250000, 320000, 450000, 700000, 1200000] as $basic) {
    $housing = (int) round($basic * 0.2);
    $transport = (int) round($basic * 0.1);
    $other = (int) round($basic * 0.05);

    // Full package: pension, NHF and rent relief all switched on.
    $cases[] = ['basic' => $basic, 'housing' => $housing, 'transport' => $transport, 'other' => $other,
        'useRent' => true, 'rent' => 1800000, 'usePension' => true, 'pensionRate' => 8, 'useNhf' => true];
    // Same money, relief and statutory deductions off — the worst-case tax bill.
    $cases[] = ['basic' => $basic, 'housing' => $housing, 'transport' => $transport, 'other' => $other,
        'useRent' => false, 'rent' => 0, 'usePension' => false, 'pensionRate' => 8, 'useNhf' => false];
    // Basic only, with a voluntary 15% pension — the CRA floor bites here.
    $cases[] = ['basic' => $basic, 'housing' => 0, 'transport' => 0, 'other' => 0,
        'useRent' => false, 'rent' => 0, 'usePension' => true, 'pensionRate' => 15, 'useNhf' => true];
    // Rent far above the relief cap.
    $cases[] = ['basic' => $basic, 'housing' => $housing, 'transport' => 0, 'other' => 0,
        'useRent' => true, 'rent' => 60000000, 'usePension' => true, 'pensionRate' => 8, 'useNhf' => true];
}

$keys = ['gross', 'annualGross', 'cra', 'rentRelief', 'pensionMonthly', 'nhfMonthly',
    'taxable', 'payeMonthly', 'payeAnnual', 'marginal', 'net', 'employerCost', 'annualNet', 'effRate'];

/* ---------- the browser engine, under Node ---------- */

$picks = [];
foreach ($keys as $k) {
    $picks[] = $k . ': r.' . $k;
}

$js = "const cases = " . json_encode($cases) . ";\n"
    . "const table = " . json_encode($table) . ";\n\n"
    . $bands . "\n"
    . "  var bands = DEFAULT_BANDS.map(function (b) { return { width: b.width, rate: b.rate }; });\n\n"
    . $engine . "\n"
    . "process.stdout.write(JSON.stringify(cases.map(function (c) {\n"
    . "  var r = compute(c, normalise(table));\n"
    . "  return r.empty ? null : { " . implode(', ', $picks) . " };\n"
    . "})));\n";

$file = (string) tempnam(sys_get_temp_dir(), 'payrite') . '.js';
file_put_contents($file, $js);
$node = (string) shell_exec('node ' . escapeshellarg($file) . ' 2>&1');
unlink($file);

$decoded = json_decode($node, true);
if (!is_array($decoded) || count($decoded) !== count($cases)) {
    fwrite(STDERR, "FAIL: node did not return one object per case.\n---\n{$node}\n");
    exit(1);
}

/* ---------- the PHP engine ---------- */

require dirname(__DIR__) . '/app/engines/Payroll.php';

$compared = 0;
$bad = 0;
foreach ($decoded as $i => $expect) {
    $input = $cases[$i];
    $input['use_rent'] = $input['useRent'];
    $input['annual_rent'] = $input['rent'];
    $input['use_pension'] = $input['usePension'];
    $input['pension_rate'] = $input['pensionRate'];
    $input['use_nhf'] = $input['useNhf'];

    $got = Payroll::compute($input, $rates);
    if ($expect === null || ($got['empty'] ?? false)) {
        if ($expect !== null || !($got['empty'] ?? false)) {
            printf("MISMATCH case %d: one engine called it empty, the other did not\n", $i);
            $bad++;
        }
        continue;
    }

    foreach ($keys as $key) {
        $compared++;
        if (abs((float) $expect[$key] - (float) $got[$key]) > 0.01) {
            printf("MISMATCH case %d (%s): js=%s php=%s\n", $i, $key, $expect[$key], $got[$key]);
            $bad++;
        }
    }
}

printf("calculator slice lifted verbatim from %s\n", $demo);
printf(
    "%d cases, %d figures compared, %d mismatches — %s\n",
    count($cases),
    $compared,
    $bad,
    $bad === 0 ? 'the PHP engine matches the PayRite calculator' : 'ENGINES DIFFER'
);
exit($bad === 0 ? 0 : 1);
