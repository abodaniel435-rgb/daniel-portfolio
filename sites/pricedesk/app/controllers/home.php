<?php
declare(strict_types=1);

/**
 * PriceDesk public surface — the landing page, the board and the comparison.
 *
 * The landing page quotes no number it has not just computed. The worked
 * example is fed through the same Pricing::aggregate() and Pricing::haul() the
 * board uses, so a front page can never advertise a figure the engine
 * contradicts — and the live counts underneath it come from the database.
 */

function home_index(): void
{
    $weights = ['kg' => 1.0, 'mudu' => 0.12, 'crate' => 2.4, 'bag' => 50.0];

    // Six quotes for the same commodity, deliberately including one absurd
    // outlier — the point of the page is that a median survives it.
    $demo = [
        ['perKg' => Pricing::perKg(1200, 'mudu', 1, 1.0, $weights), 'reportDate' => date('Y-m-d'), 'reporterId' => 1],
        ['perKg' => Pricing::perKg(1300, 'mudu', 1, 1.0, $weights), 'reportDate' => date('Y-m-d'), 'reporterId' => 2],
        ['perKg' => Pricing::perKg(1150, 'mudu', 1, 1.0, $weights), 'reportDate' => date('Y-m-d'), 'reporterId' => 3],
        ['perKg' => Pricing::perKg(1250, 'mudu', 1, 1.0, $weights), 'reportDate' => date('Y-m-d'), 'reporterId' => 4],
        ['perKg' => Pricing::perKg(9000, 'crate', 1, 1.0, $weights), 'reportDate' => date('Y-m-d'), 'reporterId' => 5],
        ['perKg' => Pricing::perKg(1220, 'mudu', 1, 1.0, $weights), 'reportDate' => date('Y-m-d'), 'reporterId' => 6],
    ];
    $gate = Pricing::aggregate($demo);
    $city = Pricing::aggregate([
        ['perKg' => 3100, 'reportDate' => date('Y-m-d'), 'reporterId' => 7],
        ['perKg' => 3300, 'reportDate' => date('Y-m-d'), 'reporterId' => 8],
        ['perKg' => 3250, 'reportDate' => date('Y-m-d'), 'reporterId' => 9],
        ['perKg' => 3400, 'reportDate' => date('Y-m-d'), 'reporterId' => 10],
    ]);

    // The ticker is real board data, not a decorative strip: the four lines with
    // the most verified quotes, exactly as /board ranks them.
    $live = Board::board(null, 14);
    $ticker = array_map(static function (array $row): array {
        return [
            'median' => (float)$row['median'],
            'commodity' => (string)$row['commodity'],
            'market' => (int)$row['count'] . ' verified quote' . ((int)$row['count'] === 1 ? '' : 's'),
        ];
    }, array_slice($live, 0, 4));

    view('home', [
        'title' => 'PriceDesk — the price board a market association can actually agree on',
        'gate' => $gate,
        'city' => $city,
        'haul' => Pricing::haul(Board::rates(), $gate, $city, 62),
        'ticker' => $ticker,
        'stats' => Db::one('SELECT
              (SELECT COUNT(*) FROM price_reports WHERE status = \'verified\') AS verified,
              (SELECT COUNT(*) FROM price_reports WHERE status = \'pending\') AS pending,
              (SELECT COUNT(DISTINCT market_id) FROM price_reports) AS markets,
              (SELECT COUNT(DISTINCT reporter_id) FROM price_reports) AS reporters') ?: [],
    ]);
}

function home_board(): void
{
    $markets = Board::markets();
    $index = [];
    foreach ($markets as $market) {
        $index[(int)$market['id']] = $market;
    }

    // A hand-edited ?market= that names no real market is dropped rather than
    // trusted, so the board never renders somebody else's id.
    $marketId = isset($_GET['market']) && $_GET['market'] !== '' ? (int)$_GET['market'] : null;
    if ($marketId !== null && !isset($index[$marketId])) {
        $marketId = null;
    }
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 14;
    $days = in_array($days, [7, 14, 30], true) ? $days : 14;

    view('board', [
        'title' => 'Today’s board — PriceDesk',
        'markets' => $markets,
        'marketId' => $marketId,
        'marketName' => $marketId === null ? 'every market' : (string)$index[$marketId]['name'],
        'days' => $days,
        'rows' => Board::board($marketId, $days),
    ]);
}

/**
 * GET  /compare  — the form
 * POST /compare  — the same arithmetic, run by the server and stored
 */
function home_compare(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $v = (new Validator($_POST))
            ->required('from_market', 'to_market', 'commodity')
            ->integer('from_market')->integer('to_market')->integer('commodity')
            ->max('distance', 6);

        if (!$v->passes()) {
            remember_errors($v->errors());
            redirect('/compare');
        }

        $commodity = Board::commodity((int)$v->value('commodity'));
        $from = Board::market((int)$v->value('from_market'));
        $to = Board::market((int)$v->value('to_market'));
        if ($commodity === null || $from === null || $to === null) {
            remember_errors(['commodity' => 'That market or commodity is not on the board.']);
            redirect('/compare');
        }

        $days = (int)($v->value('days') ?: 14);
        $distance = (float)($v->value('distance') ?: $to['haul_km']);

        // Each side is re-derived from the verified quotes rather than read off
        // a cached figure, so the answer always reflects the board as it is now.
        $aggFrom = Pricing::aggregate(Board::quotes((int)$commodity['id'], (int)$from['id'], $days));
        $aggTo = Pricing::aggregate(Board::quotes((int)$commodity['id'], (int)$to['id'], $days));
        $result = Pricing::haul(Board::rates(), $aggFrom, $aggTo, $distance);

        Board::logCalculation('haul', $commodity['name'] . ': ' . $from['name'] . ' → ' . $to['name'], [
            'commodity' => (int)$commodity['id'], 'from' => (int)$from['id'], 'to' => (int)$to['id'],
            'distance' => $distance, 'days' => $days,
            'from_median' => $aggFrom['median'], 'to_median' => $aggTo['median'],
        ], $result);

        $_SESSION['last_compare'] = [
            'commodity' => $commodity['name'], 'from' => $from['name'], 'to' => $to['name'],
            'aggFrom' => $aggFrom, 'aggTo' => $aggTo, 'result' => $result,
        ];
        Board::audit('compare.run', $from['name'] . ' → ' . $to['name'], ['distance' => $distance]);
        redirect('/compare');
    }

    view('compare', [
        'title' => 'Is it worth the trip? — PriceDesk',
        'markets' => Board::markets(),
        'commodities' => Board::commodities(),
        'last' => $_SESSION['last_compare'] ?? null,
        'rates' => Board::rates(),
    ]);
}
