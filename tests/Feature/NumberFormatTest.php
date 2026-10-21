<?php

namespace Tests\Feature;

use App\Models\FeedItem;
use App\Support\Number;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Numbers are written the same way everywhere, in the language in use.
 *
 * `number_format()` takes its separators from the process locale when one is
 * set, overriding the arguments it was given. That is why one feed row could
 * carry two conventions at once: currency rendered `Rp16.500` because the
 * server happened to run `id_ID`, while stock rendered `2,400.00` because that
 * call passed no separators and took the C default. Redeploy the application
 * under a different locale and the money column changes with it.
 *
 * Every figure now goes through `Number`, which pins the convention instead of
 * inheriting it.
 */
class NumberFormatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{0: float|int, 1: string}>
     */
    public static function indonesianFigures(): array
    {
        return [
            [2400, '2.400'],
            [380, '380'],
            [95, '95'],
            [11220, '11.220'],
            [1234567, '1.234.567'],
            [0, '0'],
            [-1500, '-1.500'],
        ];
    }

    public function test_whole_numbers_carry_no_decimal_padding(): void
    {
        $cases = self::indonesianFigures();

        // "2,400.00" said nothing that "2.400" did not, and the trailing
        // zeroes made a genuine 380 hard to pick out in the column.
        foreach ($cases as [$value, $expected]) {
            $this->assertSame($expected, Number::compact($value));
        }
    }

    public function test_decimals_use_a_comma_and_only_when_meaningful(): void
    {
        $this->assertSame('12,5', Number::compact(12.5, 1));
        $this->assertSame('0,75', Number::compact(0.75, 2));
        $this->assertSame('380', Number::compact(380.0, 2), 'A whole number keeps no decimals.');
    }

    public function test_a_quantity_names_its_unit_where_the_number_is(): void
    {
        $this->assertSame('2.400 kg', Number::quantity(2400, 'kg'));
        $this->assertSame('380 L', Number::quantity(380, 'liter'));
        $this->assertSame('40 sak', Number::quantity(40, 'sack'));
        $this->assertSame('12,5 ekor', Number::quantity(12.5, 'head'));
        $this->assertSame('38,8 sak', Number::quantity(38.8, 'sack'));
    }

    public function test_the_convention_survives_a_different_server_locale(): void
    {
        // The whole point of not inheriting the process locale.
        $original = setlocale(LC_NUMERIC, '0');
        setlocale(LC_NUMERIC, 'C');

        try {
            $this->assertSame('2.400', Number::compact(2400));
            $this->assertSame('11.220', Number::compact(11220));
        } finally {
            setlocale(LC_NUMERIC, $original === false ? 'C' : $original);
        }
    }

    /**
     * The hosting account had no ext-intl.
     *
     * The first version of `Number` went through `NumberFormatter` and passed
     * every test locally, then 500'd on every stock page in production with
     * `Class "NumberFormatter" not found`. A formatter the interface depends
     * on cannot assume an extension the host may not have.
     */
    public function test_it_does_not_depend_on_the_intl_extension(): void
    {
        $source = file_get_contents(app_path('Support/Number.php'));

        // Look for actual use, not the word: the docblock explains in prose
        // why the class is not used, and that comment is allowed to name it.
        $code = (string) preg_replace(['#/\*.*?\*/#s', '#//[^\n]*#'], '', $source);

        $this->assertStringNotContainsString('NumberFormatter', $code);
        $this->assertStringNotContainsString('Intl', $code);

        // The behaviour must not depend on whether intl happens to be loaded.
        $this->assertSame('2.400', Number::compact(2400));
        $this->assertSame('11.220', Number::compact(11220));
        $this->assertSame('1.234.567,89', Number::format(1234567.891, 2));
    }

    public function test_a_rounded_value_does_not_keep_a_stray_decimal_separator(): void
    {
        // Rounding 1.9999 up must not leave "2," behind.
        $this->assertSame('2', Number::compact(1.9999));
        $this->assertSame('0,75', Number::compact(0.75));
        $this->assertSame('-1.500', Number::compact(-1500));
    }

    public function test_english_switches_to_its_own_convention(): void
    {
        $this->app->setLocale('en');

        $this->assertSame('2,400', Number::compact(2400));
        $this->assertSame('12.5', Number::compact(12.5, 1));
    }

    public function test_a_phone_gets_a_short_rupiah_figure_without_losing_the_exact_one(): void
    {
        // The full figure stays on desktop; these are the forms a 320px tile
        // can show without clipping.
        $this->assertSame('Rp8,9 jt', Number::compactMoney(8_946_541));
        $this->assertSame('Rp1,0 jt', Number::compactMoney(1_000_000));
        $this->assertSame('Rp1,3 jt', Number::compactMoney(1_250_000));
        $this->assertSame('Rp16,7 jt', Number::compactMoney(16_700_000));
        $this->assertSame('-Rp33,2 jt', Number::compactMoney(-33_232_324));
        $this->assertSame('Rp1,2 M', Number::compactMoney(1_200_000_000));
        $this->assertSame('Rp1,2 T', Number::compactMoney(1_200_000_000_000));

        // The unit is chosen after rounding, so a figure just below a boundary
        // does not render as "Rp1.000,0 jt" and overflow the tile.
        $this->assertSame('Rp1,0 M', Number::compactMoney(999_950_000));
        $this->assertSame('Rp1,0 T', Number::compactMoney(999_950_000_000));

        // Below a million the grouping is already short enough; changing the
        // unit there would cost precision without saving meaningful width.
        $this->assertSame('Rp999', Number::compactMoney(999));
        $this->assertSame('Rp1.500', Number::compactMoney(1_500));
        $this->assertSame('Rp999.000', Number::compactMoney(999_000));
        $this->assertSame('Rp0', Number::compactMoney(0));
    }

    public function test_the_short_rupiah_figure_also_follows_the_active_locale(): void
    {
        $this->app->setLocale('en');

        $this->assertSame('Rp8.9 jt', Number::compactMoney(8_946_541));
        $this->assertSame('Rp1,500', Number::compactMoney(1_500));
    }

    public function test_the_feed_list_shows_one_convention_throughout(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = $this->actingAs($this->owner())
            ->get(route('feed-items.index'))
            ->assertOk()
            ->getContent();

        // The old stock column: padded, US-separated.
        $this->assertStringNotContainsString('2,400.00', $html);
        $this->assertDoesNotMatchRegularExpression('/\d,\d{3}\.\d{2}/', $html);

        // And the unit now sits with the figure instead of in its own column.
        $this->assertStringNotContainsString('<th>Satuan</th>', $html);
        $this->assertMatchesRegularExpression('/2\.400\s*<span[^>]*>kg<\/span>/', $html);
    }

    public function test_the_unit_column_is_gone_but_the_unit_is_still_readable(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = $this->actingAs($this->owner())
            ->get(route('feed-items.index'))
            ->assertOk()
            ->getContent();

        preg_match_all('/<th[^>]*>(.*?)<\/th>/s', $html, $headers);
        $headers = array_map(fn ($h) => trim(strip_tags($h)), $headers[1]);

        $this->assertNotContains('Satuan', $headers);
        $this->assertContains('Stok', $headers);

        // Reading a stock figure no longer means looking across the row.
        foreach (['kg', 'sak', 'L'] as $unit) {
            $this->assertStringContainsString($unit, $html);
        }
    }

    public function test_stock_and_money_agree_on_the_separator(): void
    {
        $this->seed(DatabaseSeeder::class);

        $item = FeedItem::where('code', 'FD-DDK01')->firstOrFail();

        $html = $this->actingAs($this->owner())
            ->get(route('feed-items.index'))
            ->assertOk()
            ->getContent();

        // Price 6.200 and stock 2.500, both with a dot as thousands separator.
        $this->assertStringContainsString('Rp6.200', $html);
        $this->assertMatchesRegularExpression('/2\.500\s*<span[^>]*>kg<\/span>/', $html);
        $this->assertGreaterThan(0, $item->currentStock());
    }
}
