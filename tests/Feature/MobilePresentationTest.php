<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The phone presentation is an enhancement, not a second application.
 *
 * Money is still rendered by the server in the same notation it always used;
 * the compact form is a mobile-only view of that figure. Tables are still
 * tables in the markup; the card layout is applied only after the client has
 * copied the translated headers onto their cells. This class locks those two
 * contracts down without duplicating the browser checks.
 */
class MobilePresentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->setLocale('id');
    }

    public function test_money_has_a_short_phone_form_and_a_full_value_beside_it(): void
    {
        $html = Blade::render('<x-money :amount="8946541" />');

        $this->assertStringContainsString('Rp8.946.541', $html);
        $this->assertStringContainsString('Rp8,9 jt', $html);
        $this->assertStringContainsString('sm:hidden', $html);
        $this->assertStringContainsString('hidden sm:inline', $html);
        $this->assertStringContainsString('whitespace-nowrap', $html);
        $this->assertStringContainsString('title="Rp8.946.541"', $html);
        $this->assertStringContainsString('class="sr-only"', $html);
    }

    public function test_money_below_a_million_is_not_given_a_misleading_unit(): void
    {
        $html = Blade::render('<x-money :amount="750000" />');

        $this->assertStringContainsString('Rp750.000', $html);
        $this->assertStringNotContainsString('Rp750 rb', $html);
    }

    public function test_currency_stats_carry_the_short_form_and_the_full_accessible_value(): void
    {
        $html = Blade::render('<x-stat label="Total" value="Rp8.946.541" />');

        $this->assertStringContainsString('data-compact-value="Rp8,9 jt"', $html);
        $this->assertStringContainsString('aria-label="Rp8.946.541"', $html);
        $this->assertStringContainsString('title="Rp8.946.541"', $html);
        $this->assertStringContainsString('Rp8.946.541', $html);
    }

    public function test_non_currency_stats_are_left_alone(): void
    {
        $html = Blade::render('<x-stat label="Animals" :value="47" />');

        $this->assertStringNotContainsString('data-compact-value', $html);
        $this->assertStringNotContainsString('aria-label=', $html);
    }

    public function test_table_cards_are_progressive_enhancement(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('function initResponsiveTables', $js);
        $this->assertStringContainsString("table.setAttribute('data-mobile-cards', '')", $js);
        $this->assertStringContainsString("table.closest('.table-scroll')?.setAttribute('data-mobile-cards', '')", $js);
        $this->assertStringContainsString('initResponsiveTables(content);', $js);
        $this->assertStringContainsString('applyStatValues(content);', $js);

        // Editable line-item forms keep their table layout and horizontal
        // scroll; only read-only data tables become cards.
        $this->assertStringContainsString("table.closest('[data-line-editor]')", $js);
        $this->assertStringContainsString('tbody input, tbody select, tbody textarea', $js);

        $this->assertMatchesRegularExpression('/@media screen and \(width < 64rem\)/', $css);
        $this->assertStringContainsString('.table-scroll[data-mobile-cards]', $css);
        $this->assertStringContainsString('table[data-mobile-cards] > thead', $css);
        $this->assertStringContainsString('content: attr(data-label)', $css);
        $this->assertStringContainsString('overflow-x: visible', $css);

        // The attribute is the switch. A browser without JavaScript never adds
        // it, so the normal table and its scroll container remain available.
        $this->assertStringContainsString('table[data-mobile-cards]', $css);
    }

    public function test_the_light_theme_logo_is_opaque_black_not_grey(): void
    {
        $path = public_path('images/logo-mark-black.png');

        $this->assertFileExists($path);

        if (! function_exists('imagecreatefrompng')) {
            $this->markTestSkipped('GD is needed to inspect the logo alpha channel.');
        }

        $image = imagecreatefrompng($path);
        $this->assertNotFalse($image, 'The light-theme logo is not a readable PNG.');

        $hasOpaqueBlack = false;

        for ($x = 0; $x < imagesx($image); $x++) {
            for ($y = 0; $y < imagesy($image); $y++) {
                $colour = imagecolorat($image, $x, $y);
                $alpha = ($colour >> 24) & 0x7F;

                // GD uses 0 for opaque and 127 for transparent.
                if ($alpha === 0 && ($colour & 0xFFFFFF) === 0) {
                    $hasOpaqueBlack = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($hasOpaqueBlack, 'The light-theme logo still has no fully opaque black pixel.');

        $css = file_get_contents(resource_path('css/app.css'));
        $app = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $guest = file_get_contents(resource_path('views/layouts/guest.blade.php'));

        $this->assertStringContainsString("url('/images/logo-mark-black.png')", $css);
        $this->assertStringContainsString("url('/images/logo-mark-light.png')", $css);
        $this->assertStringContainsString('images/logo-mark-black.png', $app);
        $this->assertStringContainsString('images/logo-mark-black.png', $guest);
    }
}
