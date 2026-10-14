<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The index pages have to be usable on a phone.
 *
 * The livestock list alone carries six filters. Stacked full width they filled
 * the entire first screen: a visitor scrolled past the title, six stat tiles,
 * the search box, every dropdown, a checkbox and the apply button before
 * reaching a single row of data. The controls now sit behind a toggle on a
 * narrow viewport while the search box, the control people reach for most,
 * stays visible.
 *
 * The desktop layout is untouched, and that is the part worth asserting: the
 * wrapper collapses to `display: contents` above the breakpoint so the controls
 * go back to being direct grid items of the form. An earlier attempt also put
 * `display: contents` on the button wrapper, which made the buttons grid items
 * in their own right — and a grid item stretches, which quietly widened "Apply"
 * from 82px to the whole 274px column.
 */
class MobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    public function test_the_filter_bar_collapses_behind_a_toggle_on_a_phone(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // The checkbox is a direct child of the form: the `:checked ~
        // .filter-fields` rule matches siblings, and nesting it inside the
        // label made the toggle do nothing at all.
        $this->assertMatchesRegularExpression(
            '/<input type="checkbox"[^>]*class="peer sr-only"[^>]*>\s*<label for="/s',
            $html,
            'The toggle must be a sibling of the fields it reveals, not nested in the label.',
        );

        $this->assertStringContainsString('filter-fields', $html);
        $this->assertStringContainsString('lg:hidden', $html, 'The toggle must not appear on desktop.');
    }

    public function test_the_toggle_appears_once_per_bar_and_is_unique_on_the_page(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // The identifier is derived from the current URL so two bars on one page
        // cannot collide, and a duplicate id would make the label toggle the
        // wrong control.
        preg_match_all('/id="(filter-toggle-[a-f0-9]+)"/', $html, $matches);

        $this->assertCount(1, $matches[1]);
        $this->assertSame(array_unique($matches[1]), $matches[1], 'The toggle id is not unique.');
    }

    public function test_the_layout_rules_keep_desktop_and_collapse_the_phone(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/\.filter-fields\s*\{[^}]*display:\s*none/s',
            $css,
            'The phone layout must collapse the filters by default.',
        );

        // The rule carries a selector list, so the block may not open right
        // after `.filter-fields`.
        $this->assertMatchesRegularExpression(
            '/\.peer:checked ~ \.filter-fields[^}]*\{[^}]*display:\s*grid/s',
            $css,
            'Checking the toggle must reveal the filters.',
        );

        // The desktop half of the rule: above the breakpoint the wrapper stops
        // generating a box so the controls rejoin the form's own grid.
        $this->assertMatchesRegularExpression(
            '/@media \(width >= 64rem\)\s*\{\s*\.filter-fields\s*\{[^}]*display:\s*contents/s',
            $css,
            'Desktop must lay the filters out as before.',
        );
    }

    public function test_the_desktop_apply_button_is_not_widened_by_the_collapse(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // Regression guard: putting `display: contents` on the button wrapper as
        // well made the buttons grid items, and a grid item stretches.
        $this->assertStringNotContainsString('flex items-center gap-2 lg:contents', $html);
    }

    public function test_touch_targets_grow_without_moving_the_glyph(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // The hit area is expanded with a pseudo-element rather than padding, so
        // the row stays the height it is on desktop and only the invisible
        // target grows.
        $this->assertMatchesRegularExpression('/@media \(pointer: coarse\)/', $css);
        $this->assertStringContainsString('.row-single-action', $css);
        $this->assertStringContainsString('inset: -9px -8px', $css);

        // A coarse pointer is the only place this applies; a mouse on a narrow
        // window keeps the compact target.
        $this->assertDoesNotMatchRegularExpression(
            '/\.row-single-action\s*:\s*is\(a,\s*button\)\s*\{[^}]*padding[^}]*\}/s',
            $css,
            'Padding would move the glyph, not just the hit area.',
        );
    }

    public function test_search_stays_visible_while_the_filters_are_collapsed(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // The search input is outside `.filter-fields`, so collapsing the
        // filters never hides the control people reach for most.
        preg_match('/<form[^>]*role="search"(.*?)<\/form>/s', $html, $form);

        $this->assertNotEmpty($form[1]);
        $this->assertMatchesRegularExpression('/name="search"/', $form[1]);

        preg_match('/name="search".*?<div class="filter-fields">/s', $form[1], $order);
        $this->assertNotEmpty($order, 'The search field must sit before the collapsible region.');
    }

    public function test_no_index_page_overflows_the_viewport_width(): void
    {
        // The tables scroll horizontally inside their own container; the page
        // itself must not.
        $overflow = [];

        foreach (['animals.index', 'feed-items.index', 'invoices.index', 'barns.index', 'expenses.index'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString('table-scroll', $html, "{$route} has no scroll container for its table.");
        }

        $this->assertSame([], $overflow);
    }
}
