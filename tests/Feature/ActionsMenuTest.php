<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Every action a table row offers has to live in one DaisyUI dropdown.
 *
 * The lists used to render a bare `btn btn-ghost btn-xs` per action, so a row
 * with two actions stretched the row and pushed the table wider, and each page
 * invented its own arrangement. This walks the real pages and checks the menu
 * is a real DaisyUI dropdown: the panel carries `dropdown-content menu`, the
 * trigger is a labelled button, the panel is never empty, and every entry still
 * points somewhere.
 */
class ActionsMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_actions_menu_renders_a_daisyui_dropdown(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->app['view']->share('errors', new ViewErrorBag);

        $html = Blade::render(
            '<x-actions-menu label="Actions for BL-001">'
            .'<x-menu-link href="/animals/1" icon="eye">Details</x-menu-link>'
            .'</x-actions-menu>'
        );

        $this->assertStringContainsString('class="dropdown dropdown-end row-menu"', $html);
        $this->assertStringContainsString('aria-haspopup="true"', $html);
        $this->assertStringContainsString('aria-label="Actions for BL-001"', $html);
        $this->assertStringContainsString('menu dropdown-content', $html);
        $this->assertStringContainsString('tabindex="0"', $html);
        $this->assertStringContainsString('href="/animals/1"', $html);
        $this->assertStringContainsString('Details', $html);
    }

    /**
     * The trigger has to report its open state. DaisyUI reveals the panel on
     * `:focus-within` and keeps no state on the element, so `aria-expanded`
     * started on "false" and stayed there for as long as the menu was open.
     */
    public function test_the_trigger_reports_its_expanded_state(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->app['view']->share('errors', new ViewErrorBag);

        $html = Blade::render(
            '<x-actions-menu label="Actions for BL-001">'
            .'<x-menu-link href="/animals/1" icon="eye">Details</x-menu-link>'
            .'</x-actions-menu>'
        );

        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('row-menu-trigger', $html);

        // The attribute is only meaningful if something keeps it in step.
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("closest('.dropdown')", $js, 'No script syncs aria-expanded.');
        $this->assertStringContainsString(':focus-within', $js, 'aria-expanded ignores the DaisyUI open state.');
    }

    public function test_every_table_action_is_a_dropdown_and_never_empty(): void
    {
        $this->seed(DatabaseSeeder::class);

        $failures = [];
        $menus = 0;
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || $route->parameterNames() !== []) {
                continue;
            }

            $response = $this->actingAs($this->owner())->get('/'.ltrim($route->uri(), '/'));

            if (! $response->isSuccessful()) {
                continue;
            }

            $checked++;
            $html = $response->getContent();

            // A bare action button left beside the dropdown means only part of
            // the page was converted.
            if (preg_match_all('/<td class="row-actions">\s*<(?:a|button)\b[^>]*\bclass="[^"]*\bbtn\b/', $html, $m) > 0) {
                $failures[] = $route->getName().' still renders a raw action button: '.substr($m[0][0], 0, 90);
            }

            // 1/2 = trigger attributes, 3 = trigger content, 4 = panel
            // attributes, 5 = panel entries.
            preg_match_all(
                '/<button((?:[^>]|\n)*?)aria-haspopup="true"((?:[^>]|\n)*?)>(.*?)<ul([^>]*dropdown-content[^>]*)>(.*?)<\/ul>/s',
                $html,
                $rows,
                PREG_SET_ORDER
            );

            foreach ($rows as $row) {
                $menus++;

                if (! str_contains($row[1].$row[2], 'aria-label=')) {
                    $failures[] = $route->getName().' has a dropdown trigger with no accessible name';
                }

                if (! str_contains($row[4], 'menu')) {
                    $failures[] = $route->getName().' has a panel that is not a DaisyUI menu';
                }

                if (! str_contains($row[5], '<li')) {
                    $failures[] = $route->getName().' has a dropdown with no entries';
                }
            }
        }

        $this->assertGreaterThan(30, $checked, 'Too few pages were checked for the test to mean anything.');
        $this->assertGreaterThan(20, $menus, 'Too few action menus were found for the test to mean anything.');
        $this->assertSame([], $failures, "Action menu problems:\n".implode("\n", array_unique($failures)));
    }

    public function test_action_menu_entries_still_reach_a_real_route(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = $this->actingAs($this->owner())
            ->get(route('animals.index'))
            ->assertOk()
            ->getContent();

        preg_match_all('/<td class="row-actions">.*?<\/td>/s', $html, $cells);

        $this->assertNotEmpty($cells[0], 'No action cells were rendered.');

        foreach ($cells[0] as $cell) {
            preg_match_all('/href="([^"]+)"/', $cell, $links);

            $this->assertNotEmpty($links[1], 'An action cell has no link: '.substr(strip_tags($cell), 0, 80));

            foreach ($links[1] as $href) {
                $this->assertNotSame('#', $href, 'An action cell has a placeholder link.');
            }
        }
    }
}
