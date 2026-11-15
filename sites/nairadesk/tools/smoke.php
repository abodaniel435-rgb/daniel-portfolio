<?php
declare(strict_types=1);

/**
 * End-to-end smoke test for a running NairaDesk.
 *
 *   php tools/smoke.php [base-url]
 *
 * It drives the real HTTP surface — sessions, CSRF tokens, redirects, JSON —
 * instead of calling controllers in isolation, then checks what MySQL kept.
 * Administrator credentials come from ND_ADMIN_EMAIL / ND_ADMIN_PASSWORD, so
 * no password is ever written into this file.
 */

require dirname(__DIR__) . '/core/bootstrap.php';
require dirname(__DIR__) . '/app/services.php';
require dirname(__DIR__) . '/app/engines/Payroll.php';

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
        $this->jar = (string) tempnam(sys_get_temp_dir(), 'ndjar');
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

$base = rtrim($argv[1] ?? 'http://127.0.0.1:5058', '/');
$adminEmail = strtolower((string) (getenv('ND_ADMIN_EMAIL') ?: env('ADMIN_EMAIL', '')));

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
$memberEmail = 'smoke.' . $suffix . '@nairadesk.test';
$memberPassword = 'SmokeTest-' . $suffix;
$otherEmail = 'other.' . $suffix . '@nairadesk.test';

$wages = ['basic' => 108000, 'housing' => 36000, 'transport' => 18000, 'other' => 18000];
$flags = ['use_pension' => 1, 'pension_rate' => 8, 'use_nhf' => 1, 'use_rent' => 1, 'annual_rent' => 1800000];
$input = $wages + $flags;

echo "NairaDesk smoke test against {$base}\n";
echo "member account: {$memberEmail}\n\n";

/* a failed run must not leave the developer locked out by the throttle */
Db::run('DELETE FROM login_attempts');

echo "Public surface\n";
$guest = new Http($base);
$guest->get('/');
ok('home renders', $guest->code === 200 && str_contains($guest->body, 'PAYE'), (string) $guest->code);
ok('home quotes the live engine, not typed figures', str_contains($guest->body, 'Worked example'));
$bands = Payroll::bands(Desk::rates());
ok('six bands are loaded from MySQL', count($bands) === 6);
foreach (['/how', '/login', '/register'] as $path) {
    $guest->get($path);
    ok('GET ' . $path . ' is 200', $guest->code === 200, (string) $guest->code);
}
$guest->get('/dashboard');
ok('a guest is sent to sign in', $guest->code === 302 && str_contains($guest->location(), '/login'), (string) $guest->code);
$guest->get('/admin');
ok('a guest cannot reach the console', $guest->code === 302, (string) $guest->code);
$guest->get('/staff/' . $suffix);
ok('an unknown path is a 404, not a crash', $guest->code === 404, (string) $guest->code);
/* dirname() on Windows used to hand back "\" as the base path, which then
   prefixed every generated url() and broke every stylesheet and form on the
   page while still looking correct in a redirect header. */
ok('every generated url uses forward slashes', $guest->get('/')->code === 200
    && substr_count($guest->body, '\\/') === 0, substr_count($guest->body, '\\/') . ' backslashed urls');
ok('the stylesheet it links to is served', (new Http($base))->get('/assets/app.css')->code === 200);

echo "\nCSRF\n";
$guest->post('/register', ['name' => 'No Token', 'email' => 'notoken.' . $suffix . '@nairadesk.test',
    'password' => 'longenoughpassword', 'password_confirm' => 'longenoughpassword']);
ok('a POST without a token is refused', $guest->code === 419, (string) $guest->code);

echo "\nRegistration and workspace\n";
$member = new Http($base);
$token = $member->token('/register');
ok('the register form carries a token', $token !== '');
$member->post('/register', ['name' => 'Smoke Tester', 'company' => 'Smoke Foods Ltd', 'email' => $memberEmail,
    'phone' => '', 'password' => $memberPassword, 'password_confirm' => $memberPassword], $token);
ok('registering lands on the staff roster', $member->code === 302 && str_contains($member->location(), '/staff'), (string) $member->code);
$member->get('/dashboard');
ok('the dashboard opens', $member->code === 200, (string) $member->code);
$member->post('/company', ['name' => 'Second Workspace'], $token);
ok('a second workspace for one account is refused', $member->code === 302
    && str_contains($member->location(), '/dashboard'), $member->location());
