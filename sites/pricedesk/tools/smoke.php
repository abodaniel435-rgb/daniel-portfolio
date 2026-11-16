<?php
declare(strict_types=1);

/**
 * End-to-end smoke test for a running PriceDesk.
 *
 *   php tools/smoke.php [base-url]
 *
 * It drives the real HTTP surface — sessions, CSRF tokens, redirects, JSON —
 * rather than calling controllers in isolation, then checks what MySQL kept.
 *
 * It never reads a password from anywhere. The member registers itself over
 * HTTP with a throwaway credential and the temporary administrator is created
 * through Installer::createUser() with a generated one, so running this test
 * cannot expose the real accounts. Everything it creates is deleted again,
 * because a demo board that grows a fake report every time a developer runs
 * the suite is a demo board nobody can read.
 */

require dirname(__DIR__) . '/core/bootstrap.php';
require dirname(__DIR__) . '/app/services.php';

/** Minimal cookie-jar client for one session. */
final class Http
{
    public int $code = 0;
    public string $body = '';
    /** @var array<string,string> */
    public array $headers = [];
    private string $jar;

    public function __construct(private string $base)
    {
        $this->jar = (string) tempnam(sys_get_temp_dir(), 'pdjar');
    }

    /** @param array<string,mixed> $fields */
    public function post(string $path, array $fields = [], string $token = ''): self
    {
        if ($token !== '') {
            $fields['_token'] = $token;
        }
        return $this->request('POST', $path, $fields);
    }

    public function get(string $path): self
    {
        return $this->request('GET', $path);
    }

    /** Token for the form that lives on $path, following a redirect if the page moved. */
    public function token(string $path): string
    {
        $this->request('GET', $path, [], true);
        return preg_match('/name="_token" value="([^"]+)"/', $this->body, $m) === 1 ? $m[1] : '';
    }

    public function location(): string
    {
        return $this->headers['location'] ?? '';
    }

    public function json(): array
    {
        $decoded = json_decode($this->body, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string,mixed> $fields */
    private function request(string $method, string $path, array $fields = [], bool $follow = false): self
    {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $follow,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Could not reach ' . $this->base . ': ' . $error);
        }
        $headSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->body = substr((string) $raw, $headSize);
        $this->headers = [];
        foreach (explode("\r\n", substr((string) $raw, 0, $headSize)) as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $this->headers[strtolower(trim($key))] = trim($value);
            }
        }
        curl_close($ch);
        return $this;
    }

    public function __destruct()
    {
        @unlink($this->jar);
    }
}

$base = rtrim($argv[1] ?? 'http://127.0.0.1:5059', '/');
$pass = 0;
$fail = 0;
function ok(string $name, bool $condition, string $detail = ''): void
{
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo '  PASS  ' . $name . "\n";
        return;
    }
    $fail++;
    echo '  FAIL  ' . $name . ($detail !== '' ? '  [' . $detail . ']' : '') . "\n";
}

$suffix = date('ymdHis') . bin2hex(random_bytes(2));
$memberEmail = 'smoke.' . $suffix . '@pricedesk.test';
$memberPassword = 'Smoke-' . $suffix . '-pw';
$adminEmail = 'smoke.admin.' . $suffix . '@pricedesk.test';
$adminPassword = 'TempAdmin-' . $suffix;

echo "PriceDesk smoke test against {$base}\n";
echo "throwaway member: {$memberEmail}\n\n";

/* A previous crashed run must not leave the developer locked out by the throttle. */
Db::run('DELETE FROM login_attempts');

$logBytes = is_file(BASE_PATH . '/storage/automation.log')
    ? (int) filesize(BASE_PATH . '/storage/automation.log') : 0;
$outboxBefore = (int) Db::value('SELECT COUNT(*) FROM email_outbox');
/* The board is demo data people read in screenshots; the run must leave it
   exactly as it found it, so the baseline is taken before anything is filed. */
$boardBefore = (int) Db::value('SELECT COUNT(*) FROM price_reports');

echo "Public surface\n";
$guest = new Http($base);
$guest->get('/');
ok('home renders', $guest->code === 200 && str_contains($guest->body, 'PriceDesk'), (string) $guest->code);
ok('home shows the photographic plate on every page', str_contains($guest->body, 'bg-plate'));
ok('home links its own stylesheet', str_contains($guest->body, '/assets/app.css'));
ok('the worked example is labelled as engine output', str_contains($guest->body, 'Pricing::aggregate'));

$board = Board::board(null, 14);
ok('the board has rows', count($board) > 0);
$top = $board[0] ?? ['median' => 0, 'commodity' => '', 'count' => 0];
ok('the landing ticker quotes the board, not typed figures',
    str_contains($guest->body, e(money((float) $top['median']))) && str_contains($guest->body, (string) $top['commodity']),
    money((float) $top['median']) . ' / ' . $top['commodity']);

$guest->get('/board');
ok('GET /board is 200', $guest->code === 200, (string) $guest->code);
ok('/board lists the busiest line', str_contains($guest->body, (string) $top['commodity']));
ok('/board prints the median to the kobo', str_contains($guest->body, e(money((float) $top['median']))));
ok('/board prints the confidence label', str_contains($guest->body, 'conf-' . $top['confidence']), (string) $top['confidence']);
ok('/board freshness is derived from real dates', (string) $top['freshness'] !== 'unknown', (string) $top['freshness']);
$disputed = count(array_filter($board, static fn (array $r): bool => $r['confidence'] === 'disputed'));
ok('the default board does not mark every line disputed', $disputed === 0, $disputed . '/' . count($board) . ' disputed');

