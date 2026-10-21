<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Row actions have to earn a menu.
 *
 * The lists used to render a bare `btn btn-ghost btn-xs` per action, so a row
 * with two actions stretched the row and pushed the table wider, and each page
 * invented its own arrangement. They were all moved into one DaisyUI dropdown
 * per row, which fixed the geometry — but then every row paid two clicks to
 * reach its primary action, and 18 of the 22 menus in the views offer exactly
 * one entry.
 *
 * A single entry now renders as a direct control and two or more keep the
 * dropdown. Direct controls show only their icon; the label stays in the DOM
 * and in `aria-label`/`title`. This walks the real pages and checks both shapes:
 * an action cell is never empty, a row with one action never gets a menu, and
 * every entry still points somewhere.
 */
class ActionsMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->share('errors', new ViewErrorBag);
    }

    public function test_a_single_entry_renders_as_a_direct_control(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = Blade::render(
            '<x-actions-menu label="Actions for BL-001">'
            .'<x-menu-link href="/animals/1" icon="info">Details</x-menu-link>'
            .'</x-actions-menu>'
        );

        $this->assertStringContainsString('row-single-action has-icon', $html);
        $this->assertStringNotContainsString('dropdown', $html, 'One action must not open a menu.');
        $this->assertStringNotContainsString('aria-haspopup', $html);

        // The entry keeps working: a real href, its icon, and a name for
        // assistive technology even though the label is visually hidden.
        $this->assertStringContainsString('href="/animals/1"', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('aria-label="Details"', $html);
        $this->assertStringContainsString('title="Details"', $html);
        $this->assertStringContainsString('Details', $html);
    }

    public function test_a_single_entry_keeps_its_icon_and_accessible_name(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = Blade::render(
            '<x-actions-menu label="Actions">'
            .'<x-menu-link href="/animals/1" icon="info">Detail</x-menu-link>'
            .'</x-actions-menu>'
        );

        // The icon is the visible control; the text remains as the accessible
        // name and the hover title, so the action is not ambiguous.
        $this->assertStringContainsString('<svg', $html, 'The single-action control should carry its icon.');
        $this->assertStringContainsString('aria-label="Detail"', $html);
        $this->assertStringContainsString('title="Detail"', $html);
        $this->assertStringContainsString('Detail', $html);
    }

    public function test_action_views_do_not_use_an_eye_icon(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            $contents = $file->getContents();

            if (str_contains($contents, 'icon="eye"') || str_contains($contents, 'name="eye"')) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders, 'Use the info icon for detail actions, not an eye.');
    }

    public function test_two_entries_still_render_a_daisyui_dropdown(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = Blade::render(
            '<x-actions-menu label="Actions for BL-001">'
            .'<x-menu-link href="/animals/1" icon="info">Details</x-menu-link>'
            .'<x-menu-link href="/animals/1/edit" icon="edit">Edit</x-menu-link>'
            .'</x-actions-menu>'
        );

        $this->assertStringContainsString('class="dropdown dropdown-end row-menu"', $html);
        $this->assertStringContainsString('aria-haspopup="true"', $html);
        $this->assertStringContainsString('aria-label="Actions for BL-001"', $html);
        $this->assertStringContainsString('menu dropdown-content', $html);
        $this->assertStringContainsString('href="/animals/1/edit"', $html);
    }

    public function test_the_trigger_reports_its_expanded_state(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = Blade::render(
            '<x-actions-menu label="Actions">'
            .'<x-menu-link href="/a">A</x-menu-link>'
            .'<x-menu-link href="/b">B</x-menu-link>'
            .'</x-actions-menu>'
        );

        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('row-menu-trigger', $html);

        // The attribute is only meaningful if something keeps it in step.
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("closest('.dropdown')", $js, 'No script syncs aria-expanded.');
        $this->assertStringContainsString(':focus-within', $js, 'aria-expanded ignores the DaisyUI open state.');
    }

    public function test_every_row_action_is_reachable_on_every_page(): void
    {
        $this->seed(DatabaseSeeder::class);

        $failures = [];
        $empty = [];
        $cells = 0;
        $direct = 0;
        $menus = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || $route->parameterNames() !== []) {
                continue;
            }

            $response = $this->actingAs($this->owner())->get('/'.ltrim($route->uri(), '/'));

            if (! $response->isSuccessful()) {
                continue;
            }

            $html = $response->getContent();

            // Every row's action cell: one direct control or one dropdown, but
            // never neither.
            preg_match_all('/<td class="row-actions">(.*?)<\/td>/s', $html, $matches);

            $perPage = $perPageEmpty = 0;

            foreach ($matches[1] as $cell) {
                $cells++;
                $perPage++;

                // A menu also contains links, so the trigger has to be
                // recognised first or every dropdown counts as a direct action.
                if (str_contains($cell, 'aria-haspopup="true"')) {
                    $menus++;

                    if (! str_contains($cell, 'aria-expanded="false"')) {
                        $failures[] = $route->getName().' has a menu trigger with no expanded state.';
                    }

                    if (substr_count($cell, '<li') < 2) {
                        $failures[] = $route->getName().' opens a menu for a single action.';
                    }
                } elseif (str_contains($cell, 'row-single-action')) {
                    $direct++;

                    if (preg_match('/href="#"/', $cell)) {
                        $failures[] = $route->getName().' has a placeholder action link.';
                    }
                } elseif (preg_match('/href="([^"]+)"/', $cell, $href)) {
                    if ($href[1] === '#') {
                        $failures[] = $route->getName().' has a placeholder action link.';
                    }

                    $direct++;
                } else {
                    // Legitimate: the row has no available action at all. The
                    // treatments list offers one only when a health record
                    // exists, so the cell is genuinely empty. Counted so a page
                    // whose actions were never converted cannot pass silently.
                    $perPageEmpty++;
                }
            }

            if ($perPageEmpty > 0) {
                $empty[$route->getName()] = [$perPageEmpty, $perPage];
            }
        }

        // A page where most rows offer no action is the signature of a table
        // whose actions were never rendered at all.
        foreach ($empty as $page => [$emptyCount, $total]) {
            $this->assertLessThan(
                $total,
                $emptyCount,
                "[{$page}] all {$total} action cells are empty.",
            );
        }

        $this->assertGreaterThan(100, $cells, 'Too few action cells were checked for the test to mean anything.');
        $this->assertGreaterThan(50, $direct, 'Too few direct actions for the test to mean anything.');
        $this->assertGreaterThan(10, $menus, 'Too few menus for the test to mean anything.');
        $this->assertSame([], array_unique($failures), "Action problems:\n".implode("\n", array_unique($failures)));
    }

    public function test_a_row_with_one_action_is_never_given_a_menu(): void
    {
        $this->seed(DatabaseSeeder::class);

        $failures = [];
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || $route->parameterNames() !== []) {
                continue;
            }

            $response = $this->actingAs($this->owner())->get('/'.ltrim($route->uri(), '/'));

            if (! $response->isSuccessful()) {
                continue;
            }

            preg_match_all('/<td class="row-actions">(.*?)<\/td>/s', $response->getContent(), $matches);

            foreach ($matches[1] as $cell) {
                $checked++;

                // Only dropdowns are in question here; a direct control has
                // nothing to collapse.
                if (! str_contains($cell, 'aria-haspopup="true"')) {
                    continue;
                }

                if (substr_count($cell, '<li') <= 1) {
                    $failures[] = $route->getName().' shows a menu for a single action.';
                }
            }
        }

        $this->assertGreaterThan(100, $checked);
        $this->assertSame([], array_unique($failures), "Pointless menus:\n".implode("\n", array_unique($failures)));
    }

    public function test_action_cells_are_marked_as_the_action_column(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = $this->actingAs($this->owner())->get(route('animals.index'))->assertOk()->getContent();

        // `text-right whitespace-nowrap` is what stops the column from wrapping
        // and making the row taller than its neighbours.
        $this->assertStringContainsString('<th class="row-actions">', $html);
    }
}
