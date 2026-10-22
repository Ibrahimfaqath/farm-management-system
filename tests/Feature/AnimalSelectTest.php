<?php

namespace Tests\Feature;

use App\Enums\Sex;
use App\Models\Animal;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Animal dropdowns.
 *
 * There are enough animals that a native select turns into a wall of names.
 * The control keeps the native select for submission and for browsers without
 * JavaScript, but shows only a short first page until the reader searches.
 */
class AnimalSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_animal_picker_keeps_the_native_select_and_shows_only_a_short_first_page(): void
    {
        $this->app['view']->share('errors', new ViewErrorBag);

        $html = Blade::render(
            '<x-form.animal-select name="animal_id" label="Animal" :options="$options" :selected="3" :limit="2" class="sm:col-span-2" />',
            ['options' => [1 => 'T-001 · Bella', 2 => 'T-002 · Moly', 3 => 'T-003 · Daisy', 4 => 'T-004 · Rose']],
        );

        $this->assertStringContainsString('form-field w-full sm:col-span-2', $html);
        $this->assertStringContainsString('name="animal_id"', $html);
        $this->assertStringContainsString('data-animal-select-native', $html);
        $this->assertStringContainsString('data-animal-select-search', $html);
        $this->assertStringContainsString('data-animal-select-limit="2"', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);

        $this->assertMatchesRegularExpression('/data-value="4"[^>]*hidden/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-value="2"[^>]*hidden/', $html);
    }

    public function test_every_animal_form_uses_the_searchable_picker(): void
    {
        $this->seed(DatabaseSeeder::class);

        $routes = [
            'milk-productions.create' => 1,
            'health-records.create' => 1,
            'vaccinations.create' => 1,
            'treatments.create' => 1,
            'breeding-records.create' => 2,
            'pregnancy-checks.create' => 1,
            'animals.create' => 2,
            'birth-records.create' => 2,
        ];

        foreach ($routes as $route => $pickerCount) {
            $html = $this->actingAs($this->owner())->get(route($route))->assertOk()->getContent();

            $this->assertSame(
                $pickerCount,
                substr_count($html, 'data-animal-select-native'),
                "{$route} should expose {$pickerCount} searchable animal picker(s).",
            );
            $this->assertSame(
                $pickerCount,
                substr_count($html, 'data-animal-select-search'),
                "{$route} should let the reader search every animal picker.",
            );
        }
    }

    public function test_every_animal_filter_has_its_own_search_field(): void
    {
        $this->seed(DatabaseSeeder::class);

        $routes = [
            'health-records.index',
            'vaccinations.index',
            'treatments.index',
            'breeding-records.index',
            'pregnancy-checks.index',
            'birth-records.index',
        ];

        foreach ($routes as $route) {
            $html = $this->actingAs($this->owner())->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString('data-filter-select-searchable', $html, "{$route} needs a searchable animal filter.");
            $this->assertStringContainsString('data-filter-select-search', $html, "{$route} needs a search field in the animal filter.");
            $this->assertStringContainsString('Cari ternak…', $html, "{$route} should label the field in Indonesian.");
        }
    }

    public function test_every_animal_option_includes_the_name_so_it_can_be_searched(): void
    {
        $this->seed(DatabaseSeeder::class);

        $female = Animal::query()->where('sex', Sex::Female->value)->whereNotNull('name')->orderBy('animal_code')->firstOrFail();
        $male = Animal::query()->where('sex', Sex::Male->value)->whereIn('status', ['active'])->whereNotNull('name')->orderBy('animal_code')->firstOrFail();
        $lactating = Animal::query()->active()->lactating()->whereNotNull('name')->orderBy('animal_code')->firstOrFail();

        $routes = [
            'milk-productions.create' => [$lactating],
            'health-records.create' => [$female],
            'vaccinations.create' => [$female],
            'treatments.create' => [$female],
            'breeding-records.create' => [$female, $male],
            'pregnancy-checks.create' => [$female],
            'animals.create' => [$female, $male],
            'birth-records.create' => [$female, $male],
            'health-records.index' => [$female],
            'vaccinations.index' => [$female],
            'treatments.index' => [$female],
            'breeding-records.index' => [$female],
            'pregnancy-checks.index' => [$female],
            'birth-records.index' => [$female],
        ];

        foreach ($routes as $route => $animals) {
            $html = $this->actingAs($this->owner())->get(route($route))->assertOk()->getContent();

            foreach ($animals as $animal) {
                $this->assertMatchesRegularExpression(
                    '/<span class="truncate">[^<]*'.preg_quote($animal->animal_code, '/').'[^<]*'.preg_quote((string) $animal->name, '/').'[^<]*<\/span>/',
                    $html,
                    "{$route} should expose the animal name to the picker search.",
                );
            }
        }
    }
}
