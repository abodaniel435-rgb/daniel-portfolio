<?php
declare(strict_types=1);

/**
 * PriceDesk automation.
 *
 * A price board fails quietly: the reporters stop reporting, and the board
 * keeps displaying last week's numbers as though they were current. These three
 * tasks exist because nobody notices that from the front page — the stale nudge
 * is the one that matters most, and the outlier sweep is what keeps a single
 * bad morning from becoming the median.
 */

return [

    /* A watched line with nothing verified for a week is a line the member is
       being quietly misled about. Nudge once per calendar week, not daily. */
    'nudge_stale_lines' => function (): string {
        $rows = Db::all(
            "SELECT w.user_id, u.name, u.email, m.name AS market_name, c.name AS commodity_name,
                    m.id AS market_id, c.id AS commodity_id,
                    MAX(r.report_date) AS latest
               FROM watches w
               JOIN users u ON u.id = w.user_id
               JOIN markets m ON m.id = w.market_id
               JOIN commodities c ON c.id = w.commodity_id
               LEFT JOIN price_reports r ON r.market_id = w.market_id
                                    AND r.commodity_id = w.commodity_id
                                    AND r.status = 'verified'
                                    AND r.report_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              WHERE u.is_active = 1 AND m.is_active = 1 AND c.is_active = 1
           GROUP BY w.id, u.name, u.email, m.name, c.name, m.id, c.id
            HAVING latest IS NULL
            LIMIT 200"
        );

        $sent = 0;
        $since = date('Y-m-d 00:00:00', strtotime('monday this week'));
        foreach ($rows as $row) {
            // Mailer has no "have I already sent this?" method, so the outbox
            // table is asked directly. Without this guard a quiet line becomes a
            // daily email, which is how members stop opening them.
            $already = Db::value(
                "SELECT id FROM email_outbox
                  WHERE recipient = ? AND message_type = 'stale_nudge' AND created_at >= ? LIMIT 1",
                [(string)$row['email'], $since]
            );
            if ($already !== null) {
                continue;
            }
            try {
                Mailer::send((string)$row['email'], 'No verified price for ' . $row['commodity_name'],
                    Mailer::template('That line has gone quiet',
                        '<p>Hello ' . e($row['name']) . ',</p>'
                        . '<p>Nobody has filed a verified price for <strong>' . e($row['commodity_name'])
                        . '</strong> at <strong>' . e($row['market_name']) . '</strong> in the last seven days, so the '
                        . 'board is showing you nothing rather than something out of date.</p>',
                        'File a price', url('/report')
                    ), '', 'stale_nudge');
                $sent++;
            } catch (Throwable) {
                break;
            }
        }
        return $sent . ' watcher(s) nudged about a stale line';
    },

    /* Anything in the queue that sits far from its verified peers is flagged for
       the administrator. The queue page shows the deviation on every pending row
       already and the log keeps this list, so the pooled email would be a third
       copy of the same news — and the third copy is how notices stop being read. */
    'flag_outliers' => function (): string {
        $pending = Board::pending(400);
        $flagged = [];
        foreach ($pending as $row) {
            $commodity = Board::commodity((int)$row['commodity_id']);
            if ($commodity === null) {
                continue;
            }
            $weights = Board::weights($commodity);
            $perKg = Pricing::perKg((float)$row['price'], (string)$row['unit'], (int)$row['quantity'],
                (float)$commodity['unit_weight_kg'], $weights);

            $peers = array_map(static fn (array $p): array => [
                'perKg' => Pricing::perKg((float)$p['price'], (string)$p['unit'], (int)$p['quantity'],
                    (float)$commodity['unit_weight_kg'], $weights),
                'reportDate' => (string)$p['report_date'], 'reporterId' => (int)$p['reporter_id'],
            ], Db::all('SELECT price, unit, quantity, report_date, reporter_id FROM price_reports
                         WHERE commodity_id = ? AND market_id = ? AND status = \'verified\'
                           AND report_date >= DATE_SUB(?, INTERVAL 14 DAY)',
                [(int)$row['commodity_id'], (int)$row['market_id'], (string)$row['report_date']]));

            $agg = Pricing::aggregate($peers);
            if ($agg['count'] < 3 || $agg['median'] <= 0) {
                continue; // no peer group yet, so nothing to be an outlier against
            }
            $deviation = ($perKg - $agg['median']) / $agg['median'] * 100;
            if (abs($deviation) >= 60) {
                $flagged[] = sprintf('%s at %s — %s/kg vs a verified median of %s/kg (%+.0f%%), filed by %s',
                    $row['commodity_name'], $row['market_name'], money((float)$perKg),
                    money((float)$agg['median']), $deviation, $row['reporter_name'] ?? 'a member');
            }
        }

        if ($flagged === []) {
            return 'no pending report sits far from its peers';
        }
        foreach (Db::all("SELECT email, name FROM users WHERE role = 'admin' AND is_active = 1") as $admin) {
            Mailer::send((string)$admin['email'], count($flagged) . ' queued price(s) far from the board',
                Mailer::template('Outliers waiting for you',
                    '<p>These are in the queue and a long way from the verified median. That does not make them '
                    . 'wrong — a genuine price jump looks identical to a mistake, which is why a human decides.</p><ul><li>'
                    . implode('</li><li>', array_map('e', array_slice($flagged, 0, 20))) . '</li></ul>'),
                '', 'outlier_flag');
        }
        return count($flagged) . ' outlier(s) reported to the administrators';
    },

    'weekly_admin_digest' => function (): string {
        $counts = Db::one("SELECT
              (SELECT COUNT(*) FROM price_reports WHERE status = 'pending') AS pending,
              (SELECT COUNT(*) FROM price_reports WHERE report_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) AS filed7,
              (SELECT COUNT(*) FROM price_reports WHERE status = 'verified'
                  AND report_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) AS verified7") ?: [];

        $silent = Db::all("SELECT m.name, c.name AS commodity_name FROM markets m
                            CROSS JOIN commodities c
                            WHERE m.is_active = 1 AND c.is_active = 1
                              AND NOT EXISTS (SELECT 1 FROM price_reports r
                                WHERE r.market_id = m.id AND r.commodity_id = c.id
                                  AND r.status = 'verified'
                                  AND r.report_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY))
                            ORDER BY m.name, c.name LIMIT 12");

        $body = '<p>Reports filed in the last seven days: <strong>' . (int)($counts['filed7'] ?? 0)
            . '</strong>, of which <strong>' . (int)($counts['verified7'] ?? 0)
            . '</strong> were verified. <strong>' . (int)($counts['pending'] ?? 0)
            . '</strong> are still waiting in the queue.</p>';
        if ($silent !== []) {
            $body .= '<p>These market and commodity lines have had nothing verified for two weeks:</p><ul><li>'
                . implode('</li><li>', array_map(
                    static fn (array $r): string => e((string)$r['commodity_name']) . ' at ' . e((string)$r['name']),
                    $silent)) . '</li></ul>';
        }

        foreach (Db::all("SELECT email, name FROM users WHERE role = 'admin' AND is_active = 1") as $admin) {
            Mailer::send((string)$admin['email'], 'PriceDesk weekly board report',
                Mailer::template('The board this week', $body, 'Open the queue', url('/admin/queue')),
                '', 'admin_digest');
        }
        return 'digest sent (' . (int)($counts['pending'] ?? 0) . ' pending, ' . count($silent) . ' silent lines)';
    },
];
