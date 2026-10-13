<?php

namespace Tests\Feature;

use App\Http\Controllers\BarnController;
use App\Http\Controllers\BreedController;
use App\Models\Farm;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Filter dropdowns.
 *
 * A native `<select>` cannot be themed: opening one adds no DOM node at all,
 * because the option list is painted by the operating system. So every filter
 * used to open a plain browser menu beside an otherwise DaisyUI page, and that
 * is not something CSS can fix.
 *
 * `x-filter-select` keeps the real `<select>` in the form — it still holds the
 * submitted value and still works without JavaScript — and `app.js` drives a
 * DaisyUI dropdown in front of it. These tests hold that contract: the listbox
 * markup is complete, no filter bar keeps a bare select, and the controllers
 * pass `$filters` so an active filter can actually show up in the trigger.
 */
class FilterSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_a_complete_themed_listbox(): void
    {
        $this->seed(DatabaseSeeder::class);
        View::share('errors', new ViewErrorBag);

        $html = Blade::render(
            '<x-filter-select name="status" :options="[\'active\' => \'Active\', \'sold\' => \'Sold\']"'
            .' :selected="\'sold\'" :placeholder="\'All statuses\'" :label="\'Filter by status\'" />'
        );

        // The working control: still a real select, still named for the form.
        $this->assertStringContainsString('data-filter-select-native', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('<option value="sold" selected>Sold</option>', $html);

        // The themed control.
        $this->assertStringContainsString('class="dropdown filter-select w-full"', $html);
        $this->assertStringContainsString('aria-haspopup="listbox"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('aria-label="Filter by status"', $html);
        $this->assertStringContainsString('role="listbox"', $html);
        // `dropdown-content` is the DaisyUI panel; the rows deliberately do not
        // use `menu`, whose 2.5rem navigation rows are too tall for a select.
        $this->assertStringContainsString('dropdown-content', $html);
        $this->assertStringNotContainsString('menu dropdown-content', $html);
        // Attributes are separated by newlines once Blade has rendered them.
        $this->assertMatchesRegularExpression('/data-value="sold"\s+aria-selected="true"/', $html);
        $this->assertMatchesRegularExpression('/data-value="active"\s+aria-selected="false"/', $html);

        // The trigger mirrors the current choice, and the list can always be
        // reset back to "all".
        $this->assertStringContainsString('data-filter-select-value', $html);
        $this->assertStringContainsString('Sold', $html);
        $this->assertStringContainsString('data-value=""', $html);
        $this->assertStringContainsString('All statuses', $html);
    }

    public function test_no_filter_bar_keeps_a_bare_native_select(): void
    {
        $this->seed(DatabaseSeeder::class);

        $failures = [];
        $checked = 0;
        $dropdowns = 0;

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

            if (preg_match_all('/<x-filter-bar\b.*?<\/x-filter-bar>/s', $html, $bars) > 0) {
                foreach ($bars[0] as $bar) {
                    // A select without the component marker is the untouched
                    // native control, which still opens the OS menu.
                    if (preg_match('/<select\b(?![^>]*data-filter-select-native)/', $bar) === 1) {
                        $failures[] = $route->getName().' has a bare <select> inside its filter bar';
                    }
                }
            }

            // The component is compiled away, so count its rendered marker.
            if (preg_match_all('/data-filter-select-list/', $html) > 0) {
                $dropdowns++;
            }
        }

        $this->assertGreaterThan(15, $checked, 'Too few pages were checked for the test to mean anything.');
        $this->assertGreaterThan(15, $dropdowns, 'Too few filter dropdowns were found for the test to mean anything.');
        $this->assertSame([], $failures, "Unconverted filters:\n".implode("\n", array_unique($failures)));
    }

    /**
     * A controller that forgets `$filters` filters correctly but leaves the
     * trigger showing "all", so an active filter looks like there is none.
     */
    public function test_index_controllers_hand_the_current_filters_to_the_view(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expected = [
            [BarnController::class, 'barns.index', ['type' => 'dairy'], 'Kandang Laktasi'],
            [BarnController::class, 'barns.index', ['farm_id' => Farm::value('id')], null],
            [BreedController::class, 'breeds.index', ['species' => 'cattle'], 'Sapi'],
        ];

        foreach ($expected as [$controller, $route, $query, $label]) {
            $html = $this->actingAs($this->owner())
                ->get(route($route, $query))
                ->assertOk()
                ->getContent();

            $name = array_key_first($query);
            preg_match('/<select\b[^>]*name="'.$name.'".*?<\/select>/s', $html, $select);

            $this->assertNotEmpty($select, $route.' did not render a filter for '.$name);
            $this->assertMatchesRegularExpression(
                '/<option value="'.preg_quote($query[$name], '/').'" selected>/',
                $select[0],
                $route.' did not mark the active '.$name.' filter as selected'
            );

            if ($label !== null) {
                $this->assertStringContainsString($label, $html);
            }
        }
    }
}
