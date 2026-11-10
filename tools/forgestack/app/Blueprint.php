<?php
/**
 * Blueprint — validate an intake and derive everything the generator needs:
 * page map, data model, roles, automation plan and the curated site config.
 */
declare(strict_types=1);

final class Blueprint
{
    public const FEATURES = ['auth', 'admin', 'bookings', 'orders', 'payments', 'messaging', 'reports'];
    public const PAGES = ['home', 'about', 'services', 'pricing', 'faq', 'contact', 'gallery', 'blog'];
    public const TONES = ['calm', 'confident', 'warm', 'technical', 'premium'];

    /** Static text shown in the wizard and README. */
    public static function featureLabels(): array
    {
        return [
            'auth' => ['Account authentication', 'Register, sign in, password hashing, throttled logins and a private portal.'],
            'admin' => ['Admin dashboard', 'Operations console with live counts, status controls and an audit trail.'],
            'bookings' => ['Bookings & tracking', 'A public request form with a reference, an email outbox entry and a public status lookup.'],
            'orders' => ['Orders & stock', 'A small catalogue with transactional stock decrement — never oversells.'],
            'payments' => ['Payments ledger', 'Manual payment capture against a booking or order, with a real ledger and totals.'],
            'messaging' => ['Contact inbox', 'A validated enquiry form that persists to the database and an admin inbox.'],
            'reports' => ['Reports', 'Live aggregates over real rows: volume, status mix, revenue and demand.'],
        ];
    }

