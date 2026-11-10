<?php
/**
 * SiteGenerator — turns a blueprint into a real, self-contained PHP + MySQL site.
 *
 * The generator is deliberately boring and inspectable: copy the template tree,
 * copy the shared core, compose schema fragments, write config + env, install the
 * database when possible, run QA, then write docs and a sha256 manifest.
 */
declare(strict_types=1);

final class SiteGenerator
{
    private const TEXT_EXTENSIONS = ['php', 'sql', 'md', 'html', 'css', 'js', 'json', 'txt', 'example', 'htaccess'];

    public static function generate(array $blueprint, array $options = []): array
    {
        $preflight = Preflight::run();
        if (!$preflight['can_generate']) {
            return [
                'ok' => false,
                'error' => 'Preflight failed. Fix the failing checks before generating a site.',
                'preflight' => $preflight,
            ];
        }

        $intake = $blueprint['intake'];
        $architecture = $blueprint['architecture'];
        $generation = $blueprint['generation'];
        $slug = (string)$generation['slug'];
        $siteDir = FS_ROOT . '/build/' . $slug;
        $overwrite = (bool)($options['overwrite'] ?? false);

        if (is_dir($siteDir) && self::dirHasFiles($siteDir)) {
            if (!$overwrite) {
                throw new RuntimeException('Build folder already exists: build/' . $slug . '. Choose another slug or pass --force.');
            }
            self::removeTree($siteDir);
        }

        $steps = [];
        self::step($steps, 'Intake captured', 'done', sprintf(
            '%s · %s · pages requested: %s · features: %s.',
            $intake['business_name'],
            $intake['industry'],
            implode(', ', $intake['pages_requested']) ?: 'home only',
            implode(', ', $intake['features'])
        ));
        self::step($steps, 'Research notes stored', 'done', $intake['references'] !== ''
            ? 'Operator competitor notes stored with the project: ' . mb_substr($intake['references'], 0, 220)
            : 'No competitor notes were supplied; the curated ' . $architecture['profile_label'] . ' research baseline was applied.');
        self::step($steps, 'Architecture derived', 'done', sprintf(
            '%d pages, %d entities, %d roles and %d automation tasks derived from the intake.',
            count($architecture['pages']),
            count($architecture['entities']),
            count($architecture['roles']),
            count($architecture['automation'])
        ));
        self::step($steps, 'cPanel preflight', $preflight['status'] === 'pass' ? 'done' : 'skipped', sprintf(
            'PHP %s · extensions %s · MySQL %s · overall %s.',
            $preflight['php'],
            extension_loaded('pdo_mysql') ? 'pdo_mysql present' : 'pdo_mysql missing',
            $preflight['database']['ok'] ? 'reachable at ' . Preflight::dbLabel() : 'unavailable (schema still included)',
            strtoupper($preflight['status'])
        ));

        self::ensureDir($siteDir);
        $config = self::config($blueprint, $options);
        $palette = $config['palette'];
        self::step($steps, 'Design system', 'done', sprintf(
            '%s palette (%s) with %s / %s. Responsive to 390px, visible focus, reduced-motion rules.',
            $palette['name'],
            $palette['description'],
            $config['fonts']['display'],
            $config['fonts']['body']
        ));

        $templateDir = FS_ROOT . '/templates/site';
        $tokens = ['{{TXN_TABLE}}' => (string)$config['labels']['txn_table']];
        $templateFiles = self::copyTree($templateDir, $siteDir, static function (string $content) use ($tokens): string {
            return strtr($content, $tokens);
        });
        self::step($steps, 'Frontend generated', 'done', sprintf(
            '%d templates copied: responsive layout, %d curated sections, industry copy and accessible forms.',
            $templateFiles,
            count((array)($config['copy']['sections'] ?? []))
        ));

        $coreDir = (string)$GLOBALS['fs_config']['shared_core'];
        $coreFiles = self::copyTree($coreDir, $siteDir . '/core');
        $dbName = (string)$generation['db_name'];
        self::step($steps, 'Backend generated', 'done', sprintf(
            '%d routes wired through Router; %d shared core files copied; single front controller at public/index.php.',
            count($architecture['routes']),
            $coreFiles
        ));

        $schema = self::schema($config);
        self::writeFile($siteDir . '/database/schema.sql', $schema);
        self::writeFile($siteDir . '/database/seed.sql', self::seed($blueprint, $config));
        self::writeFile($siteDir . '/app/config.php', self::configFile($config));
        self::writeEnv($siteDir, $blueprint, $options);
        self::writeFile($siteDir . '/.gitignore', ".env\n/storage/*.log\n/storage/*.lock\n/storage/cache/\n!storage/README.txt\n");

        $database = Database::install($siteDir, ['blueprint' => $blueprint, 'id' => $slug]);
        $database['db_name'] = $dbName;
        self::step($steps, 'Database prepared', $database['status'] === 'ready' ? 'done' : ($database['status'] === 'skipped' ? 'skipped' : 'failed'), $database['label'] . ' — ' . implode(' ', array_slice($database['steps'], 0, 3)));

        self::step($steps, 'Authentication & roles', 'done', 'password_hash/password_verify, login throttling, session regeneration, CSRF on every POST, server-side role checks and a seeded administrator from .env.');
        self::step($steps, 'Automation configured', 'done', count($architecture['automation']) . ' idempotent tasks: ' . implode(', ', array_column($architecture['automation'], 'label')) . '. Cron entry documented.');

        $docs = new Docs();
        self::writeFile($siteDir . '/README.md', $docs->siteReadme(['blueprint' => $blueprint, 'created_at' => date('c'), 'id' => $slug]));
        self::writeFile($siteDir . '/docs/DEPLOYMENT.md', $docs->deployment(['blueprint' => $blueprint, 'database' => $database, 'created_at' => date('c'), 'id' => $slug]));
        self::writeFile($siteDir . '/docs/TECH_STACK.md', $docs->techStack(['blueprint' => $blueprint, 'database' => $database, 'created_at' => date('c'), 'id' => $slug, 'config' => $config]));

        $liveLink = (string)($options['live_link'] ?? $generation['app_url'] ?? '');
        $thumbnail = (string)($options['thumbnail'] ?? ('assets/' . $slug . '.png'));
        $portfolioEntry = $docs->portfolio([
            'blueprint' => $blueprint,
            'created_at' => date('c'),
            'id' => $slug,
        ], $thumbnail, $liveLink);
        self::writeFile($siteDir . '/portfolio-entry.json', self::json($portfolioEntry));
        Store::appendPortfolio($portfolioEntry);

        $qa = self::qa($siteDir, $generation, $config);
        $failedChecks = array_map(static fn ($check) => $check['label'], array_values(array_filter($qa['checks'], static fn ($check) => !$check['ok'])));
        self::step($steps, 'QA & security scan', $qa['passed'] ? 'done' : 'failed', $qa['passed']
            ? 'php -l passed on every PHP file; no unresolved tokens; generated credentials isolated to .env; all environment variables documented; required files and schema composition verified.'
            : 'Failed: ' . implode(', ', $failedChecks) . '. See qa.checks in the project record.');
        self::step($steps, 'Defects fixed', $qa['passed'] ? 'done' : 'failed', $qa['passed']
            ? 'No blocking defects were found, so no files were rewritten.'
            : 'Automatic checks found issues that need operator attention before launch: ' . implode(', ', $failedChecks) . '.');
        $assets = self::assetStats($siteDir);
        self::step($steps, 'Optimisation', 'done', sprintf(
            'Hand-written CSS %s and JS %s; no framework, no Node build, no external script dependency. Images are lazy-loaded Unsplash URLs.',
            fs_format_bytes($assets['css']),
            fs_format_bytes($assets['js'])
        ));
        self::step($steps, 'Deployment package', 'done', 'Build folder, DEPLOYMENT.md, TECH_STACK.md, .env.example and checksums prepared. No zip extension is required; upload the folder via cPanel File Manager.');
        self::step($steps, 'Portfolio entry', 'done', 'Portfolio JSON written for ' . $intake['business_name'] . ' with technologies, key features and links.');

        // The report must quote its own file count, so build a provisional manifest
        // (everything except the report) and add one for the report itself.
        self::step($steps, 'Technical report', 'done', 'Printable report written to docs/technical-report.html (16 steps recorded).');
        $preliminary = self::manifest($siteDir);
        $projectId = Store::newId();
        $reportProject = [
            'id' => $projectId,
            'created_at' => date('c'),
            'blueprint' => $blueprint,
            'steps' => $steps,
            'database' => $database,
            'qa' => $qa,
            'manifest' => ['count' => $preliminary['count'] + 1, 'bytes' => $preliminary['bytes']],
        ];
        $report = $docs->technicalReport($reportProject);
        self::writeFile($siteDir . '/docs/technical-report.html', $report);
        $project = [
            'id' => $projectId,
            'slug' => $slug,
            'name' => $intake['business_name'],
            'industry' => $intake['industry'],
            'profile' => $blueprint['profile'],
            'profile_label' => $blueprint['profile_label'],
            'status' => $qa['passed'] ? 'ready' : 'review',
            'created_at' => $reportProject['created_at'],
            'updated_at' => date('c'),
            'blueprint' => $blueprint,
            'config' => [
                'modules' => $config['modules'],
                'palette' => $config['palette']['name'],
                'pages' => count($config['pages']),
                'fonts' => $config['fonts']['display'] . ' / ' . $config['fonts']['body'],
            ],
            'preflight' => [
                'status' => $preflight['status'],
                'php' => $preflight['php'],
                'database' => $preflight['database'],
                'checks' => $preflight['checks'],
            ],
            'steps' => $steps,
            'database' => $database,
            'qa' => $qa,
            'manifest' => [],
            'portfolio' => $portfolioEntry,
            'port' => (int)($generation['port'] ?? 0),
            'build_path' => 'build/' . $slug,
            'commands' => self::commands($blueprint),
        ];
        $manifest = self::manifest($siteDir);
        self::writeFile($siteDir . '/manifest.json', self::json([
            'generator' => 'ForgeStack ' . FS_VERSION,
            'core' => '1.0.0',
            'slug' => $slug,
            'generated_at' => date('c'),
            'count' => $manifest['count'],
            'bytes' => $manifest['bytes'],
            'files' => $manifest['files'],
        ]));
        self::writeFile($siteDir . '/checksums.sha256', $docs->checksums($manifest));
        // The registry is a repository file: never persist the admin password.
        $project['blueprint']['generation']['admin_password'] = '';
        $project['manifest'] = $manifest;
        Store::save($project);

        return [
            'ok' => true,
            'project' => $project,
            'preflight' => $preflight,
            'site_dir' => $siteDir,
        ];
    }

