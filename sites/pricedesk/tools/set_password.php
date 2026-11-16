<?php
declare(strict_types=1);

/**
 * Set or reset one account's password from the command line.
 *
 *   php tools/set_password.php someone@example.com
 *
 * The password is read from STDIN, so it never appears in a command line
 * (shell history, process lists, CI logs, or a chat window) and is never
 * written to a file. It is hashed with bcrypt and only the hash reaches MySQL.
 *
 * Git Bash will echo what you type. To keep it off the screen entirely:
 *
 *   read -rs PW && printf '%s\n' "$PW" | php tools/set_password.php you@example.com
 */

require dirname(__DIR__) . '/core/bootstrap.php';
// Board::audit() lives in the application, not the framework, so a CLI script
// has to load it the same way public/index.php does.
require dirname(__DIR__) . '/app/services.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!Installer::installed()) {
    fwrite(STDERR, "The site is not installed yet. Run core/cli/install.php first.\n");
    exit(1);
}

$email = strtolower(trim((string)($argv[1] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php tools/set_password.php <email>\n");
    exit(1);
}

$user = Db::one('SELECT id, name, role FROM users WHERE email = ? LIMIT 1', [$email]);
if ($user === null) {
    fwrite(STDERR, "No account with that email. Names on the board:\n");
    foreach (Db::all('SELECT email FROM users ORDER BY id LIMIT 40') as $row) {
        fwrite(STDERR, '  ' . $row['email'] . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "Account: {$user['name']} <{$email}> ({$user['role']})\n");
fwrite(STDOUT, "New password (at least 10 characters), then Enter:\n");
$password = trim((string) fgets(STDIN));
if (strlen($password) < 10) {
    fwrite(STDERR, "Refused: that is fewer than 10 characters.\n");
    exit(1);
}
fwrite(STDOUT, "Type it once more to be sure:\n");
if (trim((string) fgets(STDIN)) !== $password) {
    fwrite(STDERR, "Refused: the two entries did not match.\n");
    exit(1);
}

Db::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [(int)$user['id']]);
// A forgotten password usually arrives after a run of failed guesses; leaving
// the throttle counts in place would lock the owner out of the account they
// just recovered.
Db::run('DELETE FROM login_attempts WHERE email = ?', [$email]);
Board::audit('account.password_reset', 'User #' . $user['id'], ['method' => 'cli']);

// Never print the password, a fragment of it, or its hash.
fwrite(STDOUT, "Password updated for {$email}. Sign in from /login.\n");
exit(0);
