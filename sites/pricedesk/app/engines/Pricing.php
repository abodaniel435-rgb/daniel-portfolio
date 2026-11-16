<?php
declare(strict_types=1);

/**
 * Pricing engine — unit normalisation, the board aggregate and the haul maths.
 *
 * Three rules hold this together, and they are the reason a shared board needs
 * a server rather than a spreadsheet on one phone:
 *
 * 1. Nothing is averaged until every quote is on the same axis. A crate of
 *    tomatoes and a bag of rice are not comparable numbers; per kilogram they
 *    are. The unit weights come from the commodity row, which an administrator
 *    curates, so a wrong basket size is a data fix and not a code fix.
 * 2. A median, never a mean, and the spread is printed next to it. One farmer
 *    quoting a fantasy price moves a mean; it barely moves a median, and the
 *    spread tells the reader how much they should trust what they are reading.
 * 3. A price gap is only worth crossing town for once the trip is paid for.
 *    The break-even is the number a seller actually needs, and it is the only
 *    one here that depends on distance.
 *
 * No database access in this file. Every figure arrives as an argument so the
 * whole engine can be exercised without a session.
 */
final class Pricing
{
    /**
     * Kilograms one unit of this commodity weighs.
     *
     * @param array<string,float> $weights unit slug => kg, from commodity.units
     */
    public static function unitKg(string $unit, float $fallbackKg, array $weights): float
    {
        $key = self::unitKey($unit, $weights);
        if (isset($weights[$key]) && $weights[$key] > 0) {
            return (float)$weights[$key];
        }
        return $fallbackKg > 0 ? $fallbackKg : 1.0;
    }

    /** @param array<string,float> $weights */
    public static function perKg(float $price, string $unit, int $quantity, float $fallbackKg, array $weights): float
    {
        $kg = self::unitKg($unit, $fallbackKg, $weights) * max(1, $quantity);
        return $kg > 0 ? $price / $kg : 0.0;
    }

    /**
     * Summarise the verified quotes for one commodity in one market.
     *
     * @param array<int,array{perKg:float,reportDate:string,reporterId:int}> $rows
     * @return array<string,mixed>
     */
    public static function aggregate(array $rows, int $todayOffsetDays = 0): array
    {
        $values = [];
        $dates = [];
        foreach ($rows as $row) {
            $value = (float)($row['perKg'] ?? 0);
            if ($value > 0) {
                $values[] = $value;
            }
            if (!empty($row['reportDate'])) {
                $dates[] = $row['reportDate'];
            }
        }

        $count = count($values);
        if ($count === 0) {
            return [
                'count' => 0, 'median' => 0.0, 'mean' => 0.0, 'min' => 0.0, 'max' => 0.0,
                'spreadPct' => 0.0, 'confidence' => 'none', 'freshness' => 'unknown',
                'lastReport' => null,
            ];
        }

        sort($values);
        $median = self::median($values);
        $min = $values[0];
        $max = $values[$count - 1];
        $mean = array_sum($values) / $count;

        return [
            'count' => $count,
            'median' => round($median, 2),
            'mean' => round($mean, 2),
            'min' => round($min, 2),
            'max' => round($max, 2),
            // Half the spread over the median: how far a quote sits from the
            // middle, as a percentage of the middle. 0.4% is a settled market;
            // 30% means nobody in that market agrees and the reader should know.
            'spreadPct' => $median > 0 ? round((($max - $min) / 2) / $median * 100, 1) : 0.0,
            'confidence' => self::confidence($count, $median > 0 ? ($max - $min) / $median : 1),
            'freshness' => self::freshness(self::latestDate($dates), $todayOffsetDays),
            'lastReport' => self::latestDate($dates),
        ];
    }