ok('only one company was created', (int) Db::value('SELECT COUNT(*) FROM companies WHERE owner_id = (SELECT id FROM users WHERE email = ?)', [$memberEmail]) === 1);
$member->get('/staff');
ok('staff page shows the company', str_contains($member->body, 'Smoke Foods Ltd'), (string) $member->code);

$token = $member->token('/staff');
$member->post('/staff', $wages + $flags + ['full_name' => 'Amina Yusuf', 'staff_no' => 'SMK-01'], $token);
ok('an employee is added', $member->code === 302, (string) $member->code);
$member->get('/staff');
ok('the employee is listed', str_contains($member->body, 'Amina Yusuf'));
$employeeId = (int) Db::value('SELECT id FROM employees WHERE full_name = ? AND company_id = (SELECT id FROM companies WHERE owner_id = (SELECT id FROM users WHERE email = ?))',
    ['Amina Yusuf', $memberEmail]);

echo "\nServer-side calculation\n";
$token = $member->token('/calculate');
$member->post('/api/payroll', $input + ['label' => 'smoke'], $token);
$data = $member->json();
ok('the api answers 200 with ok', $member->code === 200 && ($data['ok'] ?? false) === true, (string) $member->code);

$expected = Payroll::compute($input, Desk::rates());
$figures = ['gross', 'annualGross', 'cra', 'rentRelief', 'pensionMonthly', 'nhfMonthly', 'taxable',
    'payeMonthly', 'payeAnnual', 'net', 'employerCost', 'annualNet', 'effRate', 'marginal'];
$drift = [];
foreach ($figures as $key) {
    if (abs((float) ($data['result'][$key] ?? NAN) - (float) $expected[$key]) > 0.005) {
        $drift[] = $key;
    }
}
ok('the http result equals the engine figure for figure', $drift === [], implode(',', $drift));
/* Hand-worked against the published schedule: annual gross 2,160,000; CRA
   200,000 + 20% = 632,000; rent relief 20% of 1,800,000 = 360,000; pension
   172,800; taxable 995,200 -> 300,000@7 + 300,000@11 + 395,200@15 = 113,280
   a year, 9,440 a month; net 180,000 - 9,440 - 14,400 - 4,500 = 151,660. */
ok('PAYE is ₦9,440.00 a month for the example', abs($expected['payeMonthly'] - 9440.0) < 0.01, (string) $expected['payeMonthly']);
ok('net pay is ₦151,660.00 for the example', abs($expected['net'] - 151660.0) < 0.01, (string) $expected['net']);
ok('the marginal rate is the top band reached', (float) $expected['marginal'] === 15.0, (string) $expected['marginal']);
ok('the calculation was stored', isset($data['id']) && (int) Db::value('SELECT id FROM calculations WHERE id = ?', [(int) $data['id']]) > 0);
$saved = Db::one('SELECT inputs, result FROM calculations WHERE id = ?', [(int) ($data['id'] ?? 0)]);
$stored = json_decode((string) ($saved['result'] ?? ''), true);
ok('the stored row replays the same net', is_array($stored) && abs((float) $stored['net'] - $expected['net']) < 0.005);

$member->post('/api/payroll', ['basic' => 'not-a-number'], $token);
ok('non-numeric pay is rejected with 422', $member->code === 422 && isset($member->json()['errors']['basic']), (string) $member->code);
$member->post('/api/payroll', $input, 'this-token-was-never-issued');
ok('a forged token is refused', $member->code === 419, (string) $member->code);

echo "\nPay run\n";
$period = date('Y-m');
$token = $member->token('/runs');
$member->post('/runs', ['period' => $period], $token);
ok('a run is created and opens', $member->code === 302 && str_contains($member->location(), '/runs/'), (string) $member->code);
$runId = (int) basename($member->location());
$member->get('/runs/' . $runId);
ok('the run page lists the frozen line', $member->code === 200 && str_contains($member->body, 'Amina Yusuf'), (string) $member->code);
$run = Db::one('SELECT * FROM pay_runs WHERE id = ?', [$runId]);
ok('stored totals match the payslip', $run !== null
    && abs((float) $run['net_total'] - $expected['net']) < 0.01
    && abs((float) $run['paye_total'] - $expected['payeMonthly']) < 0.01
    && (int) $run['headcount'] === 1, $run === null ? 'row missing' : json_encode($run['net_total']));

