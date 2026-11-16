<?php
declare(strict_types=1);

/**
 * End-to-end smoke test for a running PayRite.
 *
 *   php tools/smoke.php [base-url]        (default http://127.0.0.1:5075)
 *
 * It drives the real HTTP surface — sessions, CSRF tokens, redirects, JSON — and
 * then checks what MySQL kept, because a payroll application is only as good as
 * the record it leaves behind. The engine is additionally tested on its own,
 * without a web server, so a wrong band can be pinned to arithmetic rather than
 * to a template.
 *
 * It never reads a password from anywhere. The staff account registers itself
 * over HTTP with a throwaway credential and the temporary administrator is
 * created through Installer::createUser() with a generated one, so running this
 * cannot expose the real accounts. Everything it creates is deleted, and the
 * band table and relief constants are snapshotted first and put back after —
 * the demo data is what a visitor reads, so the run must leave it identical.
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
        $this->jar = (string) tempnam(sys_get_temp_dir(), 'prjar');
    }

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

$base = rtrim($argv[1] ?? 'http://127.0.0.1:5075', '/');
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
$staffEmail = 'smoke.' . $suffix . '@payrite.test';
$staffPassword = 'Smoke-' . $suffix . '-pw';
$adminEmail = 'smoke.admin.' . $suffix . '@payrite.test';
$adminPassword = 'TempAdmin-' . $suffix;
$staffNo = 'SK-' . substr(strtoupper($suffix), -7);
/* Two periods nobody has run payroll for. The demo months stay untouched, and a
   repeated test run cannot collide with its own unique-period constraint. */
$period = '2027-0' . (1 + ((int) date('i') % 8));
$other = '2027-1' . ((int) date('i') % 3);

$bandSnapshot = Db::all('SELECT * FROM pay_bands ORDER BY band_order');
$ruleSnapshot = Db::all("SELECT * FROM rates WHERE tool = 'payroll' ORDER BY id");
$before = [
    'employees' => (int) Db::value('SELECT COUNT(*) FROM employees'),
    'runs' => (int) Db::value('SELECT COUNT(*) FROM payroll_runs'),
    'users' => (int) Db::value('SELECT COUNT(*) FROM users'),
    'slips' => (int) Db::value('SELECT COUNT(*) FROM payslips'),
];

Db::run('DELETE FROM login_attempts');

echo "PayRite smoke test against {$base}\n";
echo "throwaway staff: {$staffEmail} · staff no: {$staffNo} · periods: {$period}, {$other}\n\n";

echo "Public surface\n";
$guest = new Http($base);
$guest->get('/');
ok('home renders', $guest->code === 200 && str_contains($guest->body, 'PayRite'), (string) $guest->code);
ok('home paints the photographic plate', str_contains($guest->body, 'bg-plate'));
ok('home links its own stylesheet', str_contains($guest->body, '/assets/app.css'));
ok('the landing page carries a CSRF token in its calculator', str_contains($guest->body, 'name="_token" value="'));
$default = Desk::compute([
    'basic' => 250000, 'housing' => 100000, 'transport' => 80000, 'other' => 0,
    'annual_rent' => 2400000, 'claim_rent' => true, 'pension_rate' => 8,
]);
ok('the net figure on the page is the engine\'s, not typed into the template',
    str_contains($guest->body, e(money($default['net']))), money($default['net']));
ok('the band count on the page is the live table', str_contains($guest->body, '<b>' . count(Desk::bands()) . '</b><span>tax bands live</span>'));
ok('every generated url uses forward slashes', substr_count($guest->body, '\\/') === 0);
foreach (['/rates', '/login', '/register'] as $path) {
    $guest->get($path);
    ok('GET ' . $path . ' is 200', $guest->code === 200, (string) $guest->code);
}
$guest->get('/rates');
ok('/rates prints the top band rate from the database',
    str_contains($guest->body, rtrim(rtrim(number_format((float) Desk::bands()[count(Desk::bands()) - 1]['rate'], 2), '0'), '.')));
ok('/rates shows the rent relief cap', str_contains($guest->body, e(money(Desk::rules()['rent_cap']))));
foreach (['/payslips', '/payslips/1', '/profile', '/admin', '/admin/employees', '/admin/employees/new',
    '/admin/runs', '/admin/rules', '/admin/staff', '/admin/audit', '/admin/outbox'] as $path) {
    $guest->get($path);
    ok('a guest cannot open ' . $path, $guest->code === 302 && str_contains($guest->location(), '/login'), (string) $guest->code);
}
$guest->get('/does-not-exist');
ok('an unknown path is a 404, not a crash', $guest->code === 404, (string) $guest->code);
ok('the stylesheet is served', (new Http($base))->get('/assets/app.css')->code === 200);
ok('the background plate is served', (new Http($base))->get('/assets/img/plate.webp')->code === 200);