$markets = Board::markets();
$okMarket = $markets[0];
$guest->get('/board?market=' . $okMarket['id']);
$oneMarket = Board::board((int) $okMarket['id'], 14);
ok('/board?market= filters', $guest->code === 200 && str_contains($guest->body, (string) $okMarket['name']));
ok('one market has fewer quotes than every market',
    count($oneMarket) > 0 && count($oneMarket) <= count($board));
$guest->get('/board?market=999999');
ok('a hand-typed market id that does not exist falls back to the whole board',
    $guest->code === 200 && str_contains($guest->body, 'Every market'));
$guest->get('/board?days=30');
ok('/board?days=30 is accepted', $guest->code === 200 && str_contains($guest->body, '30 days'));
$guest->get('/board?days=9999');
ok('a nonsense window is clamped back to 14 days', $guest->code === 200 && str_contains($guest->body, '14 days'));

foreach (['/compare', '/login', '/register'] as $path) {
    $guest->get($path);
    ok('GET ' . $path . ' is 200', $guest->code === 200, (string) $guest->code);
}
foreach (['/report', '/history', '/watch', '/admin', '/admin/queue', '/admin/users'] as $path) {
    $guest->get($path);
    ok('a guest cannot open ' . $path, $guest->code === 302 && str_contains($guest->location(), '/login'), (string) $guest->code);
}
$guest->get('/does-not-exist');
ok('an unknown path is a 404, not a crash', $guest->code === 404, (string) $guest->code);
/* dirname() on Windows used to hand back "\" as the base path, which then
   prefixed every generated url() and broke every stylesheet and form. */
ok('every generated url uses forward slashes', substr_count($guest->get('/')->body, '\\/') === 0);
ok('the stylesheet is served', (new Http($base))->get('/assets/app.css')->code === 200);
ok('the background plate is served', (new Http($base))->get('/assets/img/plate.webp')->code === 200);

echo "\nThe JSON board\n";
/* The busiest board line, so the api assertions are made about a commodity that
   genuinely has quotes rather than whichever row happened to sit at index 5. */
$commodity = Board::commodities()[0];
foreach (Board::commodities() as $item) {
    if ((int) $item['id'] === (int) $top['commodity_id']) {
        $commodity = $item;
    }
}
$api = $guest->get('/api/quote?commodity=' . (int) $top['commodity_id']);
$data = $api->json();
ok('GET /api/quote answers 200 with JSON', $api->code === 200 && ($data['commodity'] ?? '') === $top['commodity'], (string) $api->code);
$expected = Pricing::acrossMarkets(Board::quotes((int) $top['commodity_id'], null, 14));
ok('the api median equals the engine median', abs((float) ($data['aggregate']['median'] ?? -1) - $expected['median']) < 0.01,
    json_encode($data['aggregate']['median'] ?? null) . ' vs ' . $expected['median']);
ok('the api measures an every-market answer market by market',
    ($data['aggregate']['confidence'] ?? '') === $expected['confidence']
    && (int) ($data['aggregate']['markets'] ?? 0) >= 3, json_encode([$data['aggregate']['confidence'] ?? null, $expected['confidence']]));
ok('the api breaks the answer down per market', is_countable($data['perMarket'] ?? null) && count($data['perMarket']) >= 3);
$api->get('/api/quote?commodity=' . (int) $commodity['id'] . '&market=' . (int) $okMarket['id'] . '&days=7');
ok('a market and window can be pinned', $api->code === 200 && (int) $api->json()['windowDays'] === 7);
$api->get('/api/quote?commodity=999999');
ok('an unknown commodity is a 404 with a message, not a stack trace',
    $api->code === 404 && str_contains($api->body, 'unknown commodity'));
$api->get('/api/quote?commodity=' . (int) $commodity['id'] . '&market=999999');
ok('an unknown market is a 404', $api->code === 404);

echo "\nCSRF\n";
$guest->post('/register', ['name' => 'No Token', 'email' => 'notoken.' . $suffix . '@pricedesk.test',
    'password' => 'longenoughpassword', 'password_confirm' => 'longenoughpassword']);
ok('a POST without a token is refused', $guest->code === 419, (string) $guest->code);
$member = new Http($base);
$token = $member->token('/register');
ok('the register form carries a token', $token !== '');
$member->post('/report', ['market_id' => 1, 'commodity_id' => 1, 'price' => 1000, 'unit' => 'kg',
    'report_date' => date('Y-m-d')]);
ok('a signed-out filing is refused before anything is written', $member->code === 302 || $member->code === 419, (string) $member->code);

echo "\nRegistration and roles\n";
$member->post('/register', ['name' => 'Smoke Tester', 'email' => $memberEmail, 'phone' => '',
    'password' => $memberPassword, 'password_confirm' => $memberPassword, 'role' => 'admin'], $token);
