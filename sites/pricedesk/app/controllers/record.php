<?php
declare(strict_types=1);

/**
 * PriceDesk member surface — filing a price, the reporter's own history, and
 * the watch list that drives the notifications.
 *
 * A submitted price is never live. It sits in `pending` until an administrator
 * verifies it, which is the only reason a shared board is better than a shared
 * WhatsApp group: the number on screen has been looked at by somebody who is
 * accountable for looking at it.
 */

function record_guard(): void
{
    Auth::requireRole('member', 'admin');
}

function record_report(): void
{
    record_guard();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        record_submit();
        return;
    }

    view('member/report', [
        'title' => 'File today’s price — PriceDesk',
        'markets' => Board::markets(),
        'commodities' => Board::commodities(),
        'today' => date('Y-m-d'),
    ]);
}

function record_submit(): void
{
    verify_csrf();
    $v = (new Validator($_POST))
        ->required('market_id', 'commodity_id', 'price', 'unit', 'report_date')
        ->integer('market_id')->integer('commodity_id')
        ->max('unit', 24)->max('note', 255);

    $price = (float)(str_replace([',', ' '], '', (string)($_POST['price'] ?? '')));
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($_POST);
        redirect('/report');
    }
    if ($price <= 0 || $price > 999999999) {
        remember_errors(['price' => 'Enter the price as a number greater than zero.']);
        remember_old($_POST);
        redirect('/report');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v->value('report_date'))
        || strtotime((string)$v->value('report_date')) === false) {
        remember_errors(['report_date' => 'That date is not a valid calendar date.']);
        remember_old($_POST);
        redirect('/report');
    }
    if ((string)$v->value('report_date') > date('Y-m-d')) {
        remember_errors(['report_date' => 'You cannot file a price for a day that has not arrived.']);
        remember_old($_POST);
        redirect('/report');
    }

    $market = Board::market((int)$v->value('market_id'));
    $commodity = Board::commodity((int)$v->value('commodity_id'));
    if ($market === null || (int)$market['is_active'] !== 1) {
        remember_errors(['market_id' => 'That market is not on the board.']);
        remember_old($_POST);
        redirect('/report');
    }
    if ($commodity === null || (int)$commodity['is_active'] !== 1) {
        remember_errors(['commodity_id' => 'That commodity is not on the board.']);
        remember_old($_POST);
        redirect('/report');
    }

    // The unique key already refuses this, but catching it here turns a
    // database error into a sentence that says what to do instead.
    if (Board::hasReport((int)Auth::id(), (int)$market['id'], (int)$commodity['id'], (string)$v->value('report_date'))) {
        flash('info', 'You already filed ' . $commodity['name'] . ' at ' . $market['name'] . ' for '
            . $v->value('report_date') . '. Change it from your history.');
        redirect('/history');
    }

    $id = Db::insert('price_reports', [
        'market_id' => (int)$market['id'],
        'commodity_id' => (int)$commodity['id'],
        'reporter_id' => (int)Auth::id(),
        'report_date' => (string)$v->value('report_date'),
        'price' => round($price, 2),
        'unit' => mb_substr(trim((string)$v->value('unit')), 0, 24),
        'quantity' => $quantity,
        'note' => mb_substr(trim((string)($v->value('note') ?? '')), 0, 255),
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    Board::audit('report.submit', 'Report #' . $id, [
        'market' => $market['name'], 'commodity' => $commodity['name'],
        'price' => round($price, 2), 'unit' => (string)$v->value('unit'),
    ]);

    // Tell the people watching this exact line that a price is waiting to be
    // verified — not that it is verified, which would be a lie until the
    // administrator has actually looked.
    $watchers = Db::all('SELECT u.email, u.name FROM watches w JOIN users u ON u.id = w.user_id
                          WHERE w.market_id = ? AND w.commodity_id = ? AND u.is_active = 1 AND u.id <> ?',
        [(int)$market['id'], (int)$commodity['id'], (int)Auth::id()]);
    foreach ($watchers as $watcher) {
        Mailer::send(
            (string)$watcher['email'],
            $commodity['name'] . ' at ' . $market['name'] . ' — a new price is waiting',
            Mailer::template('A price is queued for verification',
                '<p>' . e($watcher['name']) . ',</p>'
                . '<p>' . e((string)Auth::user()['name']) . ' filed <strong>' . e($commodity['name'])
                . '</strong> at <strong>' . e($market['name']) . '</strong> for ' . e(money($price))
                . ' per ' . e((string)$v->value('unit')) . ' on ' . e((string)$v->value('report_date')) . '.</p>'
                . '<p>It is queued for verification, so it is not on the board yet.</p>'),
            '',
            'report_submitted'
        );
    }

    flash('success', 'Filed. It reaches the board once an administrator verifies it.');
    redirect('/history');
}

function record_history(): void
{
    record_guard();
    $rows = Board::reportsBy((int)Auth::id());
    $weights = [];
    foreach (Board::commodities(false) as $commodity) {
        $weights[(int)$commodity['id']] = Board::weights($commodity) + ['__fallback' => (float)$commodity['unit_weight_kg']];
    }
    foreach ($rows as $i => $row) {
        $map = $weights[(int)$row['commodity_id']] ?? ['__fallback' => 1.0];
        $fallback = $map['__fallback'] ?? 1.0;
        unset($map['__fallback']);
        $rows[$i]['perKg'] = Pricing::perKg(
            (float)$row['price'], (string)$row['unit'], (int)$row['quantity'], (float)$fallback, $map
        );
    }

    view('member/history', [
        'title' => 'Your reports — PriceDesk',
        'rows' => $rows,
        'tally' => Board::contributionCount((int)Auth::id()),
    ]);
}

function record_withdraw(): void
{
    record_guard();
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $row = Db::one('SELECT * FROM price_reports WHERE id = ? AND reporter_id = ?', [$id, (int)Auth::id()]);
    if ($row === null) {
        http_response_code(404);
        view('errors/404', ['title' => 'Report not found']);
        return;
    }
    // Once a number has been verified it belongs to the board and other people
    // have traded against it. Withdrawing it is an administrator's decision.
    if ((string)$row['status'] === 'verified') {
        flash('error', 'A verified price is part of the board. Ask an administrator to correct it.');
        redirect('/history');
    }

    Db::run('DELETE FROM price_reports WHERE id = ? AND reporter_id = ? AND status <> \'verified\'',
        [$id, (int)Auth::id()]);
    Board::audit('report.withdraw', 'Report #' . $id, ['status' => $row['status']]);
    flash('success', 'Withdrawn.');
    redirect('/history');
}

function record_watch(): void
{
    record_guard();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $marketId = (int)($_POST['market_id'] ?? 0);
        $commodityId = (int)($_POST['commodity_id'] ?? 0);
        if (Board::market($marketId) === null || Board::commodity($commodityId) === null) {
            http_response_code(404);
            view('errors/404', ['title' => 'Nothing to watch']);
            return;
        }
        $existing = Db::one('SELECT id FROM watches WHERE user_id = ? AND market_id = ? AND commodity_id = ?',
            [(int)Auth::id(), $marketId, $commodityId]);
        if ($existing === null) {
            Db::insert('watches', ['user_id' => (int)Auth::id(), 'market_id' => $marketId,
                'commodity_id' => $commodityId, 'created_at' => date('Y-m-d H:i:s')]);
            Board::audit('watch.add', "Market #{$marketId} / Commodity #{$commodityId}");
            flash('success', 'Watching that line.');
        } else {
            Db::run('DELETE FROM watches WHERE id = ?', [(int)$existing['id']]);
            Board::audit('watch.remove', "Market #{$marketId} / Commodity #{$commodityId}");
            flash('info', 'Stopped watching that line.');
        }
        redirect('/watch');
    }

    $markets = Board::markets();
    // Which market's shelf is being shown. Validated against the real ids so a
    // hand-typed ?market= can only ever select a market that exists.
    $marketId = isset($_GET['market']) ? (int)$_GET['market'] : (int)($markets[0]['id'] ?? 0);
    $ids = array_map(static fn (array $m): int => (int)$m['id'], $markets);
    if (!in_array($marketId, $ids, true)) {
        $marketId = (int)($markets[0]['id'] ?? 0);
    }

    view('member/watch', [
        'title' => 'What you are watching — PriceDesk',
        'markets' => $markets,
        'marketId' => $marketId,
        'commodities' => Board::commodities(),
        'watching' => Db::all('SELECT w.*, m.name AS market_name, c.name AS commodity_name
                                 FROM watches w
                                 JOIN markets m ON m.id = w.market_id
                                 JOIN commodities c ON c.id = w.commodity_id
                                WHERE w.user_id = ? ORDER BY c.name, m.name', [(int)Auth::id()]),
    ]);
}

/**
 * GET /api/quote?commodity=&market=&days= — the aggregate, as JSON.
 *
 * Public data, so no session is required; it is the same arithmetic the board
 * page runs, exposed so a member can pull it into a spreadsheet without
 * screenshotting a webpage.
 */
function record_quote_api(): void
{
    $commodity = Board::commodity((int)($_GET['commodity'] ?? 0));
    if ($commodity === null) {
        json_response(['error' => 'unknown commodity'], 404);
        return;
    }
    $marketId = isset($_GET['market']) && $_GET['market'] !== '' ? (int)$_GET['market'] : null;
    if ($marketId !== null && Board::market($marketId) === null) {
        json_response(['error' => 'unknown market'], 404);
        return;
    }
    $days = min(90, max(1, (int)($_GET['days'] ?? 14)));

    $quotes = Board::quotes((int)$commodity['id'], $marketId, $days);
    $agg = $marketId === null
        ? Pricing::acrossMarkets($quotes)
        : Pricing::aggregate($quotes);

    $byMarket = [];
    foreach ($quotes as $quote) {
        $byMarket[$quote['market']][] = $quote;
    }
    $perMarket = [];
    foreach ($byMarket as $name => $rows) {
        $perMarket[$name] = Pricing::aggregate($rows);
    }

    json_response([
        'commodity' => $commodity['name'],
        'market' => $marketId === null ? 'all' : (string)Board::market($marketId)['name'],
        'windowDays' => $days,
        'aggregate' => $agg,
        'perMarket' => $perMarket,
        'generatedAt' => date('c'),
    ]);
}