    /**
     * Aggregate quotes taken from several markets at once.
     *
     * Pooling every quote and measuring the spread afterwards answers the wrong
     * question. A farm gate and a retail shelf are supposed to sit 20% apart —
     * that is not a market disagreeing with itself, it is two different
     * markets. Pooled, every line on the board reads "disputed" all day and the
     * confidence pill stops meaning anything. So each market is measured on its
     * own terms first, and the board reports the middle of those middles with
     * the average of those disagreements.
     *
     * @param array<int,array{perKg:float,reportDate:string,reporterId:int,market:string}> $rows
     * @return array<string,mixed>
     */
    public static function acrossMarkets(array $rows, int $todayOffsetDays = 0): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $groups[(string)($row['market'] ?? 'unassigned')][] = $row;
        }

        $medians = [];
        $count = 0;
        $weightedMean = 0.0;
        $weightedSpread = 0.0;
        $min = null;
        $max = null;
        $dates = [];

        foreach ($groups as $group) {
            $one = self::aggregate($group, $todayOffsetDays);
            if ((int)$one['count'] === 0) {
                continue;
            }
            $weight = (int)$one['count'];
            $count += $weight;
            // Each market counts once however many reporters it has: the figure
            // a trader wants is where a typical market sits, not where the
            // busiest one does.
            $medians[] = (float)$one['median'];
            $weightedMean += (float)$one['mean'] * $weight;
            $weightedSpread += ((float)$one['spreadPct'] / 100 * 2) * $weight;
            $min = $min === null ? (float)$one['min'] : min($min, (float)$one['min']);
            $max = $max === null ? (float)$one['max'] : max($max, (float)$one['max']);
            if (!empty($one['lastReport'])) {
                $dates[] = (string)$one['lastReport'];
            }
        }

        if ($count === 0) {
            return self::aggregate([], $todayOffsetDays);
        }

        sort($medians);
        $median = self::median($medians);
        $spreadPct = $weightedSpread / $count * 100 / 2;

        return [
            'count' => $count,
            'median' => round($median, 2),
            'mean' => round($weightedMean / $count, 2),
            // The range stays pooled on purpose: it is the honest answer to
            // "what did anyone actually pay", and it is what a seller is
            // negotiating against when they walk in from a farm gate.
            'min' => round((float)$min, 2),
            'max' => round((float)$max, 2),
            'spreadPct' => round($spreadPct, 1),
            'confidence' => self::confidence($count, $median > 0 ? $spreadPct / 100 * 2 : 1),
            'freshness' => self::freshness(self::latestDate($dates), $todayOffsetDays),
            'lastReport' => self::latestDate($dates),
            'markets' => count($medians),
        ];
    }

    /**
     * Should crossing town for this commodity be bothered with?
     *
     * @param array<string,float> $rates
     * @param array{median:float,count:int} $from  the market the seller is standing in
     * @param array{median:float,count:int} $to    the market they are thinking of hauling to
     * @return array<string,mixed>
     */
    public static function haul(array $rates, array $from, array $to, float $distanceKm): array
    {
        $gapPerKg = (float)$to['median'] - (float)$from['median'];
        $capacity = max(100.0, (float)($rates['vehicle_capacity_kg'] ?? 1200));
        $spoilage = max(0.0, (float)($rates['spoilage_pct'] ?? 5)) / 100;

        // What one round trip costs, before a single kilogram is sold.
        $fuel = $distanceKm * 2 * max(0.0, (float)($rates['fuel_per_km'] ?? 210));
        $levies = max(0.0, (float)($rates['levy_flat'] ?? 1500)) +
            $distanceKm * max(0.0, (float)($rates['levy_per_km'] ?? 40));
        $porter = max(0.0, (float)($rates['porter_per_load'] ?? 4000));
        $tripCost = $fuel + $levies + $porter;

        // Spoilage is charged against the load, not the trip: what rots is
        // whatever fraction of the cargo you paid for, so the sellable weight
        // shrinks while the cost of reaching the market does not.
        $sellableKg = $capacity * (1 - $spoilage);
        $costPerKg = $sellableKg > 0 ? $tripCost / $sellableKg : 0.0;
        $netPerKg = $gapPerKg - $costPerKg;

        // The break-even load: how many kilograms must move before the gap
        // covers the trip. Below this the seller loses money by driving, which
        // is the mistake the paper notebook never tells them about.
        $breakEvenKg = $gapPerKg > 0 ? $tripCost / $gapPerKg : 0.0;

        return [
            'gapPerKg' => round($gapPerKg, 2),
            'tripCost' => round($tripCost, 2),
            'fuel' => round($fuel, 2),
            'levies' => round($levies, 2),
            'porter' => round($porter, 2),
            'costPerKg' => round($costPerKg, 2),
            'netPerKg' => round($netPerKg, 2),
            'breakEvenKg' => round($breakEvenKg, 1),
            'breakEvenWithinLoad' => $breakEvenKg > 0 && $breakEvenKg <= $capacity,
            'fullLoadProfit' => round($netPerKg * $sellableKg, 2),
            'distanceKm' => round($distanceKm, 1),
            'capacityKg' => round($capacity, 0),
            'sellableKg' => round($sellableKg, 0),
            // Two thin boards do not make a reliable gap. If either side is
            // guesswork the answer is a guess, and saying so is the feature.
            'trustworthy' => $from['count'] >= 3 && $to['count'] >= 3 && $gapPerKg != 0.0,
            'verdict' => self::verdict($netPerKg, $breakEvenKg, $capacity),
        ];
    }

    /** @param array<string,float> $rates */
    public static function rates(): array
    {
        return [
            'fuel_per_km' => 210, 'levy_flat' => 1500, 'levy_per_km' => 40,
            'porter_per_load' => 4000, 'spoilage_pct' => 5, 'vehicle_capacity_kg' => 1200,
        ];
    }

    /** @param array<int,float> $sorted ascending */
    private static function median(array $sorted): float
    {
        $n = count($sorted);
        $mid = intdiv($n, 2);
        return $n % 2 === 1 ? $sorted[$mid] : ($sorted[$mid - 1] + $sorted[$mid]) / 2;
    }

    /** @param array<string> $dates */
    private static function latestDate(array $dates): ?string
    {
        return $dates === [] ? null : max($dates);
    }

    /**
     * How much of the board to believe. A wide spread on three quotes is a
     * different kind of unknown than a tight spread on twelve.
     */
    private static function confidence(int $count, float $spreadRatio): string
    {
        if ($count < 3) {
            return 'thin';
        }
        if ($spreadRatio > 0.35) {
            return 'disputed';
        }
        return $count >= 8 ? 'solid' : 'fair';
    }

    private static function freshness(?string $lastReport, int $todayOffsetDays): string
    {
        if ($lastReport === null) {
            return 'unknown';
        }
        $age = (int)round((time() - $todayOffsetDays * 86400 - strtotime($lastReport)) / 86400);
        return match (true) {
            $age <= 1 => 'today',
            $age <= 3 => 'recent',
            $age <= 7 => 'ageing',
            default => 'stale',
        };
    }

    private static function verdict(float $netPerKg, float $breakEvenKg, float $capacity): string
    {
        if ($netPerKg <= 0) {
            return 'The gap does not survive the trip. Sell locally.';
        }
        if ($breakEvenKg > $capacity) {
            return sprintf('Worth %.0f kg of the %.0f kg the vehicle holds — a part load pays.', $breakEvenKg, $capacity);
        }
        return 'Any full load clears the trip cost. The gap is real money.';
    }

    /**
     * Units arrive typed by a human: "Crate", "crate of 30", "bag(50kg)".
     *
     * The container is the word the weights table knows, so the first
     * recognised word wins and a trailing "of 30" cannot hide it. A bare
     * weight ("50kg bag") is checked last, or a bag of rice would be read as
     * one kilogram.
     *
     * @param array<string,float> $weights
     */
    private static function unitKey(string $unit, array $weights): string
    {
        $words = preg_match_all('/[a-zA-Z]+/', $unit, $matches) === false ? []
            : array_map(static fn (string $w): string => mb_strtolower($w), $matches[0]);

        foreach ($words as $word) {
            if ($word !== 'kg' && $word !== 'g' && isset($weights[$word])) {
                return $word;
            }
        }
        foreach ($words as $word) {
            if (isset($weights[$word])) {
                return $word;
            }
        }
        return $words[0] ?? 'kg';
    }
}