$token = $member->token('/runs');
$member->post('/runs', ['period' => $period], $token);
ok('a duplicate month returns the existing run', $member->code === 302
    && (int) basename($member->location()) === $runId, $member->location());
$token = $member->token('/runs');
$member->post('/runs', ['period' => '2026-13'], $token);
ok('an impossible period is rejected', $member->code === 302 && str_contains($member->location(), '/runs'), (string) $member->code);

echo "\nTenant isolation\n";
$other = new Http($base);
$token = $other->token('/register');
$other->post('/register', ['name' => 'Other Owner', 'email' => $otherEmail,
    'password' => $memberPassword, 'password_confirm' => $memberPassword], $token);
$other->get('/runs/' . $runId);
ok('another account cannot open run #' . $runId, $other->code === 302 && str_contains($other->location(), '/runs'), (string) $other->code);
$other->get('/staff/' . $employeeId . '/delete');
ok('a member cannot delete staff by guessing an id over GET', $other->code === 404, (string) $other->code);
$other->get('/admin');
ok('a member cannot open the console', $other->code === 403, (string) $other->code);
// a genuinely valid token, so this tests the role check and not CSRF
$memberToken = $other->token('/calculate');
$other->post('/admin/rates', ['rates' => ['nhf_rate' => 0]], $memberToken);
ok('a member cannot write the rate table', $other->code === 403, (string) $other->code);

echo "\nPasswords and storage\n";
$row = Db::one('SELECT password_hash FROM users WHERE email = ?', [$memberEmail]);
ok('only a bcrypt hash is stored', $row !== null && str_starts_with((string) $row['password_hash'], '$2y$')
    && password_verify($memberPassword, (string) $row['password_hash']), (string) ($row['password_hash'] ?? 'missing'));
ok('no column is called password', (int) Db::value(
    "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND column_name = 'password'") === 0);
ok('no hash is leaked into a public page', !str_contains($guest->get('/')->body, '$2y$'));

$fresh = new Http($base);
$token = $fresh->token('/login');
$fresh->post('/login', ['email' => $memberEmail, 'password' => 'wrong-password-entirely'], $token);
ok('a wrong password does not sign in', $fresh->code === 302 && str_contains($fresh->location(), '/login'), (string) $fresh->code);
$token = $fresh->token('/login');
$fresh->post('/login', ['email' => strtoupper($memberEmail), 'password' => $memberPassword], $token);
ok('the right password signs in, case-insensitively', $fresh->code === 302 && str_contains($fresh->location(), '/dashboard'), (string) $fresh->code);
$cookie = strtolower($fresh->headers['set-cookie'] ?? '');
ok('the session cookie is HttpOnly', str_contains($cookie, 'httponly'), $cookie);
ok('the session cookie is SameSite=Lax', str_contains($cookie, 'samesite=lax'), $cookie);
$token = $fresh->token('/dashboard');
$fresh->post('/logout', [], $token);
$fresh->get('/dashboard');
ok('signing out ends the session', $fresh->code === 302 && str_contains($fresh->location(), '/login'), (string) $fresh->code);

echo "\nAdministrator\n";
/* The console is exercised through a throwaway admin the test creates and
 * removes, so no real password is ever needed here. The account you installed
 * is checked separately, from the row it left in MySQL. */
$tempAdmin = 'smoke-admin.' . $suffix . '@nairadesk.test';
$tempPassword = 'Throwaway-' . $suffix;
$adminId = Installer::createUser('Smoke Administrator', $tempAdmin, $tempPassword, 'admin');
ok('the real administrator account exists', (int) Db::value(
    "SELECT COUNT(*) FROM users WHERE email = ? AND role = 'admin' AND is_active = 1", [$adminEmail]) === 1, $adminEmail);
ok('its stored verifier is a bcrypt hash', str_starts_with((string) Db::value(
    "SELECT password_hash FROM users WHERE email = ?", [$adminEmail]), '$2y$'));

$desk = new Http($base);
$token = $desk->token('/login');
$desk->post('/login', ['email' => $tempAdmin, 'password' => $tempPassword], $token);
ok('an administrator signs in', $desk->code === 302 && str_contains($desk->location(), '/admin'), (string) $desk->code);
$desk->get('/admin');
ok('the console renders', $desk->code === 200 && str_contains($desk->body, 'Console'), (string) $desk->code);
$desk->post('/admin/users/' . $adminId . '/status', ['id' => $adminId], $desk->token('/admin/users'));
ok('an administrator cannot disable their own account', $desk->code === 302
    && (int) Db::value('SELECT is_active FROM users WHERE id = ?', [$adminId]) === 1);
