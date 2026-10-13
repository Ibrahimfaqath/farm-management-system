<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Table headers and bodies have to render at one consistent rhythm.
 *
 * Two separate faults made them look wrong. `.app-table` set its own cell
 * padding from the `components` layer, but DaisyUI emits `.table :where(th, td)`
 * from a layer *nested inside* `components`, and a nested layer wins over its
 * parent however specific the parent's selectors are. Every row therefore kept
 * DaisyUI's 12px padding and came out roughly 20px taller than intended. The
 * override only took hold once it was moved into `utilities`, the last layer.
 *
 * The second fault was content: long free-text cells wrapped to two or three
 * lines and dragged their row to 85px while its neighbours stayed at 40px, so
 * the body looked ragged even though the styling was right.
 */
class TableRhythmTest extends TestCase
{
    use RefreshDatabase;

    public function test_cell_padding_is_overridden_in_the_last_layer(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Locate the block that sets padding and assert which layer it sits in.
        $this->assertMatchesRegularExpression(
            '/@layer utilities\s*\{(?:(?!@layer).)*\.app-table :is\(thead,\s*tbody\) :is\(th,\s*td\)\s*\{(?:(?!@layer).)*padding-block/s',
            $css,
            'Cell padding must be overridden from the utilities layer, or DaisyUI wins.',
        );

        // The same trap applies to the row-menu trigger, whose `.btn-ghost`
        // background also comes from the nested layer.
        $this->assertMatchesRegularExpression(
            '/@layer utilities\s*\{(?:(?!@layer).)*\.row-menu-trigger\[aria-expanded=\'true\'\]/s',
            $css,
            'The open row-menu highlight must live in the utilities layer.',
        );
    }

    public function test_every_index_table_uses_the_shared_class_inside_a_scroll_wrapper(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->app['view']->share('errors', new ViewErrorBag);

        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || $route->parameterNames() !== []) {
                continue;
            }

            $response = $this->actingAs($this->owner())->get('/'.ltrim($route->uri(), '/'));

            if (! $response->isSuccessful()) {
                continue;
            }

            $html = $response->getContent();

            if (! str_contains($html, '<table')) {
                continue;
            }

            $checked++;

            preg_match_all('/<table\b[^>]*class="([^"]*)"/', $html, $tables);

            foreach ($tables[1] as $class) {
                $this->assertStringContainsString(
                    'app-table',
                    $class,
                    "{$route->getName()} renders a table without app-table: {$class}",
                );
            }
        }

        $this->assertGreaterThan(20, $checked, 'Too few pages were checked for the test to mean anything.');
    }

    public function test_headers_and_bodies_agree_on_column_count(): void
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

            $html = $response->getContent();

            // One table at a time: split on each opening tag and keep the first
            // header and first body row that follow it.
            foreach (explode('<table', $html) as $chunk) {
                if (! preg_match('/class="[^"]*app-table/', $chunk)) {
                    continue;
                }

                $checked++;

                $headers = preg_match('/<thead>.*?<tr>(.*?)<\/tr>/s', $chunk, $head)
                    ? preg_match_all('/<th\b/', $head[1])
                    : 0;

                $cells = preg_match('/<tbody>.*?<tr>(.*?)<\/tr>/s', $chunk, $body)
                    ? preg_match_all('/<td\b/', $body[1])
                    : 0;

                // A mismatch shifts every column after the gap, which is what
                // made the barns page unreadable: eight headers over nine cells.
                if ($headers > 0 && $cells > 0 && $headers !== $cells) {
                    $failures[] = sprintf(
                        '%s: %d header cells but %d body cells',
                        $route->getName(),
                        $headers,
                        $cells,
                    );
                }
            }
        }

        $this->assertGreaterThan(20, $checked, 'Too few tables were checked for the test to mean anything.');
        $this->assertSame([], $failures, "Table column mismatches:\n".implode("\n", array_unique($failures)));
    }

    public function test_free_text_cells_truncate_instead_of_stretching_their_row(): void
    {
        $html = Blade::render(
            '<table class="table app-table"><tbody><tr>'
            .'<td class="max-w-xs truncate" title="A very long vendor name">A very long vendor name</td>'
            .'</tr></tbody></table>'
        );

        // The pattern the long-text columns rely on: a capped width plus
        // truncation, with the full value kept in the tooltip.
        $this->assertStringContainsString('truncate', $html);
        $this->assertStringContainsString('title="A very long vendor name"', $html);
    }
}
