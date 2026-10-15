<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The refinements made to stop the interface reading as flat and as a desktop
 * layout shrunk to phone width.
 *
 * Each of these is a defect that was measured rather than assumed, and the
 * measurement is kept here because every one of them is easy to reintroduce by
 * a well-meaning edit. A test that only asserted the new markup would pass
 * happily if the thing being protected was quietly removed again.
 */
class VisualDesignTest extends TestCase
{
    use RefreshDatabase;

    private string $css = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = file_get_contents(resource_path('css/app.css'));

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    public function test_the_neutrals_are_warmed_without_moving_their_lightness(): void
    {
        // The lightness values are the contract: contrast is computed from
        // lightness, so keeping them identical means this change cannot have
        // moved a single contrast ratio. The chroma is what gives the surfaces
        // a colour temperature instead of reading as a lightbox.
        $light = $this->themeBlock('light');

        foreach (['base-100', 'base-200', 'base-300'] as $surface) {
            [$lightness, $chroma] = $this->chromaOf($light, $surface);

            $this->assertGreaterThan(
                0,
                $chroma,
                "{$surface} is back to zero chroma, which is what made the light theme look clinical.",
            );

            // Deliberately faint. A visible tint starts to read as a coloured
            // theme; a few thousandths reads as paper.
            $this->assertLessThanOrEqual(0.02, $chroma, "{$surface} is tinted enough to read as a colour.");

            // The lightness is what the contrast ratios were computed against,
            // so it is what must not have moved.
            $this->assertContains(
                $lightness,
                ['100', '98', '95'],
                "{$surface} changed lightness, which can move every contrast ratio on the page.",
            );
        }
    }

    public function test_the_dark_theme_warms_its_surfaces_too(): void
    {
        // The dark palette was left achromatic while the light one was warmed.
        // Half a change reads as a bug rather than as a decision.
        $this->assertMatchesRegularExpression(
            '/--color-base-200: oklch\(\d+(?:\.\d+)?% 0\.00\d+ \d+\)/',
            $this->themeBlock('black'),
            'The dark surfaces carry no chroma at all.',
        );
    }

    public function test_elevated_surfaces_have_a_shadow_rather_than_only_a_border(): void
    {
        // Everything used to be a 1px border on a flat plane, which is why the
        // pages read as one undifferentiated sheet.
        foreach (['surface', 'raised', 'overlay'] as $step) {
            $this->assertStringContainsString(
                "--shadow-{$step}:",
                $this->css,
                "The {$step} elevation step is missing from the theme tokens.",
            );
        }

        $this->assertMatchesRegularExpression('/\.surface-raised\s*\{[^}]*box-shadow:\s*var\(--shadow-surface\)/s', $this->css);
    }

    public function test_the_drawer_cannot_be_wider_than_the_screen(): void
    {
        // The drawer is 16rem of content, which is wider than a 320px phone, and
        // that alone put the whole page into horizontal scroll: 7px of overflow
        // at 320px, 3px at 375px. The constraint has to be on the `aside` and not
        // only on the inner panel, because the aside is what sizes the overlay.
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString(
            'class="drawer-side z-40 max-w-[86vw] lg:max-w-none"',
            $layout,
            'The sidebar may be wider than a narrow viewport again.',
        );
    }

    public function test_the_chart_does_not_take_the_page_into_horizontal_scroll(): void
    {
        // DaisyUI hides a tooltip with opacity, not by removing it, so the tip
        // is laid out at all times. Seven bars across a 320px screen left each
        // bar 32px wide and a tip needing 66px, hanging past the chart on both
        // sides. Plain `overflow: hidden` would fix the scroll and clip the
        // tooltip, so the margin has to be there too.
        $chart = file_get_contents(resource_path('views/components/bar-chart.blade.php'));

        $this->assertStringContainsString('overflow-clip', $chart);
        $this->assertStringContainsString('overflow-clip-margin', $chart);
        $this->assertStringNotContainsString('overflow-hidden', $chart, 'This clips the tooltip when it does appear.');
    }

