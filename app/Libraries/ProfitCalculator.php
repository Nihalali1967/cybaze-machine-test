<?php

namespace App\Libraries;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Pure profit mathematics - no database, no framework state.
 *
 * Keeping this separate from the processor is deliberate: the tricky parts of
 * this domain (proration, month-end clamping, period keys) can then be unit
 * tested directly against the spec's own numbers.
 */
class ProfitCalculator
{
    public const BASIS_ANNUAL     = 'annual';
    public const BASIS_PER_PERIOD = 'per_period';

    /**
     * Payout periods in a year, used to prorate the annual return percentage.
     */
    public const PERIODS_PER_YEAR = [
        'weekly'    => 52,
        'monthly'   => 12,
        'quarterly' => 4,
        'yearly'    => 1,
    ];

    /**
     * These advance by whole months, so they need end-of-month clamping.
     */
    private const MONTH_BASED = ['monthly', 'quarterly', 'yearly'];

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::PERIODS_PER_YEAR);
    }

    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::PERIODS_PER_YEAR);
    }

    public static function periodsPerYear(string $type): int
    {
        if (! self::isValidType($type)) {
            throw new InvalidArgumentException('Unknown withdrawal type: ' . $type);
        }

        return self::PERIODS_PER_YEAR[$type];
    }

    /**
     * Profit earned during a single payout period.
     *
     * With the default annual basis:
     *   ₹50,000 @ 12% monthly   => 50000 * 12 / 100 / 12 = ₹500.00
     *   ₹25,000 @ 15% monthly   => 25000 * 15 / 100 / 12 = ₹312.50
     *   ₹50,000 @ 20% quarterly => 50000 * 20 / 100 / 4  = ₹2,500.00
     *   ₹1,00,000 @ 25% yearly  => 100000 * 25 / 100 / 1 = ₹25,000.00
     */
    public static function periodProfit(
        float $amount,
        float $annualRate,
        string $type,
        string $basis = self::BASIS_ANNUAL,
    ): float {
        if (! self::isValidType($type)) {
            throw new InvalidArgumentException('Unknown withdrawal type: ' . $type);
        }

        $perPeriod = $basis === self::BASIS_PER_PERIOD
            ? $amount * $annualRate / 100
            : $amount * $annualRate / 100 / self::PERIODS_PER_YEAR[$type];

        return round($perPeriod, 2);
    }

    /**
     * Reporting label for the period a payout falls in.
     *
     * weekly    => 2026-W39
     * monthly   => 2026-09
     * quarterly => 2026-Q3
     * yearly    => 2026
     */
    public static function periodKey(string $type, string $date): string
    {
        $day = new DateTimeImmutable($date);

        return match ($type) {
            'weekly'    => $day->format('o-\WW'),
            'monthly'   => $day->format('Y-m'),
            'quarterly' => $day->format('Y') . '-Q' . (int) ceil(((int) $day->format('n')) / 3),
            'yearly'    => $day->format('Y'),
            default     => throw new InvalidArgumentException('Unknown withdrawal type: ' . $type),
        };
    }

    /**
     * Advance one payout period, clamping to the end of the target month.
     *
     * Naive month arithmetic turns 31 Jan into 3 Mar; an investor should be
     * paid on 28/29 Feb instead, so:
     *   2026-01-31 + 1 month   => 2026-02-28
     *   2025-01-31 + 3 months  => 2025-04-30
     *   2024-02-29 + 1 year    => 2025-02-28
     */
    public static function nextDate(string $type, string $from): string
    {
        if (! self::isValidType($type)) {
            throw new InvalidArgumentException('Unknown withdrawal type: ' . $type);
        }

        $day = new DateTimeImmutable($from);

        if ($type === 'weekly') {
            return $day->modify('+7 days')->format('Y-m-d');
        }

        $target = match ($type) {
            'monthly'   => $day->modify('+1 month'),
            'quarterly' => $day->modify('+3 months'),
            'yearly'    => $day->modify('+1 year'),
        };

        // A changed day-of-month means PHP overflowed into the following month.
        if ((int) $target->format('j') !== (int) $day->format('j')) {
            $target = $target->modify('first day of this month')->modify('-1 day');
        }

        return $target->format('Y-m-d');
    }

    /**
     * Months between payouts, handy for UI copy ("paid every 3 months").
     */
    public static function monthsPerPeriod(string $type): int
    {
        return match ($type) {
            'weekly'    => 0,
            'monthly'   => 1,
            'quarterly' => 3,
            'yearly'    => 12,
            default     => throw new InvalidArgumentException('Unknown withdrawal type: ' . $type),
        };
    }
}
