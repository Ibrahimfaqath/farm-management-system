<?php

namespace App\Support;

/**
 * Number formatting that does not depend on the server's locale or extensions.
 *
 * `number_format()` takes its separators from the process locale when one is
 * set, overriding the arguments it was given. That made the interface say two
 * different things at once on the feed list: currency rendered `Rp16.500`
 * because the server happened to run `id_ID`, while stock rendered `2,400.00`
 * because that call passed none and took the C default. Change the server
 * locale and the money column moves with it.
 *
 * The grouping is done by hand rather than through `NumberFormatter`, because
 * the hosting account did not have ext-intl installed: the first version of
 * this class shipped assuming it, and every stock page 500'd in production
 * while passing locally. A formatter that the interface depends on cannot be
 * allowed to depend on something the host may not have.
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
        return self::render((float) ($value ?? 0), $decimals);
    }

    /**
     * A rupiah figure shortened to the largest useful Indonesian unit.
     *
     * A phone tile is roughly half a desktop tile wide. "Rp8.946.541" is nine
     * characters of digits before the reader gets to the label beside it, and
     * at 320px it was being clipped. "Rp8,9 jt" says the same thing at a
     * glance; the exact figure remains available as the component's title and
     * accessible label. Figures below one million stay whole, where they fit
     * and where a "rb" suffix would save only a couple of characters.
     */
    public static function compactMoney(float|int|string|null $value): string
    {
        $number = (float) ($value ?? 0);
        $absolute = abs($number);

        if ($absolute < 1_000_000) {
            return 'Rp'.self::format($number, 0);
        }

        // Pick the unit after rounding. Without this, 999.950.000 would render
        // as "Rp1.000,0 jt" instead of the shorter, correctly rounded "Rp1,0 M".
        foreach ([[1_000_000_000_000, 'T'], [1_000_000_000, 'M'], [1_000_000, 'jt']] as [$divisor, $suffix]) {
            $scaled = round($absolute / $divisor, 1);

            if ($scaled < 1 || $scaled >= 1000) {
                continue;
            }

            // Keep one decimal even on a whole million. It makes the short form
            // consistent ("Rp1,0 jt", not "Rp1 jt") and gives the count-up
            // animation a decimal to move through; a bare "Rp1 jt" was read as a
            // sub-thousand figure and skipped by the counter. The formatting helper
            // still supplies the active locale's comma or dot.
            return ($number < 0 ? '-' : '').'Rp'.self::format($scaled, 1).' '.$suffix;
        }

        return 'Rp'.self::format($number, 0);
    }

    /**
     * Format, dropping the decimals when the value is whole.
     *
     * Stock is stored as a decimal so a half bag is possible, but printing
     * "2.400,00" for two thousand four hundred kilos adds nothing — and a column
     * of trailing zeroes makes a real value like 380 harder to pick out.
     */
    public static function compact(float|int|string|null $value, int $maxDecimals = 2): string
    {
        $number = (float) ($value ?? 0);

        if ($number === floor($number)) {
            return self::render($number, 0);
        }

        $rendered = self::render($number, $maxDecimals);

        // `render` rounds, so 1.9999 can come back as "2" with a stray comma
        // behind it. Anything with no significant decimals drops them.
        return str_ends_with($rendered, '0') && str_contains($rendered, self::decimalSeparator())
            ? rtrim(rtrim($rendered, '0'), self::decimalSeparator())
            : $rendered;
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
        // places false precision on a stock figure.
        return trim(self::compact($value, 1).' '.self::unit($unit));
    }

    /**
     * A stock-coverage estimate in words a keeper can scan.
     *
     * Feed inventory is seasonal, so "3.970 hari" is mathematically correct
     * but useless on a warehouse screen: it is longer than the feed's shelf
     * life and longer than the planning horizon. Once coverage passes a
     * quarter, the useful answer is that there is more than three months of
     * stock, not an exact day count nobody will act on.
     */
    public static function durationDays(float|int|null $days): string
    {
        if ($days === null) {
            return '—';
        }

        $days = max(0, (int) floor($days));

        if ($days === 0) {
            return '< 1 '.__('days');
        }

        if ($days > 90) {
            return '> 3 '.__('months');
        }

        return self::format($days).' '.__('days');
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

    /**
     * Group the digits and join the parts, without touching the process locale.
     */
    private static function render(float $number, int $decimals): string
    {
        $negative = $number < 0;
        $rounded = round(abs($number), $decimals);

        $fixed = number_format($rounded, $decimals, '.', '');
        [$whole, $fraction] = array_pad(explode('.', $fixed, 2), 2, '');

        $grouped = self::group($whole);
        $joined = $decimals > 0 && $fraction !== ''
            ? $grouped.self::decimalSeparator().$fraction
            : $grouped;

        return $negative && (float) $fixed !== 0.0 ? '-'.$joined : $joined;
    }

    /**
     * Insert thousands separators into a run of digits, right to left.
     */
    private static function group(string $digits): string
    {
        $separator = self::thousandsSeparator();
        $length = strlen($digits);

        if ($length <= 3) {
            return $digits;
        }

        $chunks = [];

        for ($end = $length; $end > 3; $end -= 3) {
            array_unshift($chunks, substr($digits, $end - 3, 3));
        }

        array_unshift($chunks, substr($digits, 0, $end));

        return implode($separator, $chunks);
    }

    private static function thousandsSeparator(): string
    {
        return app()->getLocale() === 'id' ? '.' : ',';
    }

    private static function decimalSeparator(): string
    {
        return app()->getLocale() === 'id' ? ',' : '.';
    }
}