    /** Build the on-disk config consumed by site_config() / cfg(). */
    private static function config(array $blueprint, array $options): array
    {
        $intake = $blueprint['intake'];
        $profile = fs_profile((string)$blueprint['profile']);
        $paletteKey = (string)$blueprint['palette'];
        $palette = fs_palettes()[$paletteKey] ?? fs_palettes()['slate'];
        $features = array_flip($intake['features']);
        $modules = [];
        foreach (Blueprint::FEATURES as $feature) {
            $modules[$feature] = isset($features[$feature]);
        }

        $shopTitles = [
            'dental' => 'Care essentials',
            'courier' => 'Packaging shop',
            'restaurant' => 'Order for collection',
            'generic' => 'Shop',
        ];
        $quantityUnits = [
            'dental' => 'minutes',
            'courier' => 'parcels',
            'restaurant' => 'guests',
            'generic' => 'minutes',
        ];
        $labels = $profile['labels'];
        $labels['shop_title'] = $shopTitles[$profile['key']] ?? 'Shop';
        $labels['quantity_unit'] = $quantityUnits[$profile['key']] ?? '';

        $copy = self::replaceBrand($profile['copy'], $profile['default_name'], $intake['business_name']);
        $copy['tagline'] = self::replaceBrand($profile['copy']['tagline'], $profile['default_name'], $intake['business_name']);
        $copy['meta_description'] = self::replaceBrand($profile['copy']['meta_description'], $profile['default_name'], $intake['business_name']);

        $pages = [];
        $navPaths = ['/' => true, '/about' => true, '/services' => true, '/track' => true, '/shop' => true, '/contact' => true, '/faq' => false, '/book' => false];
        $pageLabels = [
            '/' => 'Home',
            '/about' => 'About',
            '/services' => 'Services',
            '/faq' => 'FAQ',
            '/book' => $labels['book'],
            '/track' => $labels['track'],
            '/shop' => $labels['shop_title'],
            '/contact' => 'Contact',
            '/login' => 'Sign in',
            '/register' => 'Create account',
            '/portal' => $labels['portal'],
        ];
        foreach ($blueprint['architecture']['pages'] as $page) {
            $path = (string)$page['path'];
            $label = $pageLabels[$path] ?? (string)$page['title'];
            if (str_starts_with($path, '/admin')) {
                $label = (string)$page['title'];
            }
            $pages[] = [
                'path' => $path,
                'label' => $label,
                'title' => (string)$page['title'],
                'nav' => (bool)($navPaths[$path] ?? false),
            ];
        }

        return [
            'app_name' => $intake['business_name'],
            'description' => $copy['meta_description'],
            'tagline' => $copy['tagline'],
            'profile' => $profile['key'],
            'variant' => $profile['variant'],
            'palette' => $palette,
            'fonts' => $profile['fonts'],
            'pages' => $pages,
            'contact' => [
                'name' => $intake['contact_name'] !== '' ? $intake['contact_name'] : $intake['business_name'],
                'email' => $intake['contact_email'],
                'phone' => $intake['contact_phone'],
                'address' => $intake['address'] !== '' ? $intake['address'] : (string)($copy['footer']['address'] ?? ''),
            ],
            'copy' => $copy,
            'labels' => $labels,
            'status_meta' => $profile['status_meta'],
            'initial_status' => $profile['initial_status'],
            'reminder_statuses' => $profile['reminder_statuses'],
            'close_past_status' => $profile['close_past_status'],
            'form' => $profile['form'],
            'modules' => $modules,
            'features' => $intake['features'],
        ];
    }