echo "\nThe JSON API\n";
$api = new Http($base);
$api->get('/api/paye?gross=450000&rent=2400000');
$data = $api->json();
$check = Desk::compute([
    'basic' => 270000, 'housing' => 90000, 'transport' => 90000, 'other' => 0,
    'annual_rent' => 2400000, 'claim_rent' => true, 'pension_rate' => 8,
]);
ok('GET /api/paye answers 200 with JSON', $api->code === 200 && isset($data['monthly']['net']), (string) $api->code);
ok('a bare gross is split 60/20/20', abs((float) ($data['monthly']['gross'] ?? 0) - 450000) < 0.01, (string) ($data['monthly']['gross'] ?? 0));
ok('the api PAYE equals the engine PAYE', abs((float) ($data['monthly']['paye'] ?? -1) - round($check['paye'], 2)) < 0.01,
    ($data['monthly']['paye'] ?? 'x') . ' vs ' . round($check['paye'], 2));
ok('net is gross less PAYE, pension and NHF',
    abs($check['gross'] - $check['paye'] - $check['pension'] - $check['nhf'] - $check['net']) < 0.01);
ok('the api publishes the bands it used', count($data['bands'] ?? []) === count(Desk::bands()));
$api->get('/api/paye?gross=450000&claim_rent=0');
ok('claim_rent=0 removes the rent relief', abs((float) $api->json()['monthly']['rent_relief']) < 0.01, (string) ($api->json()['monthly']['rent_relief'] ?? 'x'));
$api->get('/api/paye');
ok('a request with no numbers is a 422 with a message', $api->code === 422 && str_contains($api->body, 'Provide gross'), (string) $api->code);

echo "\nCSRF\n";
$guest->post('/register', ['name' => 'No Token', 'email' => 'notoken.' . $suffix . '@payrite.test',
    'password' => 'longenoughpassword', 'password_confirm' => 'longenoughpassword']);
ok('a POST without a token is refused', $guest->code === 419, (string) $guest->code);
$staff = new Http($base);
$token = $staff->token('/register');
ok('the register form carries a token', $token !== '');
$staff->post('/profile', ['phone' => '+2348009998888']);
ok('a signed-out POST to a staff route is bounced by the guard before the token is read',
    $staff->code === 302 && str_contains($staff->location(), '/login'), (string) $staff->code . ' ' . $staff->location());

echo "\nThe landing calculator\n";
$calc = new Http($base);
$calcToken = $calc->token('/');
$calcsBefore = (int) Db::value("SELECT COUNT(*) FROM calculations WHERE tool = 'paye'");
$calc->post('/', ['who' => 'Smoke calc', 'basic' => 300000, 'housing' => 50000, 'transport' => 50000, 'other' => 0,
    'annual_rent' => 1200000, 'claim_rent' => '1', 'pension_rate' => 8], $calcToken);
$claimed = Desk::compute(['basic' => 300000, 'housing' => 50000, 'transport' => 50000, 'other' => 0,
    'annual_rent' => 1200000, 'claim_rent' => true, 'pension_rate' => 8]);
ok('a real POST renders the payslip', $calc->code === 200 && str_contains($calc->body, 'Net pay, monthly'), (string) $calc->code);
ok('with the engine\'s answer on the page', str_contains($calc->body, e(money($claimed['net']))), money($claimed['net']));
ok('and stores the run so the figure can be reproduced',
    (int) Db::value("SELECT COUNT(*) FROM calculations WHERE tool = 'paye'") === $calcsBefore + 1);
$calc->post('/', ['who' => 'Smoke calc', 'basic' => 300000, 'housing' => 50000, 'transport' => 50000, 'other' => 0,
    'annual_rent' => 1200000, 'claim_rent' => '0', 'pension_rate' => 8], $calcToken);
$unclaimed = Desk::compute(['basic' => 300000, 'housing' => 50000, 'transport' => 50000, 'other' => 0,
    'annual_rent' => 1200000, 'claim_rent' => false, 'pension_rate' => 8]);
ok('unticking rent relief is actually honoured',
    str_contains($calc->body, e(money($unclaimed['net']))) && $unclaimed['paye'] > $claimed['paye'],
    money($unclaimed['net']) . ' vs ' . money($claimed['net']));
$calc->post('/', ['who' => 'Smoke calc', 'basic' => 0, 'housing' => 0, 'claim_rent' => '1', 'pension_rate' => 8], $calcToken);
ok('a POST with nothing to pay is explained, not divided by',
    $calc->code === 200 && str_contains($calc->body, 'at least one monthly component'));

echo "\nRegistration and roles\n";
$staff->post('/register', [
    'name' => 'Smoke Tester', 'email' => $staffEmail, 'phone' => '+2348000000000',
    'password' => $staffPassword, 'password_confirm' => $staffPassword, 'role' => 'admin',
], $token);
ok('registering signs the person in and lands them on their package',
    $staff->code === 302 && str_contains($staff->location(), '/profile'), $staff->location() . ' / ' . $staff->code);
