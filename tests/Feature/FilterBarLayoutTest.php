<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The filter bar's layout.
 *
 * The bug this guards was invisible in the markup and in every other kind of
 * check. Selecting a filter ticked a checkbox, and a CSS rule keyed on that
 * checkbox outranked the rule that keeps the controls in the form's own grid —
 * so on a desktop every dropdown silently jumped from a 274px column to the
 * full 1118px of the bar. Nothing about the HTML changed. Only a measurement
 * could see it.
 *
 * The stylesheet is therefore checked as well as the rendered page: the
 * specificity clash is a property of the source, and asserting it is the only
 * way to keep a future edit from putting the two rules back in step.
 */
class FilterBarLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    public function test_the_disclosure_rule_is_confined_to_the_range_where_the_toggle_exists(): void
    {
        // Comments stripped first: the block in the stylesheet explains this
        // exact clash and names the selector, so searching the raw file finds
        // the prose before it reaches the rule and reports for the wrong reason.
        $css = $this->withoutComments(file_get_contents(resource_path('css/app.css')));

        // `.peer:checked ~ .filter-fields` at (0,3,0) used to be declared
        // unconditionally, and `.filter-fields { display: contents }` at (0,1,0)
        // inside the desktop media query came after it. A media query adds no
        // specificity, so the checked rule won at every width and the desktop
        // grid collapsed the moment a filter was active.
        $checkedRule = strpos($css, '.peer:checked ~ .filter-fields');

        $this->assertNotFalse($checkedRule, 'The disclosure rule is missing.');

        $mobile = strpos($css, '@media (width < 64rem)');

        $this->assertNotFalse($mobile, 'The disclosure must be scoped below the desktop breakpoint.');

        $this->assertLessThan(
            $checkedRule,
            $mobile,
            'The `:checked` rule has to sit inside a `width < 64rem` block, otherwise it outranks the desktop override.',
        );

        // And the narrow scope has to close before the desktop block opens, or
        // the rule is inside the desktop range rather than below it.
        $desktop = strpos($css, '@media (width >= 64rem)');

        $this->assertLessThan($desktop, $checkedRule, 'The `:checked` rule is still declared outside any narrow scope.');
    }

    public function test_the_checkbox_is_ticked_once_a_filter_is_active(): void
    {
        // This is what made the leak possible, so it is pinned deliberately: the
        // desktop layout must not depend on the checkbox being unticked.
        $unticked = $this->get(route('animals.index'))->assertOk()->getContent();
        $ticked = $this->get(route('animals.index', ['status' => 'active']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/class="peer sr-only"[^>]*>/',
            $unticked,
            'The disclosure checkbox should be present and unticked with no filter set.',
        );

        $this->assertMatchesRegularExpression(
            '/class="peer sr-only" checked/',
            $ticked,
            'An active filter should tick the disclosure so the controls stay reachable.',
        );
    }

    public function test_the_grid_columns_survive_an_active_filter(): void
    {
        $columns = 'sm:grid-cols-2 lg:grid-cols-4';

        $unticked = $this->get(route('animals.index'))->assertOk()->getContent();
        $ticked = $this->get(route('animals.index', ['status' => 'active']))->assertOk()->getContent();

        // The form's own grid is the fallback if the stylesheet is ever wrong,
        // so both states must declare it identically.
        $this->assertStringContainsString($columns, $unticked);
        $this->assertStringContainsString($columns, $ticked);

        $this->assertSame(
            substr_count($unticked, 'filter-select'),
            substr_count($ticked, 'filter-select'),
            'An active filter must not change how many filter controls the page renders.',
        );
    }

    public function test_the_mobile_toggle_is_hidden_on_a_desktop(): void
    {
        $html = $this->get(route('animals.index', ['status' => 'active']))->assertOk()->getContent();

        // `lg:hidden` on the label is what keeps a phone control off a desktop;
        // if it is ever dropped, the same clash returns with a button on top.
        $this->assertMatchesRegularExpression(
            '/<label for="filter-toggle-[^"]*" class="btn btn-ghost btn-sm w-full justify-start gap-2 lg:hidden">/',
            $html,
        );
    }

    /**
     * A stylesheet with its comments removed.
     *
     * The rule under test is named in the comment explaining why it is scoped
     * the way it is. Reading the raw file would match that prose first and pass
     * or fail for reasons that have nothing to do with the rule.
     */
    private function withoutComments(string $css): string
    {
        return (string) preg_replace('#/\*.*?\*/#s', '', $css);
    }

    public function test_filter_selects_keep_matching_width_to_their_trigger(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        // The panel is positioned with `position: fixed`, so it needs the
        // trigger's width written on it or it falls back to its own `w-full`
        // and covers the whole bar.
        $this->assertStringContainsString("dropdown.hasAttribute('data-filter-select')", $script);
        $this->assertStringContainsString('panel.style.width', $script);
        $this->assertStringContainsString("panel.style.position = 'fixed'", $script);
    }
}
