<?php

namespace App\Support;

use NumberFormatter;

/**
 * Number formatting that does not depend on the server's locale.
 *
 * `number_format()` takes its separators from the process locale when one is
 * set, overriding the arguments it was given. That made the interface say two
 * different things at once on the feed list: currency rendered `Rp16.500`
 * because the process happened to be running `id_ID`, while stock rendered
 * `2,400.00` because that call passed no separators and took the C default.
 * Change the server locale and the money column changes with it.
 *
 * Everything is formatted through here instead, so a number looks the same in
 * every column and does not move when the server is redeployed.
 */
final class Number
{
    /**
     * Format with a fixed number of decimals.
     *
     * Thousands separated with a dot and decimals with a comma, which is how
     * numbers are written in Indonesian.
     */
    public static function format(float|int|string|null $value, int $decimals = 0): string
    {
        $formatter = self::formatter();
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

        // The symbols are pinned per locale below, but a fallback keeps the
        // output sane if the intl data for a locale is missing.
        return $formatter->format((float) ($value ?? 0))
            ?: self::plain((float) ($value ?? 0), $decimals);
    }

    /**
     * Format, dropping the decimals when the value is whole.
     *
     * Stock is stored as a decimal so a half bag is possible, but printing
     * "2,400.00" for two thousand four hundred kilos adds nothing — and a column
     * of trailing zeroes makes real values like 380 harder to pick out.
     */
    public static function compact(float|int|string|null $value, int $maxDecimals = 2): string
    {
        $number = (float) ($value ?? 0);

        if ($number === floor($number)) {
            return self::format($number, 0);
        }

        // Two decimals is enough to tell 1.25 from 1.256, and more than that is
        // noise on a quantity.
        $formatter = self::formatter();
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);

        return $formatter->format($number) ?: self::plain($number, $maxDecimals);
    }

    /**
     * A quantity with its unit, e.g. "2.400 kg".
     *
     * The unit used to sit in a column of its own, so reading a stock figure
     * meant looking across the row. Naming the unit where the number is removes
     * that step and frees the column.
     */
    public static function quantity(float|int|string|null $value, string $unit): string
    {
        // One decimal is enough to tell a half bag from a whole one, and two
        // places false precision on a stock figure: "12,50 kg" of feed reads as
        // more exact than the ledger actually is.
        return trim(self::compact($value, 1).' '.self::unit($unit));
    }

    /**
     * Expand a stored unit code to the short label shown in the interface.
     */
    public static function unit(string $unit): string
    {
        return match (strtolower($unit)) {
            'kg', 'kilogram' => 'kg',
            'liter', 'l', 'litre' => 'L',
            'sack', 'sak', 'bag' => 'sak',
            'head' => 'ekor',
            'box', 'kotak' => 'kotak',
            'bundle', 'ikatan' => 'ikatan',
            'pcs', 'buah' => 'buah',
            'ton', 'tonne' => 'ton',
            default => $unit,
        };
    }

    private static function formatter(): NumberFormatter
    {
        $locale = self::locale();

        return new NumberFormatter($locale, NumberFormatter::DECIMAL);
    }

    private static function plain(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', ',');
    }

    /**
     * The locale the interface is currently in, as an ICU identifier.
     */
    private static function locale(): string
    {
        return app()->getLocale() === 'id' ? 'id_ID' : 'en_US';
    }
}