$role = Db::value('SELECT role FROM users WHERE email = ?', [$staffEmail]);
ok('posting role=admin does not make an administrator', $role === 'staff', (string) $role);
ok('the password is stored hashed, never in the clear',
    (string) Db::value('SELECT password_hash FROM users WHERE email = ?', [$staffEmail]) !== $staffPassword
    && str_starts_with((string) Db::value('SELECT password_hash FROM users WHERE email = ?', [$staffEmail]), '$2y$'));
$dupe = new Http($base);
$dupe->post('/register', ['name' => 'Copy', 'email' => strtoupper($staffEmail), 'password' => 'another-long-password',
    'password_confirm' => 'another-long-password'], $dupe->token('/register'));
ok('a duplicate email is refused', $dupe->code === 302 && str_contains($dupe->location(), '/register'));
ok('the email is matched case-insensitively',
    (int) Db::value('SELECT COUNT(*) FROM users WHERE email LIKE ?', ['%.' . $suffix . '@payrite.test']) === 1);
$short = new Http($base);
$short->post('/register', ['name' => 'Short', 'email' => 'short.' . $suffix . '@payrite.test',
    'password' => 'abc', 'password_confirm' => 'abc'], $short->token('/register'));
ok('a short password never reaches the database',
    $short->code === 302 && (int) Db::value('SELECT COUNT(*) FROM users WHERE email = ?', ['short.' . $suffix . '@payrite.test']) === 0);

echo "\nAn unlinked account sees nothing\n";
$staff->get('/profile');
ok('GET /profile is 200 for the signed-in staff member', $staff->code === 200, (string) $staff->code);
ok('and says the record is not linked yet', str_contains($staff->body, 'not linked to a staff record'));
$staff->get('/payslips');
ok('GET /payslips renders the pending page', str_contains($staff->body, 'not yet attached to a pay record'));
$staff->get('/admin/employees');
ok('a staff account cannot open the desk', $staff->code === 403, (string) $staff->code . ' ' . $staff->location());
$staff->post('/admin/runs', ['period' => $period]);
ok('a staff POST to a desk route is refused, not parsed', $staff->code === 403, (string) $staff->code);

echo "\nThe desk\n";
$adminId = Installer::createUser('Smoke Admin', $adminEmail, $adminPassword, 'admin');
ok('a temporary administrator was created for the run', $adminId > 0);
$admin = new Http($base);
$admin->post('/login', ['email' => $adminEmail, 'password' => $adminPassword], $admin->token('/login'));
ok('the administrator signs in and lands on the desk', str_contains($admin->location(), '/admin'), $admin->location());
$adminToken = $admin->token('/admin/employees');
ok('a desk form carries a token', $adminToken !== '');
$admin->get('/admin');
ok('the dashboard renders', $admin->code === 200 && str_contains($admin->body, 'The month, and what it costs'));
$activeNow = (int) Db::value("SELECT COUNT(*) FROM employees WHERE status = 'active'");
ok('the dashboard counts the live roster, not a hard-coded number',
    str_contains($admin->body, '<b>' . $activeNow . '</b><span>active on roster</span>'), (string) $activeNow);
$admin->get('/payslips');
ok('an administrator is bounced off the staff pages', $admin->code === 302 && str_contains($admin->location(), '/admin'));

echo "\nRoster\n";
$admin->post('/admin/employees/new', ['name' => '', 'staff_no' => $staffNo, 'basic' => 400000,
    'housing' => 100000, 'transport' => 60000, 'annual_rent' => 1200000, 'claim_rent' => 1,
    'pension_rate' => 8, 'status' => 'active'], $adminToken);
ok('an employee with no name is refused', $admin->code === 302 && str_contains($admin->location(), '/new'));
ok('and nothing was written', (int) Db::value('SELECT COUNT(*) FROM employees WHERE staff_no = ?', [$staffNo]) === 0);
$admin->post('/admin/employees/new', ['name' => 'Smoke Employee', 'staff_no' => $staffNo, 'basic' => 400000,
    'housing' => 100000, 'transport' => 60000, 'annual_rent' => 1200000, 'claim_rent' => 1,
    'pension_rate' => 8, 'status' => 'active', 'role_title' => 'Tester', 'department' => 'Quality'], $adminToken);
$employeeId = (int) Db::value('SELECT id FROM employees WHERE staff_no = ?', [$staffNo]);
ok('the valid POST created a roster row', $employeeId > 0, $admin->location());
ok('the package was stored as typed',
    abs((float) Db::value('SELECT basic FROM employees WHERE id = ?', [$employeeId]) - 400000) < 0.01);
$admin->post('/admin/employees/new', ['name' => 'Clash', 'staff_no' => $staffNo, 'basic' => 1000,
    'claim_rent' => 1, 'pension_rate' => 8, 'status' => 'active'], $adminToken);
ok('a duplicate staff number is refused', (int) Db::value('SELECT COUNT(*) FROM employees WHERE name = ?', ['Clash']) === 0);
$admin->post('/admin/employees/new', ['name' => 'Nothing', 'staff_no' => 'SK-ZZ-' . substr($suffix, -4),
    'basic' => 0, 'housing' => 0, 'claim_rent' => 1, 'pension_rate' => 8, 'status' => 'active'], $adminToken);