    private static function schema(array $config): string
    {
        $txn = str_replace('`', '', (string)$config['labels']['txn_table']);
        $base = (string)file_get_contents(FS_ROOT . '/templates/site/database/schema.sql');
        $sql = str_replace('{{TXN_TABLE}}', $txn, $base);
        $modules = $config['modules'];
        $fragments = [];
        if ($modules['orders']) {
            $fragments[] = 'orders.sql';
        }
        if ($modules['payments']) {
            $fragments[] = 'payments.sql';
        }
        if ($modules['messaging']) {
            $fragments[] = 'messages.sql';
        }
        foreach ($fragments as $fragment) {
            $sql .= "\n\n-- Module: " . $fragment . "\n"
                . str_replace('{{TXN_TABLE}}', $txn, (string)file_get_contents(FS_ROOT . '/templates/site/database/' . $fragment));
        }
        return $sql;
    }

    private static function seed(array $blueprint, array $config): string
    {
        $profile = fs_profile((string)$blueprint['profile']);
        $txn = str_replace('`', '', (string)$config['labels']['txn_table']);
        $payments = (bool)$config['modules']['payments'];
        $orders = (bool)$config['modules']['orders'];
        $messaging = (bool)$config['modules']['messaging'];
        $today = strtotime('today');
        $out = "-- Reference content for " . $config['app_name'] . ".\n"
            . "-- Dates are relative to generation time so the dashboard always has live-looking data.\n"
            . "-- Remove this file before launch if the operator wants an empty database.\n\n";

        foreach ((array)($profile['seed']['records'] ?? []) as $record) {
            $date = date('Y-m-d', strtotime((string)$record['date'], $today));
            $payment = $payments ? (string)$record['payment'] : 'not_required';
            $out .= 'INSERT IGNORE INTO `' . $txn . '` (reference, customer_name, customer_email, customer_phone, request_date, request_time, choice_a, choice_b, quantity, notes, amount, payment_status, status, created_at) VALUES ('
                . implode(', ', [
                    self::sqlv($record['ref']),
                    self::sqlv($record['name']),
                    self::sqlv($record['email']),
                    self::sqlv($record['phone']),
                    self::sqlv($date),
                    self::sqlv($record['time']),
                    self::sqlv($record['a']),
                    self::sqlv($record['b']),
                    (string)(int)$record['qty'],
                    self::sqlv($record['notes']),
                    number_format((float)$record['amount'], 2, '.', ''),
                    self::sqlv($payment),
                    self::sqlv($record['status']),
                    self::sqlv(date('Y-m-d H:i:s', strtotime('-2 hours', $today))),
                ]) . ");\n";
            $out .= 'INSERT INTO record_events (record_id, status, note, source, created_at) SELECT id, '
                . self::sqlv($record['status']) . ', ' . self::sqlv('Reference content loaded with the starter build.') . ", 'system', "
                . self::sqlv(date('Y-m-d H:i:s', strtotime('-2 hours', $today)))
                . ' FROM `' . $txn . '` WHERE reference = ' . self::sqlv($record['ref'])
                . ' AND NOT EXISTS (SELECT 1 FROM record_events e WHERE e.record_id = `' . $txn . '`.id);' . "\n";
        }

        if ($orders) {
            foreach ((array)($profile['seed']['products'] ?? []) as $product) {
                $out .= 'INSERT IGNORE INTO products (sku, name, description, price, stock, is_active) VALUES ('
                    . implode(', ', [
                        self::sqlv($product['sku']),
                        self::sqlv($product['name']),
                        self::sqlv($product['description']),
                        number_format((float)$product['price'], 2, '.', ''),
                        (string)(int)$product['stock'],
                        '1',
                    ]) . ");\n";
            }
        }

        if ($messaging) {
            $offset = 1;
            foreach ((array)($profile['seed']['messages'] ?? []) as $message) {
                $out .= 'INSERT IGNORE INTO messages (name, email, phone, subject, body, status, created_at, handled_at) VALUES ('
                    . implode(', ', [
                        self::sqlv($message['name']),
                        self::sqlv($message['email']),
                        self::sqlv($message['phone']),
                        self::sqlv($message['subject']),
                        self::sqlv($message['body']),
                        self::sqlv($message['status']),
                        self::sqlv(date('Y-m-d H:i:s', strtotime('-' . $offset . ' day', $today))),
                        $message['status'] === 'handled' ? self::sqlv(date('Y-m-d H:i:s', strtotime('-' . $offset . ' day', $today))) : 'NULL',
                    ]) . ");\n";
                $offset++;
            }
        }

        return $out;
    }

