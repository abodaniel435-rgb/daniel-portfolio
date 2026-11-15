<?php
declare(strict_types=1);

function home_index(): void
{
    $rates = Desk::rates();
    // Rendered live rather than typed in, so the front page can never quote a
    // figure the engine disagrees with.
    $example = Payroll::compute([
        'basic' => 108000, 'housing' => 36000, 'transport' => 18000, 'other' => 18000,
        'use_pension' => true, 'pension_rate' => 8, 'use_nhf' => true,
        'use_rent' => true, 'annual_rent' => 1800000,
    ], $rates);

    view('home', [
        'title' => 'NairaDesk — Nigerian payroll, done properly',
        'bands' => Payroll::bands($rates),
        'example' => $example,
    ]);
}

function home_how(): void
{
    view('how', ['title' => 'How the PAYE is worked out — NairaDesk']);
}
