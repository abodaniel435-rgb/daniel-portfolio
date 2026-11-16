<?php
declare(strict_types=1);

require dirname(__DIR__) . '/core/bootstrap.php';

// The automation closures talk to Board and Pricing, which the web entry point
// loads. A cron run has to load them itself or the first task dies on a missing
// class, silently, at 3am, in a log nobody reads.
require_once BASE_PATH . '/app/services.php';

if (PHP_SAPI === 'cli') {
    $ran = Automation::run('PriceDesk');
    echo "Automation complete: {$ran} task(s).\n";
    exit(0);
}

// cPanel cron can also call this over HTTP: /cron/run.php?key=CRON_SECRET
$expected = (string)env('CRON_SECRET', '');
$given = (string)($_GET['key'] ?? '');
if ($expected === '' || !hash_equals($expected, $given)) {
    http_response_code(403);
    exit('Forbidden');
}
header('Content-Type: text/plain; charset=UTF-8');
$ran = Automation::run('PriceDesk');
echo "Automation complete: {$ran} task(s).\n";