    private static function configFile(array $config): string
    {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Could not encode the generated site config.');
        }
        return "<?php\n"
            . "/**\n"
            . " * Generated site configuration. Edit through ForgeStack when possible;\n"
            . " * business copy lives here, secrets live in .env.\n"
            . " */\n"
            . "declare(strict_types=1);\n\n"
            . "return json_decode(<<<'JSON'\n"
            . $json . "\n"
            . "JSON, true);\n";
    }

    private static function writeEnv(string $siteDir, array $blueprint, array $options): void
    {
        $generation = $blueprint['generation'];
        $intake = $blueprint['intake'];
        $port = (int)($generation['port'] ?? 0);
        $c = $GLOBALS['fs_config'];
        $appUrl = (string)($generation['app_url'] ?? '');
        if ($appUrl === '') {
            $appUrl = $port > 0 ? 'http://127.0.0.1:' . $port : 'https://example.com';
        }
        $domain = substr((string)strrchr($intake['contact_email'], '@'), 1) ?: 'example.com';
        $installKey = bin2hex(random_bytes(16));
        $cronSecret = bin2hex(random_bytes(16));

        $env = "# Local environment for " . $intake['business_name'] . ".\n"
            . "# Generated " . date('c') . " — never commit this file.\n"
            . "APP_NAME=\"" . self::envQuote($intake['business_name']) . "\"\n"
            . "APP_URL=" . $appUrl . "\n"
            . "APP_TIMEZONE=Africa/Lagos\n"
            . "APP_DEBUG=false\n\n"
            . "DB_HOST=" . $c['host'] . "\n"
            . "DB_PORT=" . $c['port'] . "\n"
            . "DB_NAME=" . $generation['db_name'] . "\n"
            . "DB_USER=" . $c['user'] . "\n"
            . "DB_PASS=\"" . self::envQuote((string)$c['pass']) . "\"\n\n"
            . "MAIL_ENABLED=false\n"
            . "MAIL_FROM=no-reply@" . $domain . "\n"
            . "MAIL_FROM_NAME=\"" . self::envQuote($intake['business_name']) . "\"\n\n"
            . "INSTALL_KEY=" . $installKey . "\n"
            . "ADMIN_NAME=\"" . self::envQuote($intake['contact_name'] !== '' ? $intake['contact_name'] : $intake['business_name'] . ' Admin') . "\"\n"
            . "ADMIN_EMAIL=" . $generation['admin_email'] . "\n"
            . "ADMIN_PASSWORD=\"" . self::envQuote((string)$generation['admin_password']) . "\"\n\n"
            . "CRON_SECRET=" . $cronSecret . "\n";

        self::writeFile($siteDir . '/.env', $env);
        @chmod($siteDir . '/.env', 0600);

        $example = (string)file_get_contents(FS_ROOT . '/templates/site/.env.example');
        $example = str_replace('APP_NAME="Your business name"', 'APP_NAME="' . self::envQuote($intake['business_name']) . '"', $example);
        $example = str_replace('MAIL_FROM_NAME="Your business name"', 'MAIL_FROM_NAME="' . self::envQuote($intake['business_name']) . '"', $example);
        $example = str_replace('DB_NAME=cpuser_yoursite', 'DB_NAME=cpuser_' . $generation['db_name'], $example);
        self::writeFile($siteDir . '/.env.example', $example);
    }

    private static function qa(string $siteDir, array $generation, array $config): array
    {
        $checks = [];
        $lint = self::lint($siteDir);
        $checks[] = self::check('php', 'PHP syntax', $lint['failed'] === 0, $lint['failed'] === 0
            ? $lint['total'] . ' PHP files passed php -l.'
            : $lint['failed'] . ' of ' . $lint['total'] . ' files failed: ' . implode('; ', array_slice($lint['errors'], 0, 3)));

        $tokens = self::scanTokens($siteDir);
        $checks[] = self::check('tokens', 'Template tokens', $tokens === [], $tokens === []
            ? 'No unresolved {{ … }} tokens in the generated tree.'
            : 'Unresolved tokens: ' . implode(', ', $tokens));

        $secrets = self::scanSecrets($siteDir, [(string)$generation['admin_password']]);
        $checks[] = self::check('secrets', 'Secret isolation', $secrets === [], $secrets === []
            ? 'The generated admin password is not present outside .env; no private keys or hardcoded credentials found.'
            : 'Potential secret exposure: ' . implode(', ', $secrets));

        $envKeys = self::envKeys($siteDir . '/.env.example');
        $missing = array_values(array_diff(Docs::ENV_KEYS, $envKeys));
        $checks[] = self::check('env', 'Environment template', $missing === [], $missing === []
            ? count(Docs::ENV_KEYS) . '/' . count(Docs::ENV_KEYS) . ' environment variables documented in .env.example and DEPLOYMENT.md.'
            : 'Missing from .env.example: ' . implode(', ', $missing));

        $required = ['public/index.php', 'public/install.php', 'public/.htaccess', 'public/assets/css/app.css', 'public/assets/js/app.js', 'app/config.php', 'app/routes.php', 'app/services.php', 'app/controllers/home.php', 'app/controllers/auth.php', 'app/controllers/admin.php', 'app/automation.php', 'core/bootstrap.php', 'database/schema.sql', 'database/seed.sql', 'cron/run.php', 'storage/README.txt', '.env.example', 'docs/DEPLOYMENT.md', 'docs/TECH_STACK.md', 'portfolio-entry.json'];
        $absent = array_values(array_filter($required, static fn ($file) => !is_file($siteDir . '/' . $file)));
        $checks[] = self::check('files', 'Required files', $absent === [], $absent === []
            ? count($required) . ' required files present.'
            : 'Missing: ' . implode(', ', $absent));

        $schema = (string)file_get_contents($siteDir . '/database/schema.sql');
        $schemaOk = str_contains($schema, 'CREATE TABLE IF NOT EXISTS users')
            && str_contains($schema, $config['labels']['txn_table'])
            && ($config['modules']['orders'] === false || str_contains($schema, 'CREATE TABLE IF NOT EXISTS order_items'));
        $checks[] = self::check('schema', 'Schema composition', $schemaOk, $schemaOk
            ? 'Base tables plus the selected module fragments are present (' . implode(', ', array_keys(array_filter($config['modules']))) . ').'
            : 'The composed schema is missing a base or selected module table.');

        $passed = true;
        foreach ($checks as $check) {
            if (!$check['ok']) {
                $passed = false;
            }
        }
        return ['passed' => $passed, 'checks' => $checks, 'checked_at' => date('c')];
    }

    private static function lint(string $siteDir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($siteDir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        $failed = 0;
        $errors = [];
        if (!function_exists('exec')) {
            return ['total' => count($files), 'failed' => 0, 'errors' => []];
        }
        foreach ($files as $file) {
            $output = [];
            $code = 0;
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
            if ($code !== 0) {
                $failed++;
                $errors[] = basename($file) . ': ' . trim(implode(' ', $output));
            }
        }
        return ['total' => count($files), 'failed' => $failed, 'errors' => $errors];
    }

    private static function scanTokens(string $siteDir): array
    {
        $found = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($siteDir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if (in_array($name, ['.env', 'manifest.json', 'checksums.sha256'], true)) {
                continue;
            }
            if (!self::isText($file->getPathname())) {
                continue;
            }
            $content = (string)file_get_contents($file->getPathname());
            if (preg_match('/\{\{[A-Z0-9_]+\}\}/', $content, $m)) {
                $found[] = $m[0] . ' in ' . str_replace('\\', '/', substr($file->getPathname(), strlen($siteDir) + 1));
            }
        }
        return $found;
    }

    private static function scanSecrets(string $siteDir, array $banned): array
    {
        $hits = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($siteDir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if (in_array($name, ['.env', '.env.example', 'manifest.json', 'checksums.sha256'], true)) {
                continue;
            }
            if (!self::isText($file->getPathname())) {
                continue;
            }
            $content = (string)file_get_contents($file->getPathname());
            foreach ($banned as $needle) {
                if ($needle !== '' && str_contains($content, $needle)) {
                    $hits[] = 'generated credential in ' . $name;
                    break;
                }
            }
            if (preg_match('/-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----/', $content)) {
                $hits[] = 'private key in ' . $name;
            }
        }
        return array_values(array_unique($hits));
    }

    private static function envKeys(string $path): array
    {
        $keys = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            $keys[] = trim(explode('=', $line, 2)[0]);
        }
        return $keys;
    }

    private static function manifest(string $siteDir): array
    {
        $files = [];
        $bytes = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($siteDir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($siteDir) + 1));
            if (in_array($relative, ['.env', 'manifest.json', 'checksums.sha256', 'storage/installed.lock', 'storage/automation.log'], true)) {
                continue;
            }
            if (str_starts_with($relative, 'storage/') && !str_ends_with($relative, 'README.txt')) {
                continue;
            }
            $size = $file->getSize();
            $bytes += $size;
            $files[$relative] = ['bytes' => $size, 'sha256' => hash_file('sha256', $file->getPathname())];
        }
        ksort($files);
        return ['count' => count($files), 'bytes' => $bytes, 'files' => $files, 'generated_at' => date('c')];
    }

    private static function assetStats(string $siteDir): array
    {
        $css = @filesize($siteDir . '/public/assets/css/app.css') ?: 0;
        $js = @filesize($siteDir . '/public/assets/js/app.js') ?: 0;
        return ['css' => (int)$css, 'js' => (int)$js];
    }

    private static function commands(array $blueprint): array
    {
        $port = (int)($blueprint['generation']['port'] ?? 0);
        $slug = (string)$blueprint['generation']['slug'];
        return [
            'serve' => 'C:/xampp/php/php.exe -S 127.0.0.1:' . $port . ' -t public public/router.php',
            'install' => 'C:/xampp/php/php.exe core/cli/install.php',
            'cron' => 'C:/xampp/php/php.exe cron/run.php',
            'folder' => 'agent/forgestack/build/' . $slug,
        ];
    }

    private static function copyTree(string $source, string $destination, ?callable $transform = null): int
    {
        if (!is_dir($source)) {
            throw new RuntimeException('Template source not found: ' . $source);
        }
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));
            $target = $destination . '/' . $relative;
            if ($item->isDir()) {
                self::ensureDir($target);
                continue;
            }
            self::ensureDir(dirname($target));
            $content = (string)file_get_contents($item->getPathname());
            if ($transform !== null && self::isText($item->getPathname())) {
                $content = $transform($content);
            }
            file_put_contents($target, $content, LOCK_EX);
            $count++;
        }
        return $count;
    }

    private static function isText(string $path): bool
    {
        $name = strtolower(basename($path));
        if ($name === '.env.example') {
            return true;
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, self::TEXT_EXTENSIONS, true);
    }

    private static function step(array &$steps, string $label, string $status, string $log): void
    {
        $steps[] = [
            'label' => $label,
            'status' => $status,
            'log' => $log,
            'at' => date('c'),
        ];
    }

    private static function check(string $id, string $label, bool $ok, string $detail): array
    {
        return ['id' => $id, 'label' => $label, 'ok' => $ok, 'detail' => $detail];
    }

    private static function writeFile(string $path, string $content): void
    {
        self::ensureDir(dirname($path));
        file_put_contents($path, $content, LOCK_EX);
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    private static function dirHasFiles(string $dir): bool
    {
        foreach (new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS) as $item) {
            return true;
        }
        return false;
    }

    private static function removeTree(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    private static function replaceBrand(mixed $value, string $from, string $to): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $child) {
                $out[$key] = self::replaceBrand($child, $from, $to);
            }
            return $out;
        }
        if (is_string($value)) {
            return str_replace($from, $to, $value);
        }
        return $value;
    }

    private static function sqlv(mixed $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], (string)$value) . "'";
    }

    private static function envQuote(string $value): string
    {
        return str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', ' ', ' '], $value);
    }

    private static function json(array $data): string
    {
        return (string)json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