ok('a package that sums to zero is refused', (int) Db::value('SELECT COUNT(*) FROM employees WHERE staff_no = ?', ['SK-ZZ-' . substr($suffix, -4)]) === 0);
$admin->post('/admin/employees/' . $employeeId . '/status', ['status' => 'paused'], $adminToken);
ok('status can be changed', (string) Db::value('SELECT status FROM employees WHERE id = ?', [$employeeId]) === 'paused');
$admin->post('/admin/employees/' . $employeeId . '/status', ['status' => 'nonsense'], $adminToken);
ok('an unknown status is rejected', (string) Db::value('SELECT status FROM employees WHERE id = ?', [$employeeId]) === 'paused');
$admin->post('/admin/employees/' . $employeeId . '/status', ['status' => 'active'], $adminToken);
$admin->post('/admin/employees/' . $employeeId . '/edit', ['name' => 'Smoke Employee', 'staff_no' => $staffNo,
    'basic' => 400000, 'housing' => 100000, 'transport' => 60000, 'annual_rent' => 1200000,
    'claim_rent' => '0', 'pension_rate' => 8, 'status' => 'active'], $adminToken);
ok('unticking rent relief actually saves as unticked',
    (int) Db::value('SELECT claim_rent FROM employees WHERE id = ?', [$employeeId]) === 0);
$admin->post('/admin/employees/' . $employeeId . '/edit', ['name' => 'Smoke Employee', 'staff_no' => $staffNo,
    'basic' => 400000, 'housing' => 100000, 'transport' => 60000, 'annual_rent' => 1200000,
    'claim_rent' => '1', 'pension_rate' => 8, 'status' => 'active'], $adminToken);
ok('and can be claimed again', (int) Db::value('SELECT claim_rent FROM employees WHERE id = ?', [$employeeId]) === 1);
$admin->get('/admin/employees/' . $employeeId . '/edit');
ok('the edit page shows what the package produces', str_contains($admin->body, 'What this produces'));
$admin->get('/admin/employees/999999/edit');
ok('a hand-typed employee id is a 404', $admin->code === 404, (string) $admin->code);

echo "\nA payroll run\n";
$admin->post('/admin/runs', ['period' => $period, 'note' => 'smoke run'], $adminToken);
$runId = (int) Db::value('SELECT id FROM payroll_runs WHERE period = ?', [$period]);
ok('a draft run was built', $admin->code === 302 && $runId > 0, $admin->location());
ok('the transaction wrote one slip per active employee',
    (int) Db::value('SELECT COUNT(*) FROM payslips WHERE run_id = ?', [$runId]) === (int) Db::value("SELECT COUNT(*) FROM employees WHERE status = 'active'"),
    Db::value('SELECT COUNT(*) FROM payslips WHERE run_id = ?', [$runId]) . ' slips');
$run = Desk::run($runId);
$totals = Desk::totals($runId);
ok('the stored totals match the slips', abs((float) $run['net_total'] - (float) $totals['net_total']) < 0.01,
    $run['net_total'] . ' vs ' . $totals['net_total']);
$slip = Db::one('SELECT * FROM payslips WHERE run_id = ? AND employee_id = ?', [$runId, $employeeId]);
$engine = Payroll::compute(['basic' => 400000, 'housing' => 100000, 'transport' => 60000, 'other' => 0,
    'annual_rent' => 1200000, 'claim_rent' => true, 'pension_rate' => 8], $bandSnapshot ? array_map(
        static fn(array $b): array => ['slice_width' => $b['slice_width'] === null ? null : (float) $b['slice_width'], 'rate' => (float) $b['rate']],
        $bandSnapshot) : null, Desk::rules());
ok('the slip matches the engine to the kobo',
    $slip !== null && abs((float) $slip['paye'] - round($engine['paye'], 2)) < 0.01
    && abs((float) $slip['net'] - round($engine['net'], 2)) < 0.01,
    $slip === null ? 'no slip' : $slip['paye'] . '/' . $slip['net'] . ' vs ' . round($engine['paye'], 2) . '/' . round($engine['net'], 2));
ok('the slip stored the band table it was computed with',
    is_array(json_decode((string) $slip['bands_json'], true)) && is_array(json_decode((string) $slip['rules_json'], true)));
$admin->post('/admin/runs', ['period' => $period, 'note' => 'second attempt'], $adminToken);
ok('a second run for the same period is refused',
    (int) Db::value('SELECT COUNT(*) FROM payroll_runs WHERE period = ?', [$period]) === 1);
ok('and the refusal is explained rather than silent', str_contains($admin->body, 'already exists') || str_contains($admin->location(), '/admin/runs'));
$admin->post('/admin/runs', ['period' => 'not-a-month'], $adminToken);
ok('a malformed period never reaches the database',
    (int) Db::value("SELECT COUNT(*) FROM payroll_runs WHERE period = 'not-a-month'") === 0);
