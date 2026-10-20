<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\AttentionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The header controls: notification bell, theme switch, account menu.
 *
 * The one that matters most here is the first. The bell was rendered from the
 * layout but fed only by `DashboardController`, and `@extends('layouts.app',
 * ['title' => …])` hands the layout that array rather than the child view's
 * data — so the list never arrived. A `?? collect()` fallback turned that into
 * a bell that said "all clear" and counted zero on every page in the
 * application, including the dashboard. It looked healthy the entire time,
 * which is why nothing caught it: a notification that is quietly dead is the
 * worst kind.
 */
class HeaderControlsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    /**
     * A page that is not the dashboard, so a passing test cannot be explained
     * by the controller that used to pass the list down.
     */
    public function test_the_bell_reports_items_on_a_page_that_never_asked_for_them(): void
    {
        $this->overdueInvoice();

        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        $this->assertStringContainsString('badge badge-error badge-xs', $html, 'The bell carries no count.');

        // Not a fixed number: the seeder's own data contributes attention items,
        // so the only stable claim is that the bell's count and the count in the
        // panel's own header agree with each other.
        $onBell = $this->bellCount($html);
        $inPanel = $this->panelCount($html);

        $this->assertGreaterThan(0, $onBell, 'The overdue invoice should put something on the bell.');
        $this->assertSame($inPanel, $onBell, 'The bell and the panel header must agree on how many there are.');

        // And it must not be the "nothing to do" panel.
        $this->assertStringNotContainsString(__('All clear'), $html);
    }

    public function test_the_bell_is_populated_on_every_page_that_uses_the_layout(): void
    {
        $this->overdueInvoice();

        foreach (['animals.index', 'invoices.index', 'milk-productions.index', 'purchase-orders.index'] as $route) {
            if (! Route::has($route)) {
                $this->markTestSkipped("{$route} does not exist.");
            }

            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString(
                'badge badge-error badge-xs',
                $html,
                "The bell is empty on {$route}. It is part of the header, so it has to work everywhere.",
            );
        }
    }

    public function test_the_bell_says_so_when_there_is_nothing(): void
    {
        // Reachable without arranging a database with nothing wrong in it, and
        // without depending on what the seeder happens to produce.
        // Rendered through the component tag rather than by naming the view:
        // an anonymous component needs `$attributes`, which only the tag supplies.
        $html = Blade::render(
            '<x-global-alerts :attention-items="$empty" />',
            ['empty' => collect()],
        );

        $this->assertStringContainsString(__('All clear'), $html);
        $this->assertStringContainsString(__('Nothing needs follow-up right now.'), $html);
        $this->assertStringNotContainsString('badge badge-error badge-xs', $html);
        $this->assertStringContainsString('aria-label="'.__('No items need attention').'"', $html);
    }

    public function test_the_panel_is_a_daisyui_menu_with_a_tone_glyph_per_item(): void
    {
        $this->overdueInvoice();

        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // The list used to be a bare <ul> of links with hover backgrounds while
        // the account menu beside it used `menu` — two panels, two behaviours,
        // from one header.
        $this->assertStringContainsString('menu min-h-0 flex-1 flex-nowrap', $html);

        // One glyph per row, so "which of these is the urgent one" is a looking
        // task rather than a reading one.
        $this->assertSame(
            substr_count($html, 'data-line-tone-glyph'),
            substr_count($html, '<li>'.PHP_EOL.'                        <a href'),
            'Every notification row needs exactly one tone glyph.',
        );

        $this->assertStringContainsString('data-line-tone-glyph="error"', $html);
        $this->assertStringContainsString('data-line-tone-glyph="warning"', $html);
    }

    public function test_both_panel_triggers_declare_what_they_open(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // `initDropdownState` only keeps `aria-expanded` in step on triggers
        // that already declare it, so its absence leaves a permanently collapsed
        // panel announced to assistive technology.
        $cluster = $this->headerCluster($html);

        $this->assertMatchesRegularExpression(
            '/<button[^>]*aria-haspopup="true"[^>]*aria-expanded="false"/',
            $cluster,
            'The bell opens a menu and must say so.',
        );

        // The account panel is a disclosure, not a menu: it holds the language
        // form and has no arrow-key navigation, so `aria-haspopup` would promise
        // a menu widget that is not there. `aria-controls` points at the panel
        // and `aria-expanded` reports its state — the correct pattern for a
        // button that reveals a block of content.
        $this->assertMatchesRegularExpression(
            '/<button[^>]*aria-expanded="false"[^>]*aria-controls="account-menu-panel"/',
            $cluster,
            'The account row must say what it opens.',
        );
    }

    public function test_the_header_controls_are_large_enough_to_hit(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // They measured 32px before, and this stylesheet already targets 44px
        // on a coarse pointer.
        $cluster = $this->headerCluster($html);

        $this->assertSame(
            2,
            substr_count($cluster, 'size-10'),
            'The bell and the theme switch are square and the same size.',
        );

        $this->assertStringContainsString(
            'h-10',
            $cluster,
            'The account row carries a name and so is wider, but must match in height.',
        );
    }

    public function test_the_avatar_declares_its_own_square(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // DaisyUI hangs `aspect-ratio: 1` on `.avatar > div`, so a `<span>`
        // child never received it and the badge rendered 28x16 — a stretched
        // oval. The shape is now stated rather than inherited.
        $this->assertMatchesRegularExpression(
            '/avatar avatar-placeholder[^>]*>\s*<span class="[^"]*size-8[^"]*rounded-full/',
            $html,
            'The avatar needs an explicit square size.',
        );
    }

    public function test_the_account_row_advertises_that_it_opens_something(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // A ghost button carrying a name does not look like it opens a panel.
        // The caret's path is asserted rather than its name, because the name
        // never reaches the page — the icon component renders only the path.
        $this->assertStringContainsString(
            'M4.5 8.25 12 15.75l7.5-7.5',
            $html,
            'The account row needs a downward caret next to the name.',
        );
    }

    public function test_narrow_panels_are_pinned_to_the_viewport(): void
    {
        // The bell sits hard against the right edge of a 375px phone, and a
        // `right: 0` panel anchored to it started at x=-83: a third of the list
        // off-screen with no way to scroll to it.
        $css = $this->withoutComments(file_get_contents(resource_path('css/app.css')));

        $this->assertStringContainsString('.header-controls .dropdown-content', $css);

        $this->assertMatchesRegularExpression(
            '/@media \(width < 64rem\) \{[^}]*\.header-controls \.dropdown-content \{[^}]*position: fixed/s',
            $css,
            'The panel must be fixed to the viewport below the desktop breakpoint.',
        );
    }

    public function test_the_chevron_icon_exists(): void
    {
        $icons = file_get_contents(resource_path('views/components/icon.blade.php'));

        $this->assertStringContainsString("'chevron-down'", $icons);
    }

    public function test_the_list_is_built_once_per_request(): void
    {
        // The dashboard renders its own attention section and the header bell is
        // on every page, so `items()` gets asked for twice. It is eight queries
        // over feed, invoices, purchase orders, treatments, births and stock, so
        // the second pass is the difference between one set and two.
        $queries = 0;

        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $service = app(AttentionService::class);

        $first = $service->items();
        $afterFirst = $queries;

        $second = $service->items();

        $this->assertSame($first, $second, 'A repeated call must return the same list.');
        $this->assertGreaterThan(0, $afterFirst, 'The first call should have queried something.');
        $this->assertSame(
            $afterFirst,
            $queries,
            'The second call ran more queries; the list is no longer being remembered.',
        );
    }

    /**
     * Just the header controls.
     *
     * Counted across the whole page the numbers are meaningless: every table
     * row carries its own action menu with the same `aria-haspopup` pair, and
     * `size-10` appears in pagination and filters.
     */
    private function headerCluster(string $html): string
    {
        $start = strpos($html, 'header-controls');

        $this->assertNotFalse($start, 'The header control cluster is missing.');

        $end = strpos($html, '</header>', $start);

        return substr($html, $start, $end - $start);
    }

    private function bellCount(string $html): int
    {
        preg_match('/badge badge-error badge-xs[^>]*>\s*([0-9]+)/', $html, $m);

        return (int) ($m[1] ?? 0);
    }

    private function panelCount(string $html): int
    {
        preg_match('/badge badge-error badge-sm[^>]*>\s*([0-9]+)/', $html, $m);

        return (int) ($m[1] ?? 0);
    }

    private function overdueInvoice(): void
    {
        Invoice::factory()->create([
            'invoice_date' => now()->subDays(45)->toDateString(),
            'due_date' => now()->subDays(30)->toDateString(),
            'status' => InvoiceStatus::Unpaid,
            'paid_amount' => 0,
        ]);
    }

    /**
     * A stylesheet with its comments removed.
     *
     * The block under test explains this exact bug and names the selectors, so
     * reading the raw file matches the prose first and reports for the wrong
     * reason.
     */
    private function withoutComments(string $css): string
    {
        return (string) preg_replace('#/\*.*?\*/#s', '', $css);
    }
}
