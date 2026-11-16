<?php
declare(strict_types=1);

/**
 * Demo staff accounts, created only when DEMO_PASSWORD is set for the CLI
 * installer. They are deliberately `staff`, never `admin`: the point of the
 * demo is that a signed-in worker can see their own payslip and nothing else.
 * The addresses use the reserved .test domain, so no real mailbox is touched.
 */

return [
    ['name' => 'Ada Okonkwo', 'email' => 'ada@payrite.test', 'role' => 'staff', 'phone' => '+2348020001001'],
    ['name' => 'Tunde Bakare', 'email' => 'tunde@payrite.test', 'role' => 'staff', 'phone' => '+2348020001002'],
    ['name' => 'Ibrahim Musa', 'email' => 'ibrahim@payrite.test', 'role' => 'staff', 'phone' => '+2348020001003'],
];