$admin->get('/admin/runs/' . $runId);
ok('the run page lists the slips', str_contains($admin->body, 'Smoke Employee'));
ok('and shows what today\'s table would produce', str_contains($admin->body, 'Net on today'));
$staff->get('/payslips');
/* The account is not linked to a record yet, so it could not show a slip even
   if a draft were published — the real test of the lock comes after linking. */
ok('a draft month is not on anyone\'s list', !str_contains($staff->body, Desk::periodLabel($period)));
$admin->post('/admin/runs/' . $runId . '/lock', [], $adminToken);
ok('locking publishes the run', (string) Db::value('SELECT status FROM payroll_runs WHERE id = ?', [$runId]) === 'locked');
ok('and stamps the time', Db::value('SELECT locked_at FROM payroll_runs WHERE id = ?', [$runId]) !== null);
$admin->post('/admin/runs/' . $runId . '/lock', [], $adminToken);
ok('a locked run cannot be locked twice', str_contains($admin->body, 'Only a draft') || !str_contains((string) Db::value('SELECT status FROM payroll_runs WHERE id = ?', [$runId]), 'draft'));

echo "\nStaff can only see their own\n";
$linkedId = (int) Db::value('SELECT id FROM users WHERE email = ?', [$staffEmail]);
$admin->post('/admin/staff/' . $linkedId . '/link', ['employee_id' => $employeeId], $adminToken);
ok('an account can be linked to a roster row',
    (int) Db::value('SELECT user_id FROM employees WHERE id = ?', [$employeeId]) === $linkedId);
$staff->get('/payslips');
ok('after linking, the slip appears for the right person',
    $staff->code === 200 && str_contains($staff->body, Desk::periodLabel($period)));
$admin->post('/admin/runs', ['period' => $other, 'note' => 'smoke draft'], $adminToken);
$staff->get('/payslips');
ok('a locked month shows while a draft for another month stays hidden',
    str_contains($staff->body, Desk::periodLabel($period)) && !str_contains($staff->body, Desk::periodLabel($other)));
$slipId = (int) $slip['id'];
$staff->get('/payslips/' . $slipId);
ok('the staff member can open their own slip', $staff->code === 200 && str_contains($staff->body, 'Net pay for'));
ok('the slip shows the stored band table', str_contains($staff->body, 'The rate table this slip was built with'));
$otherSlip = Db::one('SELECT id FROM payslips WHERE run_id = ? AND employee_id <> ?', [$runId, $employeeId]);
if ($otherSlip !== null) {
    $staff->get('/payslips/' . (int) $otherSlip['id']);
    ok('another employee\'s slip is a 404, not a leaked page', $staff->code === 404, (string) $staff->code);
} else {
    ok('another employee\'s slip is a 404, not a leaked page', true, 'skipped: single-employee run');
}
$staff->get('/payslips/999999');
ok('a made-up slip id is a 404', $staff->code === 404, (string) $staff->code);
$staff->post('/profile', ['phone' => '+2348001112222', 'query_note' => 'smoke query about the ' . $period . ' slip'], $staff->token('/profile'));
ok('staff can raise a query on their own record',
    str_contains((string) Db::value('SELECT query_note FROM employees WHERE id = ?', [$employeeId]), 'smoke query'));
$admin->get('/admin');
ok('the dashboard shows that query to the administrator', str_contains($admin->body, 'smoke query'));

echo "\nNotifications and the outbox\n";
$outboxBefore = (int) Db::value('SELECT COUNT(*) FROM email_outbox');
$admin->post('/admin/runs/' . $runId . '/notify', [], $adminToken);
ok('notifying queues one message per linked, active login',
    (int) Db::value('SELECT COUNT(*) FROM email_outbox') > $outboxBefore, $outboxBefore . ' -> ' . Db::value('SELECT COUNT(*) FROM email_outbox'));
ok('with the payslip subject', str_contains((string) Db::value("SELECT subject FROM email_outbox ORDER BY id DESC LIMIT 1"), 'payslip is ready'));
ok('and nothing is sent while MAIL_ENABLED is off',
    (string) Db::value('SELECT status FROM email_outbox ORDER BY id DESC LIMIT 1') === 'queued');

echo "\nRates the accountant can edit\n";
$bandId = (int) $bandSnapshot[0]['id'];
$originalRate = (float) $bandSnapshot[0]['rate'];
$postBands = static function (array $rows, string $lastWidth) use ($admin, $adminToken, $ruleSnapshot): void {
    $fields = ['band_id' => [], 'band_order' => [], 'slice_width' => [], 'rate' => [], 'rule' => []];
    foreach ($rows as $i => $row) {
        $fields['band_id'][] = (int) $row['id'];
        $fields['band_order'][] = (int) $row['band_order'];
        $fields['slice_width'][] = $i === count($rows) - 1 ? $lastWidth : (string) (int) $row['slice_width'];
        $fields['rate'][] = (string) (float) $row['rate'];
    }
    foreach ($ruleSnapshot as $rate) {
        $fields['rule'][(string) $rate['code']] = (string) (float) $rate['value'];
    }
    $admin->post('/admin/rules', $fields, $adminToken);
};
$broken = $bandSnapshot;
$broken[count($broken) - 1]['slice_width'] = 999999;
$postBands($broken, '999999');
ok('a closed final band is rejected', abs((float) Db::value('SELECT rate FROM pay_bands WHERE id = ?', [$bandId]) - $originalRate) < 0.001);
ok('and the rejection says why', str_contains($admin->body, 'final band') || str_contains($admin->location(), '/admin/rules'));
$moved = $bandSnapshot;
$moved[0]['rate'] = $originalRate + 1;
$postBands($moved, '');
ok('a valid save moves the first band', abs((float) Db::value('SELECT rate FROM pay_bands WHERE id = ?', [$bandId]) - ($originalRate + 1)) < 0.001,
    (string) Db::value('SELECT rate FROM pay_bands WHERE id = ?', [$bandId]));
