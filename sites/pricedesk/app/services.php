<?php
declare(strict_types=1);

/**
 * PriceDesk services — reference data, the audit trail, and the two queries the
 * board is built from. Everything that touches the database lives here so the
 * controllers stay about requests and the engine stays about arithmetic.
 */

require_once __DIR__ . '/engines/Pricing.php';

final class Board
{
    /** @var array<string,array<string,float>> tool => code => value */
    private static array $rates = [];

    /** @return array<string,float> */
    public static function rates(string $tool = 'haul'): array
    {
        if (!isset(self::$rates[$tool])) {
            $rows = Db::all('SELECT code, value FROM rates WHERE tool = ? ORDER BY id', [$tool]);
            $found = [];
            foreach ($rows as $row) {
                $found[(string)$row['code']] = (float)$row['value'];
            }
            // A fresh install has no rows yet; the engine's published defaults
            // keep the board arithmetically identical to the demo until an
            // administrator deliberately moves a number.
            self::$rates[$tool] = $found + Pricing::rates();
        }
        return self::$rates[$tool];
    }

    public static function refreshRates(): void
    {
        self::$rates = [];
    }

    public static function audit(string $action, string $target = '', array $meta = []): void
    {
        Db::insert('audit_log', [
            'actor_id' => Auth::id(),
            'action' => $action,
            'target' => $target,
            'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function markets(bool $activeOnly = true): array
    {
        return Db::all(
            'SELECT * FROM markets' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY name'
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function commodities(bool $activeOnly = true): array
    {
        return Db::all(
            'SELECT * FROM commodities' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY category, name'
        );
    }

    public static function commodity(int $id): ?array
    {
        return Db::one('SELECT * FROM commodities WHERE id = ?', [$id]);
    }

    public static function market(int $id): ?array
    {
        return Db::one('SELECT * FROM markets WHERE id = ?', [$id]);
    }

    /** The unit weights a commodity row carries, decoded. @return array<string,float> */
    public static function weights(array $commodity): array
    {
        $raw = json_decode((string)($commodity['units'] ?? '{}'), true);
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $unit => $kg) {
            if (is_numeric($kg) && (float)$kg > 0) {
                $out[mb_strtolower((string)$unit)] = (float)$kg;
            }
        }
        return $out;
    }

    /**
     * Verified quotes for one commodity across one or every market, already
     * normalised to naira per kilogram and keyed the way the engine reads them.
     * The snake_case column names are a database detail Pricing never needed.
     *
     * @return array<int,array{perKg:float,reportDate:string,reporterId:int,market:string}>
     */
    public static function quotes(int $commodityId, ?int $marketId = null, int $days = 14): array
    {
        $commodity = self::commodity($commodityId);
        if ($commodity === null) {
            return [];
        }
        $weights = self::weights($commodity);
        $rows = Db::all(
            'SELECT r.price, r.unit, r.quantity, r.report_date, r.reporter_id, m.name AS market_name
               FROM price_reports r
               JOIN markets m ON m.id = r.market_id
              WHERE r.commodity_id = ? AND r.status = \'verified\'
                AND r.report_date >= DATE_SUB(CURDATE(), INTERVAL ' . max(1, $days) . ' DAY)'
            . ($marketId !== null ? ' AND r.market_id = ?' : '')
            . ' ORDER BY r.report_date DESC, m.name',
            $marketId === null ? [$commodityId] : [$commodityId, $marketId]
        );

        return array_map(static fn (array $row): array => [
            'perKg' => Pricing::perKg(
                (float)$row['price'],
                (string)$row['unit'],
                (int)$row['quantity'],
                (float)$commodity['unit_weight_kg'],
                $weights
            ),
            'reportDate' => (string)$row['report_date'],
            'reporterId' => (int)$row['reporter_id'],
            'market' => (string)$row['market_name'],
        ], $rows);
    }

    /**
     * The board: every active commodity against its aggregate for one market,
     * or across all markets when $marketId is null.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function board(?int $marketId, int $days = 14): array
    {
        $out = [];
        foreach (self::commodities() as $commodity) {
            $quotes = self::quotes((int)$commodity['id'], $marketId, $days);
            if ($quotes === []) {
                continue;
            }
            // One market: its own spread. Every market: the middle of the
            // markets, measured market by market — see Pricing::acrossMarkets().
            $agg = $marketId === null
                ? Pricing::acrossMarkets($quotes)
                : Pricing::aggregate($quotes);
            $agg['commodity'] = $commodity['name'];
            $agg['category'] = $commodity['category'];
            $agg['unit_label'] = $commodity['unit_label'];
            $agg['commodity_id'] = (int)$commodity['id'];
            $out[] = $agg;
        }
        // Highest-volume lines first; a board that buries the staples under an
        // obscure import is a board nobody reads before trading.
        usort($out, static fn (array $a, array $b): int => [$b['count'], $b['median']] <=> [$a['count'], $a['median']]);
        return $out;
    }

    /** Pending queue for the administrator, oldest first. */
    public static function pending(int $limit = 100): array
    {
        return Db::all(
            'SELECT r.*, m.name AS market_name, c.name AS commodity_name, c.unit_weight_kg, c.units,
                    u.name AS reporter_name
               FROM price_reports r
               JOIN markets m ON m.id = r.market_id
               JOIN commodities c ON c.id = r.commodity_id
               LEFT JOIN users u ON u.id = r.reporter_id
              WHERE r.status = \'pending\'
              ORDER BY r.created_at ASC
              LIMIT ' . max(1, $limit)
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function reportsBy(int $userId, int $limit = 60): array
    {
        return Db::all(
            'SELECT r.*, m.name AS market_name, c.name AS commodity_name
               FROM price_reports r
               JOIN markets m ON m.id = r.market_id
               JOIN commodities c ON c.id = r.commodity_id
              WHERE r.reporter_id = ?
              ORDER BY r.report_date DESC, r.id DESC
              LIMIT ' . max(1, $limit),
            [$userId]
        );
    }

    public static function hasReport(int $userId, int $marketId, int $commodityId, string $date): bool
    {
        return Db::one(
            'SELECT id FROM price_reports WHERE reporter_id = ? AND market_id = ? AND commodity_id = ? AND report_date = ?',
            [$userId, $marketId, $commodityId, $date]
        ) !== null;
    }

    /** @param array<string,mixed> $input @param array<string,mixed> $result */
    public static function logCalculation(string $tool, string $label, array $input, array $result): int
    {
        return Db::insert('calculations', [
            'user_id' => Auth::id(),
            'tool' => $tool,
            'label' => mb_substr($label, 0, 160),
            'inputs' => json_encode($input, JSON_UNESCAPED_UNICODE),
            'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'headline' => money((float)($result['netPerKg'] ?? 0)),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Distinct days on which this reporter already filed, for the dashboard. */
    public static function contributionCount(int $userId): array
    {
        $row = Db::one(
            'SELECT COUNT(*) AS total,
                    SUM(status = \'verified\') AS verified,
                    SUM(status = \'pending\') AS pending,
                    SUM(status = \'rejected\') AS rejected,
                    MAX(report_date) AS latest
               FROM price_reports WHERE reporter_id = ?',
            [$userId]
        ) ?: [];
        return ['total' => (int)($row['total'] ?? 0), 'verified' => (int)($row['verified'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0), 'rejected' => (int)($row['rejected'] ?? 0),
            'latest' => $row['latest'] ?? null];
    }
}