    public function test_the_mobile_card_list_replaces_the_table_without_dropping_data(): void
    {
        foreach ([
            'animals.index' => 'Livestock list',
            'invoices.index' => 'Invoice list',
            'barns.index' => 'Barn list',
        ] as $route => $label) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString('record-card', $html, "{$route} has no phone presentation.");
            $this->assertStringContainsString('lg:hidden', $html);

            // The table stays in the document, hidden only by a breakpoint.
            // Deleting it would remove the desktop layout; leaving it visible
            // everywhere would put a ten-column table back on a phone.
            $this->assertStringContainsString('hidden lg:block', $html, "{$route} hides its table unconditionally.");
            $this->assertStringContainsString('table app-table', $html);
        }
    }

    public function test_the_card_list_keeps_every_row_the_table_had(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        preg_match_all('/class="record-card[^\"]*"/', $html, $cards);
        preg_match_all('/<tr>\s*<td class="whitespace-nowrap">\s*<a href="[^"]*animals/', $html, $rows);

        $this->assertNotEmpty($cards[0]);
        $this->assertCount(
            count($cards[0]),
            $rows[0],
            'The phone list and the table disagree about how many records there are.',
        );
    }

    public function test_a_card_is_tappable_across_its_whole_area(): void
    {
        $card = file_get_contents(resource_path('views/components/record-card.blade.php'));

        // Only linking the code leaves a 19px target inside a list that is
        // otherwise 88px tall; the card beside it is the obvious thing to press.
        $this->assertStringContainsString('absolute inset-0', $card);

        // And the action has to stay pressable through that overlay.
        $this->assertStringContainsString('z-10', $card);
    }

    public function test_pagination_does_not_reserve_a_strip_it_has_nothing_to_put_in(): void
    {
        // Written inline as a bare bordered div, the wrapper still took its own
        // height on a single page of results: an empty ~40px band under the last
        // row, inside the panel, that looked like a half-drawn table row.
        $component = file_get_contents(resource_path('views/components/pagination.blade.php'));

        $this->assertStringContainsString('hasPages()', $component);

        // A second navigation landmark would be wrong: the paginator view
        // already renders one, so the wrapper supplies only the rule and the
        // padding. Asserted on rendered markup, not on the source — the
        // component's own comment names a `<nav>` in order to explain this.
        $withPages = $this->get(route('animals.index'))->assertOk()->getContent();
        $this->assertSame(
            1,
            substr_count($withPages, 'aria-label="'.__('Pagination Navigation').'"'),
            'The page should carry exactly one pagination landmark.',
        );

        // Five barns fit on one page, so nothing should be rendered at all.
        $this->assertStringNotContainsString('Pagination', $this->get(route('barns.index'))->assertOk()->getContent());
    }

    public function test_the_dashboard_puts_the_summary_before_the_alert_list_on_a_phone(): void
    {
        $view = file_get_contents(resource_path('views/dashboard/index.blade.php'));

        // Measured: the operational summary sat 1024px to 1245px down a phone
        // page, behind ten alert rows, so a manager opening the application saw
        // no figures at all. On a desktop the alerts still come first.
        $this->assertMatchesRegularExpression('/class="order-1[^"]*"/', $view, 'The summary is no longer first on a phone.');
        $this->assertMatchesRegularExpression('/class="order-2[^"]*lg:order-1/', $view, 'The alert list no longer leads on a desktop.');
    }

    public function test_alert_descriptions_are_clamped_so_the_grid_does_not_ragged(): void
    {
        $row = file_get_contents(resource_path('views/components/alert-row.blade.php'));

        // Unclamped, one row ran to four lines beside a neighbour at two and the
        // three-column grid left ragged gaps down the dashboard.
        $this->assertStringContainsString('line-clamp-2', $row);

        // Every row links to the record it is about, so the clamp hides detail
        // and not an action.
        $this->assertStringContainsString('$url', $row);
    }

    public function test_the_page_title_steps_down_on_a_phone(): void
    {
        // At 24px a long title wrapped onto two lines and, with its subtitle,
        // took close to 280px of a 375px screen — a quarter of the viewport spent
        // restating where you already are.
        $this->assertMatchesRegularExpression(
            '/\.page-title\s*\{[^}]*@apply text-xl[^}]*lg:text-2xl/s',
            $this->css,
        );
    }

    public function test_shared_components_accept_attributes(): void
    {
        // x-panel and x-stat merge attributes; x-section-title did not, which
        // forced callers to duplicate markup to adjust a margin or hide a
        // heading at a breakpoint.
        foreach (['section-title', 'page-header', 'stat', 'alert-row'] as $component) {
            $source = file_get_contents(resource_path("views/components/{$component}.blade.php"));

            $this->assertStringContainsString(
                '$attributes',
                $source,
                "x-{$component} cannot be adjusted by its caller.",
            );
        }
    }

    public function test_a_dialog_is_framed_on_a_short_phone(): void
    {
        // DaisyUI centres the box in a full-height grid, so a dialog taller than
        // the screen measured exactly the viewport: 667px top to bottom on a
        // 375×667 phone, touching both edges and reading as a page rather than a
        // dialog.
        $this->assertMatchesRegularExpression(
            '/@media \(width < 40rem\)\s*\{\s*\.modal-box\s*\{[^}]*max-height:\s*calc\(100dvh - 2rem\)/s',
            $this->css,
        );
    }

    public function test_no_view_renders_a_bare_pagination_wrapper(): void
    {
        // The fix is only real if every list uses the component; one missed call
        // site puts the empty strip back on that page.
        $strays = [];

        foreach (glob(resource_path('views/*/**/*.blade.php')) ?: [] as $file) {
            if (str_contains($file, '/components/')) {
                continue;
            }

            if (str_contains(file_get_contents($file), '->links() }}</div>')) {
                $strays[] = basename($file);
            }
        }

        $this->assertSame([], $strays, 'These views still render their own empty pagination wrapper.');
    }

    /**
     * The lightness and chroma of one of the theme's surfaces.
     *
     * `oklch(100% 0 0)` is achromatic white and `oklch(96.8% 0.009 78)` is the
     * same white with a trace of warmth. Reading the two numbers out is the
     * only way to tell them apart in a stylesheet.
     *
     * @return array{0: string, 1: float}
     */
    private function chromaOf(string $block, string $surface): array
    {
        $this->assertMatchesRegularExpression(
            '/--color-'.$surface.': oklch\(([\d.]+)% ([\d.]+)/',
            $block,
            "No surface token found for {$surface}.",
        );

        preg_match('/--color-'.$surface.': oklch\(([\d.]+)% ([\d.]+)/', $block, $m);

        return [$m[1], (float) $m[2]];
    }

    /**
     * One theme's block of custom properties out of the two the stylesheet
     * defines, so a rule about the light palette cannot pass on the dark one.
     */
    private function themeBlock(string $theme): string
    {
        $marker = "[data-theme={$theme}])";

        $start = strpos($this->css, $marker);

        $this->assertNotFalse($start, "No block found for the {$theme} theme.");

        $end = strpos($this->css, '}', $start);

        return substr($this->css, $start, $end - $start);
    }
}