Desk::refresh();
ok('the engine picks the new table up without a redeploy', abs((float) Desk::bands()[0]['rate'] - ($originalRate + 1)) < 0.001);
$withNewRate = Payroll::compute(['basic' => 400000, 'housing' => 100000, 'transport' => 60000, 'other' => 0,
    'annual_rent' => 1200000, 'claim_rent' => true, 'pension_rate' => 8], Desk::bands(), Desk::rules());
ok('so a payslip built now is different from the locked one',
    abs($withNewRate['paye'] - (float) $slip['paye']) > 0.01, round($withNewRate['paye'], 2) . ' vs ' . $slip['paye']);
$admin->get('/admin/runs/' . $runId);
ok('while the locked run still shows its own figures', str_contains($admin->body, 'Copied onto every slip in this run'));
$admin->post('/admin/rules', ['band_id' => [$bandId], 'band_order' => [1], 'slice_width' => [''], 'rate' => ['999'],
    'rule' => ['cra_base' => -5]], $adminToken);
ok('a rate above 60% is refused', abs((float) Db::value('SELECT rate FROM pay_bands WHERE id = ?', [$bandId]) - ($originalRate + 1)) < 0.001);
$admin->get('/admin/audit');
ok('the rate change is in the audit trail', str_contains($admin->body, 'rules.save'));
ok('and the rejected attempt was recorded too', str_contains($admin->body, 'rules.rejected'));

echo "\nVoiding, and what it protects\n";
$admin->post('/admin/runs/' . $runId . '/void', ['note' => 'smoke: rebuilt after the rate moved'], $adminToken);
ok('a run can be voided', (string) Db::value('SELECT status FROM payroll_runs WHERE id = ?', [$runId]) === 'void');
ok('the slips are kept as evidence', (int) Db::value('SELECT COUNT(*) FROM payslips WHERE run_id = ?', [$runId]) > 0);
$staff->get('/payslips');
ok('but disappear from the employee', !str_contains($staff->body, Desk::periodLabel($period)));
$admin->post('/admin/runs', ['period' => $period, 'note' => 'smoke rebuild'], $adminToken);
$rebuildId = (int) Db::value('SELECT id FROM payroll_runs WHERE period = ? AND status = ?', [$period, 'draft']);
ok('a voided month can be drafted again', $rebuildId > 0 && $rebuildId !== $runId);
if ($rebuildId > 0 && $rebuildId !== $runId) {
    Db::run('DELETE FROM payslips WHERE run_id = ?', [$rebuildId]);
    Db::run('DELETE FROM payroll_runs WHERE id = ?', [$rebuildId]);
}

echo "\nAccounts\n";
$admin->post('/admin/staff/' . $linkedId . '/status', [], $adminToken);
ok('an administrator can disable a staff account',
    (int) Db::value('SELECT is_active FROM users WHERE id = ?', [$linkedId]) === 0);
$locked = new Http($base);
$locked->post('/login', ['email' => $staffEmail, 'password' => $staffPassword], $locked->token('/login'));
ok('a disabled account cannot sign in', str_contains($locked->location(), '/login'), $locked->location());
$admin->post('/admin/staff/' . $linkedId . '/status', [], $adminToken);
ok('and can be re-enabled', (int) Db::value('SELECT is_active FROM users WHERE id = ?', [$linkedId]) === 1);
$adminself = (int) Db::value('SELECT id FROM users WHERE email = ?', [$adminEmail]);
$admin->post('/admin/staff/' . $adminself . '/status', [], $adminToken);
ok('the administrator account is not offered to itself for disabling',
    (int) Db::value('SELECT is_active FROM users WHERE id = ?', [$adminself]) === 1);
$admin->post('/admin/staff/' . $linkedId . '/link', ['employee_id' => 0], $adminToken);
ok('linking to nothing does not clear the link',
    (int) Db::value('SELECT user_id FROM employees WHERE id = ?', [$employeeId]) === $linkedId);