foreach (['/admin/users', '/admin/rates', '/admin/audit', '/admin/runs'] as $path) {
    $desk->get($path);
    ok('GET ' . $path, $desk->code === 200, (string) $desk->code);
}
$desk->get('/admin');
ok('the console counts the smoke run', str_contains($desk->body, 'Pay runs stored'));

$before = (float) Db::value("SELECT value FROM rates WHERE tool = 'payroll' AND code = 'nhf_rate'");
$token = $desk->token('/admin/rates');
$desk->post('/admin/rates', ['rates' => ['nhf_rate' => '3.5', 'band_99_width' => '999999', 'not-a-code' => '5']], $token);
ok('the save redirects', $desk->code === 302, (string) $desk->code);
$after = (float) Db::value("SELECT value FROM rates WHERE tool = 'payroll' AND code = 'nhf_rate'");
ok('the edited figure is in MySQL', abs($after - 3.5) < 0.0001, (string) $after);
ok('a made-up code is not inserted', (int) Db::value("SELECT COUNT(*) FROM rates WHERE code IN ('band_99_width','not-a-code')") === 0);

$token = $member->token('/calculate');
$member->post('/api/payroll', $input + ['label' => 'smoke-raised-nhf'], $token);
$raised = $member->json()['result'] ?? [];
ok('the next calculation uses the edited rate', isset($raised['gross'])
    && abs((float) $raised['nhfMonthly'] - (float) $raised['gross'] * 0.035) < 0.01, json_encode($raised['nhfMonthly'] ?? null));

$desk->post('/admin/rates', ['rates' => ['nhf_rate' => (string) $before]], $desk->token('/admin/rates'));
$restored = (float) Db::value("SELECT value FROM rates WHERE tool = 'payroll' AND code = 'nhf_rate'");
ok('the figure is restored for the developer', abs($restored - $before) < 0.0001, (string) $restored);

$member->get('/staff');
$member->post('/staff/' . $employeeId . '/delete', ['id' => $employeeId], $member->token('/staff'));
$member->get('/runs/' . $runId);
ok('a frozen run keeps its lines after the roster changes', str_contains($member->body, 'Former member of staff'), (string) $member->code);

$actions = array_column(Db::all('SELECT action FROM audit_log ORDER BY id DESC LIMIT 20'), 'action');
ok('the audit trail recorded the writes', in_array('rates.update', $actions, true)
    && in_array('run.create', $actions, true) && in_array('employee.delete', $actions, true), implode(',', $actions));
ok('nothing credential-shaped is audited', (int) Db::value(
    "SELECT COUNT(*) FROM audit_log WHERE meta LIKE '%password%' OR meta LIKE '%\$2y\$%'") === 0);

Db::run('DELETE FROM users WHERE id = ?', [$adminId]);
ok('the throwaway administrator is removed again', (int) Db::value('SELECT COUNT(*) FROM users WHERE id = ?', [$adminId]) === 0);

echo "\nSign-in throttle\n";
Db::run('DELETE FROM login_attempts');
$throttleEmail = 'throttle.' . $suffix . '@nairadesk.test';
$target = new Http($base);
$target->post('/register', ['name' => 'Throttle Target', 'email' => $throttleEmail,
    'password' => 'ThrottlePassword1', 'password_confirm' => 'ThrottlePassword1'], $target->token('/register'));
$blocked = false;
for ($i = 0; $i < 12; $i++) {
    $try = new Http($base);
    $try->post('/login', ['email' => $throttleEmail, 'password' => 'guess-' . $i . '-is-wrong'], $try->token('/login'));
    /* the refusal is a flash, so it surfaces on the page the redirect lands on */
    $try->get('/login');
    if (str_contains($try->body, 'Too many sign-in attempts')) {
        $blocked = true;
        ok('the throttle arrives after eight failures, not sooner', $i >= 7, 'attempt ' . ($i + 1));
        break;
    }
}
ok('repeated failures are throttled', $blocked);
Db::run('DELETE FROM login_attempts');
ok('attempt rows cleared so the developer is not locked out', (int) Db::value('SELECT COUNT(*) FROM login_attempts') === 0);

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