    public static function validate(array $input): array
    {
        $errors = [];
        $name = trim((string)($input['business_name'] ?? ''));
        if (mb_strlen($name) < 2) {
            $errors['business_name'] = 'Give the business a name (at least 2 characters).';
        } elseif (mb_strlen($name) > 80) {
            $errors['business_name'] = 'Keep the business name under 80 characters.';
        }
        if (trim((string)($input['industry'] ?? '')) === '') {
            $errors['industry'] = 'Choose the industry this site is for.';
        }
        $idea = trim((string)($input['idea'] ?? ''));
        if (mb_strlen($idea) < 10) {
            $errors['idea'] = 'Describe the offering in one line (at least 10 characters).';
        }
        $problem = trim((string)($input['problem'] ?? ''));
        if (mb_strlen($problem) < 20) {
            $errors['problem'] = 'Explain the problem this business solves (at least 20 characters).';
        }
        $audience = trim((string)($input['audience'] ?? ''));
        if (mb_strlen($audience) < 5) {
            $errors['audience'] = 'Describe who experiences the problem.';
        }
        $email = trim((string)($input['contact_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Enter a valid contact email address.';
        }
        $phone = trim((string)($input['contact_phone'] ?? ''));
        if (mb_strlen($phone) < 7) {
            $errors['contact_phone'] = 'Enter a contact phone number.';
        }
        $features = array_values(array_intersect(self::FEATURES, (array)($input['features'] ?? [])));
        if (!$features) {
            $errors['features'] = 'Select at least one feature.';
        }
        $pages = array_values(array_intersect(self::PAGES, (array)($input['pages'] ?? [])));
        if (!$pages) {
            $errors['pages'] = 'Select the pages the site needs.';
        }
        $tone = strtolower(trim((string)($input['tone'] ?? '')));
        if (!in_array($tone, self::TONES, true)) {
            $errors['tone'] = 'Choose a brand tone.';
        }
        return $errors;
    }

    /**
     * @param array $options slug, db_name, port, admin_email, admin_password, install_db
     */
    public static function build(array $input, array $options = []): array
    {
        $features = array_values(array_intersect(self::FEATURES, (array)($input['features'] ?? [])));
        // Auth and the admin console are the operating surface of every generated
        // site; select them automatically and record that decision honestly.
        $auto = [];
        foreach (['auth', 'admin'] as $required) {
            if (!in_array($required, $features, true)) {
                $features[] = $required;
                $auto[] = $required;
            }
        }
        $features = array_values(array_intersect(self::FEATURES, $features));
        $pagesRequested = array_values(array_intersect(self::PAGES, (array)($input['pages'] ?? [])));

        $normalized = [
            'business_name' => trim((string)($input['business_name'] ?? '')),
            'industry' => trim((string)($input['industry'] ?? '')),
            'idea' => trim((string)($input['idea'] ?? '')),
            'problem' => trim((string)($input['problem'] ?? '')),
            'audience' => trim((string)($input['audience'] ?? '')),
            'pages_requested' => $pagesRequested,
            'features' => $features,
            'auto_added_features' => $auto,
            'tone' => strtolower(trim((string)($input['tone'] ?? 'calm'))),
            'colour_direction' => trim((string)($input['colour_direction'] ?? '')),
            'contact_name' => trim((string)($input['contact_name'] ?? '')),
            'contact_email' => strtolower(trim((string)($input['contact_email'] ?? ''))),
            'contact_phone' => trim((string)($input['contact_phone'] ?? '')),
            'address' => trim((string)($input['address'] ?? '')),
            'mysql_available' => (bool)($input['mysql_available'] ?? true),
            'references' => trim((string)($input['references'] ?? '')),
            'notes' => trim((string)($input['notes'] ?? '')),
        ];

        $profile = fs_profile(fs_profile_key($normalized['industry'], $normalized['idea']));
        // A requested direction maps to a curated palette; otherwise the industry
        // profile supplies its own. Colours are never generated at random.
        $paletteKey = $normalized['colour_direction'] === ''
            ? $profile['palette']
            : fs_palette_for($normalized['colour_direction']);

        $arch = self::architecture($normalized, $profile, $paletteKey);
        $slugSeed = (string)($options['slug'] ?? '');
        $slug = fs_slug($slugSeed !== '' ? $slugSeed : $normalized['business_name'], $profile['key']);
        $dbName = (string)($options['db_name'] ?? '');
        $dbName = $dbName !== '' ? fs_db_name($dbName) : fs_db_name('forge_' . $slug);

        $adminEmail = strtolower(trim((string)($options['admin_email'] ?? $normalized['contact_email'])));
        $adminPassword = (string)($options['admin_password'] ?? '');
        if ($adminPassword === '') {
            $adminPassword = self::randomPassword();
            $generated = true;
        } else {
            $generated = false;
        }

        return [
            'schema' => 'forgestack/1',
            'intake' => $normalized,
            'profile' => $profile['key'],
            'profile_label' => $profile['label'],
            'palette' => $paletteKey,
            'palette_name' => fs_palettes()[$paletteKey]['name'] ?? $paletteKey,
            'architecture' => $arch,
            'generation' => [
                'slug' => $slug,
                'db_name' => $dbName,
                'port' => isset($options['port']) ? (int)$options['port'] : null,
                'admin_email' => $adminEmail,
                'admin_password' => $adminPassword,
                'password_generated' => $generated,
                'install_db' => (bool)($options['install_db'] ?? $normalized['mysql_available']),
                'app_url' => (string)($options['app_url'] ?? ''),
            ],
        ];
    }

    private static function randomPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 20; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $password . '!7';
    }

    /** Derive the page map, entities, roles and automation plan. */
    private static function architecture(array $intake, array $profile, string $paletteKey): array
    {
        $f = array_flip($intake['features']);
        $labels = $profile['labels'];
        $pages = [];
        $add = static function (array &$pages, string $path, string $title, string $purpose, string $feature = 'core', string $status = 'generated') use (&$add): void {
            foreach ($pages as $existing) {
                if ($existing['path'] === $path) {
                    return;
                }
            }
            $pages[] = ['path' => $path, 'title' => $title, 'purpose' => $purpose, 'feature' => $feature, 'status' => $status];
        };

        $add($pages, '/', 'Home', 'Industry-specific landing page: hero, services, proof, process and conversion path.', 'core');
        $add($pages, '/book', $labels['book'], 'Public request form with server-side validation, reference generation and email outbox.', 'bookings');
        $add($pages, '/track', $labels['track'], 'Reference lookup with status timeline; exposes no contact details to the public.', 'bookings');

        $requested = array_flip($intake['pages_requested']);
        if (isset($requested['about'])) {
            $add($pages, '/about', 'About', 'Editorial story, numbers and the people behind the business.', 'core');
        }
        if (isset($requested['services']) || isset($requested['pricing'])) {
            $add($pages, '/services', 'Services & pricing', 'Service detail, process and indicative pricing.', 'core');
        }
        if (isset($requested['faq'])) {
            $add($pages, '/faq', 'FAQ', 'Objection-handling questions with operator-written answers.', 'core');
        }
        if (isset($f['orders'])) {
            $add($pages, '/shop', $profile['key'] === 'restaurant' ? 'Menu & collection' : 'Catalogue', 'Small catalogue with transactional stock decrement and an order reference.', 'orders');
        }
        if (isset($f['messaging'])) {
            $add($pages, '/contact', 'Contact', 'Validated enquiry form persisted to the database and answered from the admin inbox.', 'messaging');
        } else {
            $add($pages, '/contact', 'Contact details', 'Contact page is limited to address, phone and email because the messaging feature was not selected.', 'core', 'limited');
        }
        if (isset($f['auth'])) {
            $add($pages, '/login', 'Sign in', 'Throttled login with CSRF and session regeneration.', 'auth');
            $add($pages, '/register', 'Create account', 'Public account registration for the customer role.', 'auth');
            $add($pages, '/portal', $labels['portal'], 'Customer-owned records and orders.', 'auth');
        }
        $add($pages, '/admin', $labels['admin'], 'Operations dashboard with live counts, 7-day volume and recent activity.', 'admin');
        $add($pages, '/admin/records', 'All ' . $labels['txn_plural'], 'Search, filter and update the status of every record.', 'admin');
        $add($pages, '/admin/users', 'People', 'Accounts and activation state; administrator-only.', 'admin');
        $add($pages, '/admin/outbox', 'Email outbox', 'Every queued message with delivery state and error.', 'admin');
        if (isset($f['orders'])) {
            $add($pages, '/admin/orders', 'Orders', 'Order queue with fulfilment status updates.', 'orders');
            $add($pages, '/admin/products', 'Catalogue & stock', 'Product management and restocking.', 'orders');
        }
        if (isset($f['messaging'])) {
            $add($pages, '/admin/messages', 'Enquiries', 'Inbox for contact-form submissions.', 'messaging');
        }
        if (isset($f['reports'])) {
            $add($pages, '/admin/reports', 'Reports', 'Aggregates over real rows: volume, status mix, revenue and demand.', 'reports');
        }
        if (isset($f['payments'])) {
            $add($pages, '/admin/payments', 'Payments', 'Manual payment capture ledger and collected totals.', 'payments');
        }

        // Pages the intake asked for that are intentionally not generated.
        $deferred = [];
        foreach (['gallery', 'blog'] as $extra) {
            if (isset($requested[$extra])) {
                $deferred[] = [
                    'page' => ucfirst($extra),
                    'reason' => $extra === 'gallery'
                        ? 'Needs an image upload pipeline and media storage policy; add after the first content shoot.'
                        : 'Needs an editorial workflow and SEO plan; deliberately out of scope for a first deployment.',
                ];
            }
        }

        $entities = [
            ['name' => 'users', 'table' => 'users', 'purpose' => 'Accounts, password hashes and roles (admin / member).', 'fields' => ['name', 'email', 'phone', 'password_hash', 'role', 'is_active', 'last_login_at']],
            ['name' => 'login_attempts', 'table' => 'login_attempts', 'purpose' => 'Throttling failed sign-ins by email and IP.', 'fields' => ['email', 'ip', 'attempted_at']],
            ['name' => 'email_outbox', 'table' => 'email_outbox', 'purpose' => 'Every notification is queued before delivery is attempted.', 'fields' => ['recipient', 'subject', 'body_html', 'message_type', 'status', 'created_at', 'sent_at']],
            ['name' => 'audit_log', 'table' => 'audit_log', 'purpose' => 'Who changed what, with IP and timestamp.', 'fields' => ['user_id', 'action', 'detail', 'ip', 'created_at']],
            ['name' => ucfirst($labels['txn_one']), 'table' => $labels['txn_table'], 'purpose' => 'Primary requests: date, time, choices, quantity, status and payment state.', 'fields' => ['reference', 'customer_name', 'customer_email', 'customer_phone', 'request_date', 'request_time', 'choice_a', 'choice_b', 'quantity', 'notes', 'amount', 'status']],
            ['name' => 'Record event', 'table' => 'record_events', 'purpose' => 'Immutable status timeline shown on the public tracking page.', 'fields' => ['record_id', 'status', 'note', 'source', 'created_at']],
        ];
        if (isset($f['orders'])) {
            $entities[] = ['name' => 'Product', 'table' => 'products', 'purpose' => 'Catalogue items with price and live stock.', 'fields' => ['sku', 'name', 'description', 'price', 'stock', 'is_active']];
            $entities[] = ['name' => 'Order', 'table' => 'orders', 'purpose' => 'Collection orders with a reference and status.', 'fields' => ['reference', 'customer_name', 'customer_email', 'total', 'status', 'created_at']];
            $entities[] = ['name' => 'Order item', 'table' => 'order_items', 'purpose' => 'Line items; stock is decremented inside the order transaction.', 'fields' => ['order_id', 'product_id', 'product_name', 'unit_price', 'quantity', 'line_total']];
        }
        if (isset($f['payments'])) {
            $entities[] = ['name' => 'Payment', 'table' => 'payments', 'purpose' => 'Manual payment capture against a record or order.', 'fields' => ['reference', 'record_id', 'order_id', 'amount', 'method', 'status', 'created_at']];
        }
        if (isset($f['messaging'])) {
            $entities[] = ['name' => 'Enquiry', 'table' => 'messages', 'purpose' => 'Contact-form submissions with a handled state.', 'fields' => ['name', 'email', 'phone', 'subject', 'body', 'status', 'created_at']];
        }

        $roles = [
            ['key' => 'admin', 'name' => 'Administrator', 'access' => 'Full access to every record, person, payment, inbox, report and automation run.', 'seeded' => true],
            ['key' => 'member', 'name' => ucfirst($labels['customer']), 'access' => 'Own portal, own records and own orders only.', 'seeded' => false],
            ['key' => 'public', 'name' => 'Public visitor', 'access' => 'Home, request form, catalogue and reference lookup. No access to other people’s data.', 'seeded' => true],
        ];

        $automation = [
            ['key' => 'upcoming_reminders', 'label' => 'Upcoming reminders', 'schedule' => 'Every 15 minutes', 'behaviour' => 'Emails a reminder for confirmed ' . $labels['txn_plural'] . ' due tomorrow and stamps reminder_sent_at so it never repeats.'],
            ['key' => 'close_past_requests', 'label' => 'Close past requests', 'schedule' => 'Daily', 'behaviour' => 'Closes past open ' . $labels['txn_plural'] . ' as “' . $profile['status_meta'][$profile['close_past_status']]['label'] . '”, writes a timeline event and emails the customer.'],
            ['key' => 'daily_digest', 'label' => 'Daily operations digest', 'schedule' => 'Daily', 'behaviour' => 'One summary email to the active administrator: volume, pending work, new enquiries and delivery failures.'],
        ];
        if (isset($f['orders'])) {
            $automation[] = ['key' => 'low_stock_alert', 'label' => 'Low stock alert', 'schedule' => 'Daily', 'behaviour' => 'Emails a single low-stock list when any active product falls to five units or fewer.'];
        }
        if (isset($f['payments'])) {
            $automation[] = ['key' => 'payment_followup', 'label' => 'Payment follow-up', 'schedule' => 'Daily', 'behaviour' => 'Emails one polite payment reminder for records that are past their date and still unpaid.'];
        }

        $stack = [
            'PHP 8.2 on cPanel (single shared-hosting account)',
            'MySQL 8 / MariaDB 10.3+ via PDO prepared statements',
            'Apache .htaccess rewrites and security headers',
            'Plain CSS design system, no build step and no CDN framework',
            'cPanel cron for automation; local mail() through the outbox',
        ];

        return [
            'profile' => $profile['key'],
            'profile_label' => $profile['label'],
            'tagline' => $profile['copy']['tagline'],
            'pages' => $pages,
            'deferred_pages' => $deferred,
            'entities' => $entities,
            'roles' => $roles,
            'automation' => $automation,
            'features' => $intake['features'],
            'stack' => $stack,
            'palette' => $paletteKey,
            'palette_name' => fs_palettes()[$paletteKey]['name'] ?? $paletteKey,
            'palette_reason' => $intake['colour_direction'] !== ''
                ? 'Requested direction “' . $intake['colour_direction'] . '” mapped to the curated ' . (fs_palettes()[$paletteKey]['name'] ?? $paletteKey) . ' palette.'
                : 'No direction supplied, so the industry’s curated palette was applied: ' . (fs_palettes()[$paletteKey]['name'] ?? $paletteKey) . '.',
            'typography' => $profile['fonts']['display'] . ' / ' . $profile['fonts']['body'],
            'tone' => $intake['tone'],
            'routes' => self::routeList($intake['features']),
            'qa_plan' => [
                'php -l on every generated PHP file',
                'unresolved template token scan',
                'required-file and env-var completeness checks',
                'secret scan across tracked files',
                'HTTP smoke test after install (operator run)',
            ],
        ];
    }

    public static function routeList(array $features): array
    {
        $f = array_flip($features);
        $routes = [
            ['GET', '/', 'home_index'],
            ['GET', '/book', 'book_form'],
            ['POST', '/book', 'book_store'],
            ['GET', '/track', 'track_show'],
            ['GET', '/login', 'auth_login_form'],
            ['POST', '/login', 'auth_login'],
            ['GET', '/register', 'auth_register_form'],
            ['POST', '/register', 'auth_register'],
            ['POST', '/logout', 'auth_logout'],
            ['GET', '/portal', 'portal_index'],
            ['GET', '/admin', 'admin_dashboard'],
            ['GET', '/admin/records', 'admin_records'],
            ['POST', '/admin/records/{id}/status', 'admin_record_status'],
            ['GET', '/admin/users', 'admin_users'],
            ['POST', '/admin/users/{id}/toggle', 'admin_user_toggle'],
            ['GET', '/admin/outbox', 'admin_outbox'],
            ['POST', '/admin/automation', 'admin_run_automation'],
        ];
        if (isset($f['orders'])) {
            $routes[] = ['GET', '/shop', 'shop_index'];
            $routes[] = ['POST', '/order', 'order_store'];
            $routes[] = ['GET', '/admin/orders', 'admin_orders'];
            $routes[] = ['POST', '/admin/orders/{id}/status', 'admin_order_status'];
            $routes[] = ['GET', '/admin/products', 'admin_products'];
            $routes[] = ['POST', '/admin/products', 'admin_product_store'];
            $routes[] = ['POST', '/admin/products/{id}/restock', 'admin_product_restock'];
        }
        if (isset($f['messaging'])) {
            $routes[] = ['GET', '/contact', 'contact_form'];
            $routes[] = ['POST', '/contact', 'contact_store'];
            $routes[] = ['GET', '/admin/messages', 'admin_messages'];
            $routes[] = ['POST', '/admin/messages/{id}/handled', 'admin_message_handled'];
        }
        if (isset($f['reports'])) {
            $routes[] = ['GET', '/admin/reports', 'admin_reports'];
        }
        if (isset($f['payments'])) {
            $routes[] = ['POST', '/admin/records/{id}/paid', 'admin_record_paid'];
            $routes[] = ['GET', '/admin/payments', 'admin_payments'];
        }
        return $routes;
    }
}