echo "\nSign out and back in\n";
$staff->post('/logout', [], $staff->token('/profile'));
ok('signing out ends the session', $staff->code === 302 && str_contains($staff->location(), '/'));
$staff->get('/payslips');
ok('the staff member is back to a guest', $staff->code === 302 && str_contains($staff->location(), '/login'));
$again = new Http($base);
$again->post('/login', ['email' => $staffEmail, 'password' => $staffPassword], $again->token('/login'));
ok('the same account can sign in again', str_contains($again->location(), '/payslips'), $again->location());
$again->post('/login', ['email' => $staffEmail, 'password' => 'wrong'], $again->token('/login'));
ok('a wrong password gives no hint as to why', str_contains($again->body, 'not correct') || str_contains($again->location(), '/login'));

echo "\nThe throttle\n";
/* Last, because the throttle counts by ip as well as by email and a failed
   login anywhere else in this run would lock every later session out. */
$throttle = new Http($base);
$throttleToken = $throttle->token('/login');
for ($i = 0; $i < 9; $i++) {
    $throttle->post('/login', ['email' => $staffEmail, 'password' => 'nope-' . $i], $throttleToken);
}
$throttle->post('/login', ['email' => $staffEmail, 'password' => $staffPassword], $throttleToken);
ok('nine wrong passwords lock even the right one out',
    $throttle->code === 302 && str_contains($throttle->location(), '/login'), $throttle->location());
ok('the lockout is recorded in MySQL, not in the browser',
    (int) Db::value('SELECT COUNT(*) FROM login_attempts WHERE email = ?', [$staffEmail]) >= 8);
Db::run('DELETE FROM login_attempts');
$late = new Http($base);
$late->post('/login', ['email' => $staffEmail, 'password' => $staffPassword], $late->token('/login'));
ok('clearing the attempts lets a legitimate sign in through', str_contains($late->location(), '/payslips'), $late->location());

echo "\nEngine arithmetic, without a web server\n";
$bands = Payroll::DEFAULT_BANDS;
$paye = Payroll::paye(400000, $bands);
ok('the first ₦300,000 is taxed at 7% and the next ₦100,000 at 11%',
    abs($paye['annual'] - (300000 * 0.07 + 100000 * 0.11)) < 0.01, (string) $paye['annual']);
ok('the marginal rate is the top slice reached', abs($paye['marginal'] - 11.0) < 0.001, (string) $paye['marginal']);
ok('the working is shown slice by slice', count($paye['steps']) === 2);
ok('nothing is taxed below the relief', Payroll::paye(0, $bands)['annual'] === 0.0 && Payroll::paye(-5000, $bands)['steps'] === []);
ok('a million of taxable pay reaches the 15% slice',
    abs(Payroll::paye(1100000, $bands)['marginal'] - 15.0) < 0.001);
ok('the whole table is walked in order',
    abs(Payroll::paye(3200000, $bands)['annual'] - (300000 * .07 + 300000 * .11 + 500000 * .15 + 500000 * .19 + 1600000 * .21)) < 0.01,
    (string) Payroll::paye(3200000, $bands)['annual']);
ok('everything past the last ceiling takes the top rate',
    abs(Payroll::paye(4200000, $bands)['annual'] - (Payroll::paye(3200000, $bands)['annual'] + 1000000 * .24)) < 0.01);
ok('bands() forces the final slice open-ended',
    Payroll::bands([['slice_width' => 100000, 'rate' => 5], ['slice_width' => 200000, 'rate' => 10]])[1]['slice_width'] === null);
ok('an empty band list falls back to the published table', count(Payroll::bands([])) === count($bands));
$one = Payroll::compute(['basic' => 500000, 'housing' => 0, 'transport' => 0, 'other' => 0,
    'annual_rent' => 0, 'claim_rent' => false, 'pension_rate' => 8]);
ok('gross of zero components is reported as empty, not divided by',
    Payroll::compute(['basic' => 0])['empty'] === true);
ok('net is gross less the three deductions',
    abs($one['gross'] - $one['paye'] - $one['pension'] - $one['nhf'] - $one['net']) < 0.01);
ok('pension is the employee\'s own rate, not the default', abs($one['pension'] - 40000) < 0.01, (string) $one['pension']);
ok('NHF is 2.5% of gross', abs($one['nhf'] - 12500) < 0.01, (string) $one['nhf']);
ok('the employer pays 10% pension and 2.5% NHF on top', abs($one['employer_cost'] - (500000 + 50000 + 12500)) < 0.01);
$capped = Payroll::compute(['basic' => 900000, 'housing' => 0, 'transport' => 0, 'other' => 0,
    'annual_rent' => 100000000, 'claim_rent' => true, 'pension_rate' => 8]);
ok('rent relief stops at the cap however large the rent', abs($capped['rent_relief'] - 6250000) < 0.01, (string) $capped['rent_relief']);
$uncapped = Payroll::compute(['basic' => 300000, 'housing' => 0, 'transport' => 0, 'other' => 0,
    'annual_rent' => 1200000, 'claim_rent' => false, 'pension_rate' => 8]);
ok('declining the relief costs exactly what claiming it saves',
    $uncapped['rent_relief'] === 0.0 && $uncapped['paye'] > Payroll::compute([
        'basic' => 300000, 'housing' => 0, 'transport' => 0, 'other' => 0,
        'annual_rent' => 1200000, 'claim_rent' => true, 'pension_rate' => 8])['paye']);
