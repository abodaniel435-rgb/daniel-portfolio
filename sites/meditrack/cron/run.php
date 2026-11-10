<?php
declare(strict_types=1);

require dirname(__DIR__) . '/core/bootstrap.php';

if (PHP_SAPI === 'cli') {
    $ran = Automation::run('MediTrack');
    echo "Automation complete: {$ran} task(s).\n";
    exit(0);
}

// cPanel cron can call this URL: /cron/run.php?key=CRON_SECRET
$expected = (string)env('CRON_SECRET', '');
$given = (string)($_GET['key'] ?? '');
if ($expected === '' || !hash_equals($expected, $given)) {
    http_response_code(403);
    exit('Forbidden');
}
header('Content-Type: text/plain; charset=UTF-8');
$ran = Automation::run('MediTrack');
echo "Automation complete: {$ran} task(s).\n";