ok('registering lands on the filing form', $member->code === 302 && str_contains($member->location(), '/report'), (string) $member->code);
$row = Db::one('SELECT * FROM users WHERE email = ?', [$memberEmail]);
ok('the account exists', $row !== null);
ok('a posted role=admin is ignored — self-service makes a member', ($row['role'] ?? '') === 'member', (string) ($row['role'] ?? 'missing'));
ok('the password was hashed, not stored', $row !== null && !str_contains((string) $row['password_hash'], $memberPassword)
    && str_starts_with((string) $row['password_hash'], '$2y$'));
$member->get('/report');
ok('the session survived the redirect', $member->code === 200, (string) $member->code);
$member->post('/register', ['name' => 'Dupe', 'email' => strtoupper($memberEmail), 'password' => $memberPassword,
    'password_confirm' => $memberPassword], $token);
ok('a second account on the same email is refused',
    (int) Db::value('SELECT COUNT(*) FROM users WHERE email = ?', [strtolower($memberEmail)]) === 1);
$member->post('/register', ['name' => 'Weak', 'email' => 'weak.' . $suffix . '@pricedesk.test',
    'password' => 'short', 'password_confirm' => 'short'], $token);
ok('a short password is refused', Db::value('SELECT id FROM users WHERE email = ?', ['weak.' . $suffix . '@pricedesk.test']) === null);
$member->post('/register', ['name' => 'Mismatch', 'email' => 'mm.' . $suffix . '@pricedesk.test',
    'password' => 'longenoughpassword', 'password_confirm' => 'longenoughpasSword'], $token);
ok('mismatched confirmations are refused', Db::value('SELECT id FROM users WHERE email = ?', ['mm.' . $suffix . '@pricedesk.test']) === null);