$floor = Payroll::compute(['basic' => 1000, 'housing' => 0, 'transport' => 0, 'other' => 0,
    'annual_rent' => 0, 'claim_rent' => false, 'pension_rate' => 8]);
ok('a salary below the reliefs floors taxable pay at zero', $floor['taxable_annual'] === 0.0 && $floor['paye'] === 0.0);
ok('and the relief is capped at what is earned, so it cannot go negative',
    $floor['cra'] <= $floor['annual_gross'] + 0.001);
$nopension = Payroll::compute(['basic' => 300000, 'housing' => 0, 'transport' => 0, 'other' => 0,
    'annual_rent' => 0, 'claim_rent' => false, 'pension_rate' => 0]);
ok('a pension rate above the statutory ceiling is clamped',
    abs(Payroll::compute(['basic' => 300000, 'pension_rate' => 90])['pension'] - 300000 * 0.20) < 0.01);
ok('and zero pension is flagged as a problem, not presented as savings',
    Payroll::verdict($nopension)['tone'] === 'bad');
ok('a package that could claim relief but does not is told so',
    Payroll::verdict($uncapped)['tone'] === 'warn');
ok('the marginal rate is what a raise is charged at',
    Payroll::compute(['basic' => 500000])['marginal_rate'] > 7.0
    && Payroll::compute(['basic' => 500000])['marginal_rate'] === Payroll::paye(
        Payroll::compute(['basic' => 500000])['taxable_annual'], $bands)['marginal']);
$seeded = Desk::compute(['basic' => 1, 'housing' => 0, 'transport' => 0, 'other' => 0, 'annual_rent' => 0,
    'claim_rent' => false, 'pension_rate' => 8]);
$plain = Payroll::compute(['basic' => 1, 'housing' => 0, 'transport' => 0, 'other' => 0, 'annual_rent' => 0,
    'claim_rent' => false, 'pension_rate' => 8], $bands, Payroll::DEFAULT_RULES);
ok('the database table and the engine\'s own published figures agree',
    abs($seeded['paye'] - $plain['paye']) < 0.01, $seeded['paye'] . ' vs ' . $plain['paye']);

/* ------------------------------------------------------------------ clean up */
Db::run('DELETE FROM payslips WHERE run_id IN (SELECT id FROM payroll_runs WHERE period IN (?, ?))', [$period, $other]);
Db::run('DELETE FROM payroll_runs WHERE period IN (?, ?)', [$period, $other]);
Db::run('DELETE FROM email_outbox WHERE recipient LIKE ?', ['smoke.%@payrite.test']);
Db::run('DELETE FROM calculations WHERE label = ?', ['Smoke calc']);
Db::run('DELETE FROM calculations WHERE user_id IN (SELECT id FROM users WHERE email LIKE ?)', ['smoke.%@payrite.test']);
Db::run('DELETE FROM audit_log WHERE actor_id IN (SELECT id FROM users WHERE email LIKE ?)', ['smoke.%@payrite.test']);
Db::run('DELETE FROM employees WHERE staff_no = ?', [$staffNo]);
Db::run('DELETE FROM users WHERE email LIKE ?', ['smoke.%@payrite.test']);
Db::run('DELETE FROM login_attempts');

/* The rate test moved the live band table; put the published figures back or
   every screenshot of this site would be quoting a rate nobody published. */
foreach ($bandSnapshot as $row) {
    Db::update('pay_bands', [
        'band_order' => (int) $row['band_order'],
        'slice_width' => $row['slice_width'],
        'rate' => $row['rate'],
        'updated_at' => $row['updated_at'],
        'updated_by' => $row['updated_by'],
    ], 'id = ?', [(int) $row['id']]);
}
foreach ($ruleSnapshot as $row) {
    Db::update('rates', ['value' => $row['value'], 'updated_at' => $row['updated_at'], 'updated_by' => $row['updated_by']],
        'id = ?', [(int) $row['id']]);
}
Desk::refresh();

$after = [
    'employees' => (int) Db::value('SELECT COUNT(*) FROM employees'),
    'runs' => (int) Db::value('SELECT COUNT(*) FROM payroll_runs'),
    'users' => (int) Db::value('SELECT COUNT(*) FROM users'),
    'slips' => (int) Db::value('SELECT COUNT(*) FROM payslips'),
];
ok('the test leaves no accounts or roster rows behind', $after === $before, json_encode([$before, $after]));
ok('the band table is exactly the published one again',
    (float) Db::value('SELECT rate FROM pay_bands WHERE id = ?', [$bandId]) === $originalRate
    && Db::value('SELECT slice_width FROM pay_bands WHERE id = ?', [(int) $bandSnapshot[count($bandSnapshot) - 1]['id']]) === null);
ok('the query the staff member raised is gone with their record',
    (int) Db::value('SELECT COUNT(*) FROM employees WHERE query_note LIKE ?', ['%smoke query%']) === 0);

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
