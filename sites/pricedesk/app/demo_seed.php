<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/services.php';

/**
 * Demo board content — run only by the CLI installer when DEMO_PASSWORD is set.
 *
 * The point of this data is that every screen has something honest to show: a
 * board with a spread of quotes rather than one price, confidence that varies
 * from line to line, a queue with a genuine outlier sitting in it, and a couple
 * of lines left deliberately stale so the staleness styling is visible instead
 * of theoretical.
 *
 * Prices are modelled as ₦ per kilogram and converted back through the same
 * Pricing::perKg() the application uses, so the seeded board re-derives to
 * exactly the figures the engine reports.
 */
return function (): void {
    $reporters = Db::all("SELECT id, name FROM users WHERE email LIKE '%@pricedesk.test' ORDER BY id");
    if (count($reporters) < 8) {
        return; // demo accounts were not created; leave the board empty
    }
    if ((int)Db::value("SELECT COUNT(*) FROM price_reports WHERE status <> 'pending'") > 0) {
        return; // a board already exists; this script must not double-file
    }

    /** market name => per-kilogram multiplier. Wholesale is the floor. */
    $markets = [
        'Mile 12 Market' => 1.00, 'Balogun Market' => 1.18, 'Mile 3 Market' => 1.12,
        'Oshodi Market' => 1.09, 'Bodija Market' => 0.94,
    ];
    /** commodity name => [₦/kg at wholesale, unit a reporter uses, note] */
    $lines = [
        'Long-Keep Tomato' => [1450, 'crate'],
        'Scotch Bonnet Pepper' => [4200, 'basket'],
        'Garden Egg' => [2650, 'basket'],
        'Onion (Fresh)' => [1980, 'crate'],
        'Local Rice (Ofada)' => [1320, 'bag'],
        'Garri (Ijebu White)' => [1150, 'mudu'],
    ];

    $marketId = [];
    foreach ($markets as $name => $_) {
        $id = (int)Db::value('SELECT id FROM markets WHERE name = ?', [$name]);
        if ($id > 0) {
            $marketId[$name] = $id;
        }
    }
    $commodity = [];
    foreach ($lines as $name => $_) {
        $row = Db::one('SELECT * FROM commodities WHERE name = ?', [$name]);
        if ($row !== null) {
            $commodity[$name] = $row;
        }
    }
    if ($marketId === [] || count($commodity) < count($lines)) {
        return; // seed.sql has not been applied
    }

    // Deterministic jitter: the demo board must look the same on every install
    // so a screenshot and the README describe the same numbers.
    mt_srand(20261115);
    $now = date('Y-m-d H:i:s');
    $verifierId = (int)Db::value("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");

    // Which day-range each line was last filed over, in days ago. Most lines are
    // current; one sits at 'ageing' and one at 'stale' so every freshness style
    // on the board is actually exercised rather than merely defined.
    $ranges = [
        'Long-Keep Tomato' => [1, 10],
        'Local Rice (Ofada)' => [1, 10],
        'Garri (Ijebu White)' => [1, 10],
        'Onion (Fresh)' => [1, 10],
        'Scotch Bonnet Pepper' => [5, 12],
        'Garden Egg' => [9, 14],
    ];

    foreach ($commodity as $name => $row) {
        [$base, $unit] = $lines[$name];
        $weights = Board::weights($row);
        $kg = Pricing::unitKg($unit, (float)$row['unit_weight_kg'], $weights);
        [$newest, $oldest] = $ranges[$name] ?? [1, 10];

        foreach ($marketId as $marketName => $mid) {
            $premium = $markets[$marketName] * (1 + mt_rand(-4, 4) / 100);
            // Two or three reporters per line per day: enough for a median to
            // mean something, few enough that the spread stays visible.
            $crew = array_slice($reporters, mt_rand(0, 3), 3);

            for ($back = $newest; $back <= $oldest; $back++) {
                $date = date('Y-m-d', strtotime("-{$back} day"));
                $drift = 1 + mt_rand(-7, 9) / 100 + ($back * 0.004);
                foreach ($crew as $reporter) {
                    $perKg = $base * $premium * $drift;
                    Db::run(
                        'INSERT INTO price_reports
                            (market_id, commodity_id, reporter_id, report_date, price, unit, quantity,
                             note, status, verified_by, verified_at, reject_reason, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, 1, ?, \'verified\', ?, ?, \'\', ?)
                         ON DUPLICATE KEY UPDATE price = VALUES(price)',
                        [
                            $mid,
                            (int)$row['id'],
                            (int)$reporter['id'],
                            $date,
                            // round to the naira the reporter would actually say,
                            // then let the engine re-derive the per-kilo figure
                            round($perKg * $kg / 50) * 50,
                            $unit,
                            $back === 1 ? '' : 'Traded before noon.',
                            $verifierId ?: null,
                            $now,
                            $now,
                        ]
                    );
                }
            }
        }
    }

    /* The queue: three quotes waiting for a decision, one of them wildly out. */
    $tomato = $commodity['Long-Keep Tomato'] ?? null;
    $garri = $commodity['Garri (Ijebu White)'] ?? null;
    $rice = $commodity['Local Rice (Ofada)'] ?? null;
    if ($tomato !== null && $garri !== null && $rice !== null) {
        $pending = [
            [$marketId['Mile 3 Market'], (int)$tomato['id'], $reporters[0], 1750 * 22, 'crate',
                'Few trucks this morning, price is up.'],
            [$marketId['Bodija Market'], (int)$garri['id'], $reporters[4], 96000, 'bag',
                'New crop landing, cheaper than Lagos.'],
            // An honest mistake of this shape — a bag quoted as a crate — is why
            // the reviewer sees a deviation percentage instead of a bare number.
            [$marketId['Balogun Market'], (int)$rice['id'], $reporters[6], 2100000, 'crate',
                'Whole crate of bags at the current rate.'],
        ];
        foreach ($pending as [$mid, $cid, $reporter, $price, $unit, $note]) {
            Db::run('INSERT INTO price_reports
                        (market_id, commodity_id, reporter_id, report_date, price, unit, quantity,
                         note, status, verified_by, verified_at, reject_reason, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, 1, ?, \'pending\', NULL, NULL, \'\', ?)
                     ON DUPLICATE KEY UPDATE note = VALUES(note)',
                [$mid, $cid, (int)$reporter['id'], date('Y-m-d'), $price, $unit, $note, $now]);
        }
    }

    /* Watch lists, so the notifications and the stale-line nudge have targets. */
    foreach ($commodity as $name => $row) {
        foreach (array_slice($reporters, 0, 4) as $i => $reporter) {
            $marketName = array_keys($marketId)[$i % count($marketId)];
            Db::run('INSERT INTO watches (user_id, market_id, commodity_id, created_at) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE created_at = VALUES(created_at)',
                [(int)$reporter['id'], $marketId[$marketName], (int)$row['id'], $now]);
        }
    }

    $total = (int)Db::value('SELECT COUNT(*) FROM price_reports');
    echo "  {$total} price reports across " . count($marketId) . " markets and " . count($commodity) . " commodities.\n";
};