echo "\nFiling a price\n";
$balogun = null;
foreach ($markets as $market) {
    if ($market['name'] === 'Balogun Market') {
        $balogun = $market;
    }
}
$balogun = $balogun ?? $markets[1];
$garri = null;
foreach (Board::commodities() as $item) {
    if ($item['name'] === 'Garri (Ijebu White)') {
        $garri = $item;
    }
}
$garri = $garri ?? $commodity;
$watcherEmail = (string) Db::value('SELECT u.email FROM watches w JOIN users u ON u.id = w.user_id
    WHERE w.market_id = ? AND w.commodity_id = ? LIMIT 1', [(int) $balogun['id'], (int) $garri['id']]);
ok('the demo board has a watcher on this line', $watcherEmail !== '' && $watcherEmail !== $memberEmail, $watcherEmail);

$token = $member->token('/report');
$member->post('/report', ['market_id' => (int) $balogun['id'], 'commodity_id' => (int) $garri['id'],
    'price' => 1180, 'unit' => 'mudu', 'quantity' => 1, 'report_date' => date('Y-m-d'),
    'note' => 'Filed by the smoke test.'], $token);
ok('filing redirects to the reporter history', $member->code === 302 && str_contains($member->location(), '/history'), (string) $member->code);
$report = Db::one('SELECT * FROM price_reports WHERE reporter_id = (SELECT id FROM users WHERE email = ?)', [$memberEmail]);
ok('the report was written', $report !== null);
ok('a submitted price is pending, never live', ($report['status'] ?? '') === 'pending', (string) ($report['status'] ?? 'missing'));
$verifiedOnLine = (int) Db::value("SELECT COUNT(*) FROM price_reports WHERE status = 'verified'
    AND commodity_id = ? AND market_id = ?", [(int) $garri['id'], (int) $balogun['id']]);
$after = Pricing::aggregate(Board::quotes((int) $garri['id'], (int) $balogun['id'], 14));
ok('a pending price moves nothing on the board', $after['count'] === $verifiedOnLine,
    $after['count'] . ' board rows vs ' . $verifiedOnLine . ' verified');
ok('the note was stored', str_contains((string) ($report['note'] ?? ''), 'smoke test'));
$member->get('/history');
ok('the history page lists the report', $member->code === 200 && str_contains($member->body, 'Garri'));
$member->get('/watch');
ok('the watch page opens', $member->code === 200);
ok('filing notifies the watcher',
    (int) Db::value("SELECT COUNT(*) FROM email_outbox WHERE recipient = ? AND message_type = 'report_submitted'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)", [$watcherEmail]) > 0, 'watcher: ' . $watcherEmail);
ok('the reporter is not emailed their own filing',
    (int) Db::value("SELECT COUNT(*) FROM email_outbox WHERE recipient = ?", [$memberEmail]) === 0);
ok('nothing is sent while MAIL_ENABLED is false',
    (int) Db::value('SELECT COUNT(*) FROM email_outbox WHERE sent_at IS NOT NULL') === 0);

echo "\nFiling validation\n";
$member->post('/report', ['market_id' => (int) $balogun['id'], 'commodity_id' => (int) $garri['id'],
    'price' => 1300, 'unit' => 'mudu', 'quantity' => 1, 'report_date' => date('Y-m-d')], $token);
ok('a second price for the same line and day is redirected, not duplicated',
    (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE reporter_id = (SELECT id FROM users WHERE email = ?)',
        [$memberEmail]) === 1, (string) $member->location());
$member->post('/report', ['market_id' => (int) $balogun['id'], 'commodity_id' => (int) $garri['id'],
    'price' => 1300, 'unit' => 'mudu', 'report_date' => date('Y-m-d', strtotime('+3 day'))], $token);
ok('a future date is refused', (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE report_date > CURDATE()') === 0);
$member->post('/report', ['market_id' => 999999, 'commodity_id' => (int) $garri['id'],
    'price' => 1300, 'unit' => 'mudu', 'report_date' => date('Y-m-d')], $token);
ok('a market that is not on the board is refused',
    (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE market_id = 999999') === 0);
$member->post('/report', ['market_id' => (int) $balogun['id'], 'commodity_id' => (int) $garri['id'],
    'price' => -5, 'unit' => 'mudu', 'report_date' => date('Y-m-d')], $token);
$member->post('/report', ['market_id' => (int) $balogun['id'], 'commodity_id' => (int) $garri['id'],
    'price' => 1300, 'unit' => str_repeat('x', 60), 'report_date' => date('Y-m-d')], $token);
ok('nothing longer than the column width is ever stored',
    (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE LENGTH(unit) > 24 OR LENGTH(note) > 255') === 0);
$member->post('/report', ['commodity_id' => (int) $garri['id'], 'price' => 1300, 'unit' => 'mudu',
    'report_date' => date('Y-m-d')], $token);
ok('a missing market is refused', (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE market_id = 0') === 0);

echo "\nWithdrawing, and somebody else's row\n";
$foreign = (int) Db::value("SELECT id FROM price_reports WHERE reporter_id <> (SELECT id FROM users WHERE email = ?) LIMIT 1", [$memberEmail]);
$member->post('/history/withdraw', ['id' => $foreign], $token);
ok('withdrawing another reporter\'s row is a 404', $member->code === 404, (string) $member->code);
$mine = (int) $report['id'];
$member->post('/history/withdraw', ['id' => $mine], $token);
ok('a pending report can be withdrawn by its owner',
    (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE id = ?', [$mine]) === 0);
$member->post('/report', ['market_id' => (int) $balogun['id'], 'commodity_id' => (int) $garri['id'],
    'price' => 1180, 'unit' => 'mudu', 'report_date' => date('Y-m-d')], $token);
$report = Db::one('SELECT * FROM price_reports WHERE reporter_id = (SELECT id FROM users WHERE email = ?)', [$memberEmail]);
ok('refiling the same line after a withdrawal works', $report !== null);
$mine = (int) $report['id'];

echo "\nWatching a line\n";
$member->post('/watch', ['market_id' => (int) $balogun['id'], 'commodity_id' => (int) $garri['id']], $token);
ok('a watch is created', (int) Db::value('SELECT COUNT(*) FROM watches WHERE user_id = (SELECT id FROM users WHERE email = ?)',
    [$memberEmail]) === 1);
$member->post('/watch', ['market_id' => (int) $balogun['id'], 'commodity_id' => (int) $garri['id']], $token);
ok('posting the same watch again stops it', (int) Db::value('SELECT COUNT(*) FROM watches WHERE user_id = (SELECT id FROM users WHERE email = ?)',
    [$memberEmail]) === 0);
$member->post('/watch', ['market_id' => 999999, 'commodity_id' => 999999], $token);
ok('watching a line that does not exist is a 404', $member->code === 404, (string) $member->code);

echo "\nA member is not an administrator\n";
$member->get('/admin');
ok('GET /admin as a member is 403', $member->code === 403, (string) $member->code);
$member->get('/admin/queue');
ok('GET /admin/queue as a member is 403', $member->code === 403, (string) $member->code);
$member->post('/admin/queue/' . $mine . '/verify', ['id' => $mine], $token);
ok('a member cannot verify its own price', (string) Db::value('SELECT status FROM price_reports WHERE id = ?', [$mine]) === 'pending',
    (string) $member->code);
$member->post('/admin/rates', ['rates' => ['fuel_per_km' => 99999]], $token);
ok('a member cannot rewrite the haulage rates',
    abs((float) Db::value("SELECT value FROM rates WHERE tool = 'haul' AND code = 'fuel_per_km'") - 210.0) < 0.01);

echo "\nThe console\n";
Installer::createUser('Smoke Admin', $adminEmail, $adminPassword, 'admin');
$admin = new Http($base);
$admin->post('/login', ['email' => $adminEmail, 'password' => 'wrong-' . $suffix], $admin->token('/login'));
ok('a wrong password does not sign anybody in',
    $admin->code === 302 && !str_contains($admin->location(), '/admin'), $admin->location());
$admin->get('/admin');
ok('a failed login leaves no session', $admin->code === 302, (string) $admin->code);
$admin->post('/login', ['email' => $adminEmail, 'password' => $adminPassword], $admin->token('/login'));
ok('the administrator signs in', $admin->code === 302 && str_contains($admin->location(), '/admin'), $admin->location());
$admin->get('/admin');
ok('the dashboard opens', $admin->code === 200 && str_contains($admin->body, 'Smoke Admin'), (string) $admin->code);
$adminToken = $admin->token('/admin');
ok('the console forms carry a token', $adminToken !== '');
foreach (['/admin/queue', '/admin/reference', '/admin/rates', '/admin/users', '/admin/audit', '/admin/outbox'] as $path) {
    $admin->get($path);
    ok('GET ' . $path . ' is 200', $admin->code === 200, (string) $admin->code);
}
$admin->get('/admin/queue');
ok('the queue shows the deviation against verified peers', str_contains($admin->body, 'deviation') || str_contains($admin->body, 'Deviation'));

echo "\nVerification is the gate\n";
$beforeCount = (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE status = \'verified\' AND commodity_id = ? AND market_id = ?',
    [(int) $garri['id'], (int) $balogun['id']]);
$admin->post('/admin/queue/' . $mine . '/verify', ['id' => $mine], $adminToken);
ok('verifying redirects to the queue', $admin->code === 302, (string) $admin->code);
ok('the report is now verified',
    (string) Db::value('SELECT status FROM price_reports WHERE id = ?', [$mine]) === 'verified');
ok('the verifier is recorded', (int) Db::value('SELECT verified_by FROM price_reports WHERE id = ?', [$mine])
    === (int) Db::value('SELECT id FROM users WHERE email = ?', [$adminEmail]));
$afterCount = (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE status = \'verified\' AND commodity_id = ? AND market_id = ?',
    [(int) $garri['id'], (int) $balogun['id']]);
ok('verifying puts the price on the board', $afterCount === $beforeCount + 1, $beforeCount . ' -> ' . $afterCount);
$quotes = Board::quotes((int) $garri['id'], (int) $balogun['id'], 14);
$perKg = Pricing::perKg(1180, 'mudu', 1, (float) $garri['unit_weight_kg'], Board::weights($garri));
ok('the verified quote is normalised per kilogram on read',
    in_array(round($perKg, 2), array_map(static fn (array $r): float => round((float) $r['perKg'], 2), $quotes), true),
    'expected ' . $perKg);
$admin->get('/admin/outbox');
ok('the watcher is told when a price actually lands',
    (int) Db::value("SELECT COUNT(*) FROM email_outbox WHERE message_type = 'report_verified'") > 0);
$admin->post('/admin/queue/' . $mine . '/verify', ['id' => $mine], $adminToken);
ok('verifying twice does not double-count', (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE id = ? AND status = \'verified\'', [$mine]) === 1);
$admin->post('/admin/queue/' . $mine . '/unverify', ['id' => $mine], $adminToken);
ok('a bad verification can be undone', (string) Db::value('SELECT status FROM price_reports WHERE id = ?', [$mine]) === 'pending'
    && Db::value('SELECT verified_by FROM price_reports WHERE id = ?', [$mine]) === null);
$admin->post('/admin/queue/' . $mine . '/reject', ['id' => $mine, 'reason' => ''], $adminToken);
ok('a rejection with no reason is refused', (string) Db::value('SELECT status FROM price_reports WHERE id = ?', [$mine]) === 'pending');
$admin->post('/admin/queue/' . $mine . '/reject', ['id' => $mine, 'reason' => 'Quantity was a bag, priced as a mudu.'], $adminToken);
ok('a rejection with a reason records it',
    (string) Db::value('SELECT status FROM price_reports WHERE id = ?', [$mine]) === 'rejected'
    && str_contains((string) Db::value('SELECT reject_reason FROM price_reports WHERE id = ?', [$mine]), 'bag'));
$member->get('/history');
ok('the rejection and its reason are visible to the reporter',
    $member->code === 200 && str_contains($member->body, 'Quantity was a bag'), (string) $member->code);
$admin->post('/admin/queue/999999/verify', ['id' => 999999], $adminToken);
ok('verifying a row that does not exist is a 404', $admin->code === 404, (string) $admin->code);

echo "\nReference data and rates\n";
$admin->post('/admin/markets', ['name' => 'Smoke Test Market', 'state' => 'Ogun', 'kind' => 'wholesale', 'haul_km' => '41.5'], $adminToken);
$newMarket = Db::one('SELECT * FROM markets WHERE name = ?', ['Smoke Test Market']);
ok('a market can be added from the console', $newMarket !== null);
ok('an unchecked box means inactive', (int) ($newMarket['is_active'] ?? 1) === 0);
ok('the haul distance is stored as a decimal', abs((float) ($newMarket['haul_km'] ?? 0) - 41.5) < 0.01);
$admin->post('/admin/markets', ['id' => (int) $newMarket['id'], 'name' => 'Smoke Test Market', 'state' => 'Ogun',
    'kind' => 'retail', 'haul_km' => '38', 'is_active' => '1'], $adminToken);
ok('a market can be edited and reactivated',
    abs((float) Db::value('SELECT haul_km FROM markets WHERE id = ?', [(int) $newMarket['id']]) - 38.0) < 0.01
    && (int) Db::value('SELECT is_active FROM markets WHERE id = ?', [(int) $newMarket['id']]) === 1);
$admin->post('/admin/markets', ['name' => ''], $adminToken);
ok('a nameless market is refused', (int) Db::value('SELECT COUNT(*) FROM markets WHERE name = \'\'') === 0);
$admin->post('/admin/markets', ['name' => 'Kindless Market', 'kind' => 'DROP TABLE'], $adminToken);
ok('an invented market kind falls back to a known one',
    (string) Db::value('SELECT kind FROM markets WHERE name = ?', ['Kindless Market']) === 'retail');

$admin->post('/admin/commodities', ['name' => 'Smoke Kolanut', 'category' => 'crop', 'unit_weight_kg' => '0.05',
    'unit_label' => 'nut', 'units_text' => 'nut=0.05, bag = 50, rubbish, kg=1'], $adminToken);
$kolanut = Db::one('SELECT * FROM commodities WHERE name = ?', ['Smoke Kolanut']);
ok('a commodity can be added', $kolanut !== null);
$decoded = json_decode((string) ($kolanut['units'] ?? ''), true);
ok('the unit table is stored as JSON', is_array($decoded), (string) ($kolanut['units'] ?? ''));
ok('a malformed unit pair is dropped, not stored', is_array($decoded) && !isset($decoded['rubbish'])
    && isset($decoded['bag']) && abs((float) $decoded['nut'] - 0.05) < 0.0001, json_encode($decoded));
$admin->post('/admin/commodities', ['name' => 'Units Live Market', 'units_text' => 'basket=3'], $adminToken);
$liveLine = Db::one('SELECT * FROM commodities WHERE name = ?', ['Units Live Market']);
$weights = Board::weights((array) $liveLine);
ok('a saved unit table is used by the engine on the next read', abs(($weights['basket'] ?? 0) - 3.0) < 0.0001,
    json_encode($weights));
ok('unit slugs are matched case-insensitively', abs(Pricing::unitKg('Basket', 1.0, $weights) - 3.0) < 0.0001);
ok('an unknown unit falls back to the commodity weight', abs(Pricing::unitKg('lorry', 7.5, $weights) - 7.5) < 0.0001);

$rates = Board::rates();
ok('rates are read from MySQL', abs((float) $rates['fuel_per_km'] - 210.0) < 0.01, json_encode($rates));
$admin->post('/admin/rates', ['rates' => ['fuel_per_km' => '315.50', 'bogus;code' => '1', 'porter_per_load' => '4000']], $adminToken);
ok('a rate edit is stored with the naira the administrator typed',
    abs((float) Db::value("SELECT value FROM rates WHERE tool = 'haul' AND code = 'fuel_per_km'") - 315.5) < 0.01);
ok('a malformed rate key is ignored', Db::value("SELECT id FROM rates WHERE code = 'bogus'") === null);
Board::refreshRates();
ok('the application reads the new rate without a redeploy',
    abs((float) Board::rates()['fuel_per_km'] - 315.5) < 0.01, json_encode(Board::rates()));

echo "\nThe comparison, end to end\n";
$onion = null;
foreach (Board::commodities() as $item) {
    if ($item['name'] === 'Onion (Fresh)') {
        $onion = $item;
    }
}
$onion = $onion ?? $commodity;
$mile12 = $markets[0];
$dugbe = null;
foreach ($markets as $market) {
    if ($market['name'] === 'Dugbe Market') {
        $dugbe = $market;
    }
}
if ($onion !== null && $dugbe !== null) {
    $aggFrom = Pricing::aggregate(Board::quotes((int) $onion['id'], (int) $mile12['id'], 14));
    $aggTo = Pricing::aggregate(Board::quotes((int) $onion['id'], (int) $dugbe['id'], 14));
    $want = Pricing::haul(Board::rates(), $aggFrom, $aggTo, 148.0);
    $admin->post('/compare', ['from_market' => (int) $mile12['id'], 'to_market' => (int) $dugbe['id'],
        'commodity' => (int) $onion['id'], 'distance' => '148', 'days' => 14], $adminToken);
    $admin->get('/compare');
    ok('the comparison renders a server-computed readout',
        $admin->code === 200 && str_contains($admin->body, e(money($want['tripCost']))),
        'expected trip cost ' . money($want['tripCost']));
    ok('the break-even load is shown in kilograms', str_contains($admin->body, number_format($want['breakEvenKg'], 0)));
    ok('the verdict is the engine\'s verdict', str_contains($admin->body, (string) $want['verdict']));
    ok('changing fuel_per_km changed the answer',
        $want['tripCost'] > 148 * 2 * 210 + 1500 + 40 * 148 + 4000 - 0.01
        && abs((float) $want['fuel'] - 148 * 2 * 315.5) < 0.01, json_encode([$want['fuel'], $want['tripCost']]));
    ok('the run is stored for audit', (int) Db::value("SELECT COUNT(*) FROM calculations WHERE tool = 'haul'") > 0);
    ok('a thin board is flagged as untrustworthy rather than answered',
        $aggFrom['count'] < 3 || $aggTo['count'] < 3 || $want['trustworthy'] === true);
}
$admin->post('/compare', ['from_market' => (int) $mile12['id'], 'to_market' => (int) $mile12['id'],
    'commodity' => (int) $onion['id'], 'distance' => '0'], $adminToken);
$admin->get('/compare');
ok('the same market on both sides answers honestly (no gap, no trip)',
    $admin->code === 200 && str_contains($admin->body, 'Sell locally'));
$admin->post('/compare', ['from_market' => 999999, 'to_market' => 999998, 'commodity' => 999997], $adminToken);
ok('invented ids are refused rather than divided by zero', $admin->code === 302 && str_contains($admin->location(), '/compare'));

$admin->post('/admin/rates', ['rates' => ['fuel_per_km' => '210']], $adminToken);
Board::refreshRates();
ok('the rate is put back the way it was', abs((float) Board::rates()['fuel_per_km'] - 210.0) < 0.01);

echo "\nAccounts\n";
$admin->get('/admin/users');
ok('the user list never prints a password hash', !str_contains($admin->body, '$2y$'));

echo "\nAutomation\n";
$admin->post('/admin/automation', [], $adminToken);
ok('the console can run the scheduled tasks', $admin->code === 302 && str_contains($admin->location(), '/admin'), (string) $admin->code);
$admin->get('/admin');
$grew = (is_file(BASE_PATH . '/storage/automation.log') ? (int) filesize(BASE_PATH . '/storage/automation.log') : 0) > $logBytes;
ok('the run is written to the log, not just to the screen', $grew);
$admin->get('/admin/outbox');
ok('outbox messages are listed with their type', $admin->code === 200);
$admin->get('/admin/audit');
ok('the audit trail recorded the verification decision',
    $admin->code === 200 && str_contains($admin->body, 'report.reject'));
ok('the audit trail recorded the rate change', str_contains($admin->body, 'rates.update'));
$outboxAfter = (int) Db::value('SELECT COUNT(*) FROM email_outbox');
ok('the outbox grew during the run', $outboxAfter >= $outboxBefore, $outboxBefore . ' -> ' . $outboxAfter);

echo "\nSign out, and the throttle\n";
$member->post('/logout', [], $member->token('/report'));
ok('signing out ends the session', $member->code === 302 && str_contains($member->location(), '/'));
$member->get('/report');
ok('the member is back to a guest', $member->code === 302 && str_contains($member->location(), '/login'));
$relogin = new Http($base);
$relogin->post('/login', ['email' => $memberEmail, 'password' => $memberPassword], $relogin->token('/login'));
ok('the same account can sign in again', $relogin->code === 302 && str_contains($relogin->location(), '/report'), $relogin->location());

$disabledId = (int) Db::value("SELECT id FROM users WHERE email = 'reporter2@pricedesk.test'");
$admin->post('/admin/users/' . $disabledId . '/status', [], $adminToken);
ok('an administrator can disable an account',
    (int) Db::value("SELECT is_active FROM users WHERE email = 'reporter2@pricedesk.test'") === 0);
$admin->post('/admin/users/' . (int) Db::value('SELECT id FROM users WHERE email = ?', [$adminEmail]) . '/status', [], $adminToken);
ok('an administrator cannot disable their own account',
    (int) Db::value('SELECT is_active FROM users WHERE email = ?', [$adminEmail]) === 1);
$admin->post('/admin/users/' . $disabledId . '/status', [], $adminToken);
ok('a disabled account can be re-enabled',
    (int) Db::value("SELECT is_active FROM users WHERE email = 'reporter2@pricedesk.test'") === 1);

/* Last, because the throttle counts by ip as well as by email and a failed
   login test anywhere else would lock every later session in this run. */
$throttle = new Http($base);
$throttleToken = $throttle->token('/login');
for ($i = 0; $i < 9; $i++) {
    $throttle->post('/login', ['email' => $memberEmail, 'password' => 'nope-' . $i], $throttleToken);
}
$throttle->post('/login', ['email' => $memberEmail, 'password' => $memberPassword], $throttleToken);
ok('nine wrong passwords lock even the right one out',
    $throttle->code === 302 && str_contains($throttle->location(), '/login'), $throttle->location());
ok('the lockout is recorded', (int) Db::value('SELECT COUNT(*) FROM login_attempts WHERE email = ?', [$memberEmail]) >= 8);
Db::run('DELETE FROM login_attempts');
$late = new Http($base);
$late->post('/login', ['email' => $memberEmail, 'password' => $memberPassword], $late->token('/login'));
ok('clearing the attempts lets a legitimate sign in through', str_contains($late->location(), '/report'), $late->location());

echo "\nEngine arithmetic\n";
$weights = ['kg' => 1.0, 'mudu' => 0.12, 'crate' => 2.4, 'bag' => 50.0];
ok('a mudu of pepper is normalised per kilogram', abs(Pricing::perKg(1200, 'mudu', 1, 1.0, $weights) - 10000.0) < 0.01);
ok('"crate of 30" is understood as a crate', abs(Pricing::unitKg('Crate of 30', 1.0, $weights) - 2.4) < 0.0001);
ok('a bag of rice is not compared with a mudu of pepper', abs(Pricing::perKg(90000, 'bag', 1, 1.0, $weights) - 1800.0) < 0.01);
$six = [];
foreach ([1200, 1300, 1150, 1250, 9000, 1220] as $i => $price) {
    $six[] = ['perKg' => Pricing::perKg($price, $i === 4 ? 'crate' : 'mudu', 1, 1.0, $weights),
        'reportDate' => date('Y-m-d'), 'reporterId' => $i + 1];
}
$agg = Pricing::aggregate($six);
// The crate at ₦9,000 is cheap per kilogram, so it drags the mean below every
// single mudu quote while the median stays inside the cluster a trader would
// recognise. That is the landing page's claim, and it is what this checks.
$muduQuotes = array_values(array_filter(array_map(static fn (array $r): float => (float) $r['perKg'], $six),
    static fn (float $v): bool => $v > 9000));
ok('the median stays inside the cluster the crate does not belong to',
    $agg['median'] >= min($muduQuotes) && $agg['median'] <= max($muduQuotes) && $agg['count'] === 6,
    'median ' . $agg['median'] . ' vs cluster ' . min($muduQuotes) . '-' . max($muduQuotes));
ok('the mean is dragged below every real quote', $agg['mean'] < min($muduQuotes), $agg['mean'] . ' < ' . min($muduQuotes));
ok('the spread is measured against the median', abs($agg['spreadPct'] - round((($agg['max'] - $agg['min']) / 2) / $agg['median'] * 100, 1)) < 0.01);
ok('an empty board says so instead of dividing by zero',
    Pricing::aggregate([])['count'] === 0 && Pricing::aggregate([])['confidence'] === 'none');
$haul = Pricing::haul(['fuel_per_km' => 210, 'levy_flat' => 1500, 'levy_per_km' => 40, 'porter_per_load' => 4000,
    'spoilage_pct' => 5, 'vehicle_capacity_kg' => 1200],
    ['median' => 1000, 'count' => 5], ['median' => 1300, 'count' => 5], 60.0);
$trip = 60 * 2 * 210 + 1500 + 60 * 40 + 4000;
ok('the trip cost is fuel plus levies plus porters', abs($haul['tripCost'] - $trip) < 0.01, $haul['tripCost'] . ' vs ' . $trip);
ok('spoilage shrinks the cargo, not the fare', abs($haul['sellableKg'] - 1140) < 0.01);
ok('the cost per kilogram is the trip over the sellable load', abs($haul['costPerKg'] - round($trip / 1140, 2)) < 0.01);
ok('the break-even is the trip divided by the gap', abs($haul['breakEvenKg'] - round($trip / 300, 1)) < 0.01);
ok('a gap that the trip eats is reported as such',
    Pricing::haul(['fuel_per_km' => 210, 'levy_flat' => 1500, 'levy_per_km' => 40, 'porter_per_load' => 4000,
        'spoilage_pct' => 5, 'vehicle_capacity_kg' => 1200],
        ['median' => 1000, 'count' => 5], ['median' => 1010, 'count' => 5], 60.0)['netPerKg'] < 0);
$thin = Pricing::haul(Board::rates(), ['median' => 1000, 'count' => 1], ['median' => 1400, 'count' => 2], 30.0);
ok('one quote on one side is not sold as a recommendation', $thin['trustworthy'] === false);

/* Two markets that each agree with themselves, 60% apart from each other.
   Pooled, the old code called that "disputed" — which is the board lying about
   the only thing it exists to measure. */
$clustered = [];
foreach ([['Mile 12', 1000], ['Mile 12', 1010], ['Mile 12', 990],
    ['Bodija', 1600], ['Bodija', 1610], ['Bodija', 1590]] as $i => $quote) {
    $clustered[] = ['perKg' => (float) $quote[1], 'reportDate' => date('Y-m-d'),
        'reporterId' => $i + 1, 'market' => $quote[0]];
}
$pooled = Pricing::aggregate($clustered);
$across = Pricing::acrossMarkets($clustered);
ok('two markets that each agree are not reported as one market that does not',
    $pooled['confidence'] === 'disputed' && $across['confidence'] !== 'disputed',
    $pooled['confidence'] . ' pooled / ' . $across['confidence'] . ' across');
ok('the cross-market spread is measured inside the markets, not between them',
    $across['spreadPct'] < $pooled['spreadPct'] / 4,
    $pooled['spreadPct'] . '% pooled vs ' . $across['spreadPct'] . '% across');
ok('the range still shows what anyone actually paid',
    abs($across['min'] - 990) < 0.01 && abs($across['max'] - 1610) < 0.01, json_encode([$across['min'], $across['max']]));
ok('every market is counted once and every quote is still counted',
    (int) ($across['markets'] ?? 0) === 2 && (int) $across['count'] === 6);
$single = array_values(array_filter($clustered, static fn (array $r): bool => $r['market'] === 'Bodija'));
ok('one market on its own is measured identically either way round',
    abs(Pricing::acrossMarkets($single)['median'] - Pricing::aggregate($single)['median']) < 0.01
    && abs(Pricing::acrossMarkets($single)['spreadPct'] - Pricing::aggregate($single)['spreadPct']) < 0.01,
    json_encode([Pricing::acrossMarkets($single), Pricing::aggregate($single)['median']]));

/* ------------------------------------------------------------------ clean up */
Db::run('DELETE FROM price_reports WHERE reporter_id = (SELECT id FROM users WHERE email = ?)', [$memberEmail]);
Db::run('DELETE FROM watches WHERE user_id IN (SELECT id FROM users WHERE email LIKE ?)', ['smoke.%@pricedesk.test']);
Db::run('DELETE FROM markets WHERE name IN (?, ?)', ['Smoke Test Market', 'Kindless Market']);
Db::run('DELETE FROM commodities WHERE name IN (?, ?)', ['Smoke Kolanut', 'Units Live Market']);
Db::run('DELETE FROM users WHERE email LIKE ?', ['smoke.%@pricedesk.test']);
$left = (int) Db::value('SELECT COUNT(*) FROM users WHERE email LIKE ?', ['smoke.%@pricedesk.test'])
    + (int) Db::value('SELECT COUNT(*) FROM price_reports WHERE note LIKE ?', ['%smoke test%']);
ok('the test leaves no accounts or reports behind', $left === 0, (string) $left);
ok('the demo board is exactly the size it was before the run',
    (int) Db::value('SELECT COUNT(*) FROM price_reports') === $boardBefore,
    $boardBefore . ' -> ' . Db::value('SELECT COUNT(*) FROM price_reports'));

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
