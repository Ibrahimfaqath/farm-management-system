<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\FinanceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Charts.
 *
 * The runtime is JavaScript, so most of what can be asserted here is the
 * contract between the three parts that are PHP-side: the payload the component
 * writes onto the canvas, the series the controller builds, and the rule about
 * what must never reach the main bundle.
 *
 * Three of these tests exist because of a specific failure that was hard to
 * diagnose and would otherwise be easy to repeat.
 */
class ChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    public function test_the_dashboard_carries_a_chart_for_the_owner(): void
    {
        // Written here rather than left to the seeder. Whether the demo data
        // happens to reach back six months is a property of the demo, not of the
        // dashboard, and a test that depended on it would pass or fail with the
        // calendar rather than with the code.
        Invoice::factory()->create([
            'invoice_date' => now()->startOfMonth(),
            'due_date' => now()->addDays(30),
            'total' => 12_500_000,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        // Two separate markers rather than `canvas[data-chart]`: the attribute
        // sits on its own line in the component, so the CSS selector never
        // appears as one contiguous string in the output.
        $this->assertStringContainsString('<canvas', $html);
        $this->assertStringContainsString('data-chart-payload', $html);
        $this->assertStringContainsString(__('Revenue and expense, last 6 months'), $html);
    }

    public function test_the_payload_is_valid_json_with_a_number_for_every_point(): void
    {
        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        preg_match_all('/data-chart-payload="([^"]*)"/', $html, $matches);

        $this->assertNotEmpty($matches[1], 'No chart payload was written.');

        foreach ($matches[1] as $encoded) {
            $payload = json_decode(html_entity_decode($encoded, ENT_QUOTES), true, 512, JSON_THROW_ON_ERROR);

            $this->assertArrayHasKey('type', $payload);
            $this->assertNotEmpty($payload['labels']);

            foreach ($payload['series'] as $series) {
                $this->assertCount(
                    count($payload['labels']),
                    $series['data'],
                    'A series must have one value per label, or the chart draws a line of the wrong length.',
                );

                foreach ($series['data'] as $value) {
                    $this->assertTrue(
                        $value === null || is_float($value) || is_int($value),
                        'Values must be numbers or null; a string here plots as zero and looks like missing data.',
                    );
                }
            }
        }
    }

    public function test_a_chart_with_nothing_in_it_is_not_drawn(): void
    {
        $html = $this->renderChart([
            'label' => __('Empty'),
            'labels' => ['a', 'b'],
            'series' => [['label' => 'x', 'data' => [0, 0]]],
        ]);

        // A series of zeroes produces an axis running 0 to 1 and no bars, which
        // reads as a chart that failed rather than as an absence of data.
        $this->assertStringNotContainsString('<canvas', $html);
        $this->assertStringContainsString('Belum ada catatan', $html);
    }

    public function test_a_chart_with_one_real_value_is_drawn(): void
    {
        $html = $this->renderChart([
            'label' => __('One point'),
            'labels' => ['a', 'b'],
            'series' => [['label' => 'x', 'data' => [0, 12.5]]],
        ]);

        $this->assertStringContainsString('<canvas', $html);
    }

    public function test_chart_colours_come_from_the_theme_not_from_javascript(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Both themes declare them, which is what makes a chart correct in each
        // without the script carrying a second palette.
        $this->assertSame(
            2,
            substr_count($css, '--chart-label:'),
            'The chart palette must be declared once per theme.',
        );

        foreach (['label', 'grid', 'tooltip-bg', 'tooltip-text'] as $token) {
            $this->assertSame(2, substr_count($css, "--chart-{$token}:"), "Missing --chart-{$token} for a theme.");
        }

        // And the script must not have grown a literal palette of its own.
        // Read with the comments stripped: the file explains at length why
        // colours are resolved through the browser, and that explanation quotes
        // the very notation this assertion is about.
        $script = $this->code(file_get_contents(resource_path('js/charts.js')));

        $this->assertStringNotContainsString(
            'oklch(',
            $script,
            'Colours belong to the theme. A literal here would be correct in one theme and wrong in the other.',
        );
    }

    public function test_the_runtime_is_kept_out_of_the_page_bundle(): void
    {
        $entry = $this->code(file_get_contents(resource_path('js/app.js')));
        $charts = $this->code(file_get_contents(resource_path('js/charts.js')));

        // The entry point reaches the charts module and stops there. A static
        // import of Chart.js anywhere in the chain would put ~60KB of charting
        // code into every page, the login form included, for no benefit.
        $this->assertStringNotContainsString("from 'chart.js'", $entry);
        $this->assertStringNotContainsString('from "chart.js"', $entry);

        $this->assertStringNotContainsString("from 'chart.js'", $charts);
        $this->assertStringNotContainsString('from "chart.js"', $charts);

        // The chunk itself is reached dynamically, which is what makes the
        // bundler split it out in the first place.
        $this->assertStringContainsString("import('./chart-runtime.js')", $charts);

        // And the early return is what keeps a page with no chart from
        // requesting the chunk at all.
        $this->assertMatchesRegularExpression(
            '/canvas\[data-chart\][\s\S]*?if \(canvases\.length === 0\) \{\s*return;\s*\}/',
            $charts,
        );
    }

    public function test_only_the_drawn_chart_types_are_registered(): void
    {
        // Comments stripped: the file names `chart.js/auto` precisely to say it
        // is not used.
        $runtime = $this->code(file_get_contents(resource_path('js/chart-runtime.js')));

        $this->assertStringNotContainsString('chart.js/auto', $runtime, 'The auto entry registers everything.');
        $this->assertStringNotContainsString('registerables', $runtime);

        foreach (['Doughnut', 'Radar', 'Polar', 'Bubble', 'TimeScale', 'RadialLinear'] as $unused) {
            $this->assertStringNotContainsString(
                $unused,
                $runtime,
                "{$unused} is never drawn and should not be shipped.",
            );
        }

        foreach (['LineController', 'BarController', 'CategoryScale', 'LinearScale'] as $needed) {
            $this->assertStringContainsString($needed, $runtime);
        }
    }

    public function test_a_chart_failure_is_reported_rather_than_swallowed(): void
    {
        $script = file_get_contents(resource_path('js/charts.js'));

        // An earlier handler caught the import and did nothing. A configuration
        // error inside Chart.js then propagated into it, the canvases were
        // removed, and the page showed two empty frames with no error anywhere —
        // console, log or screen — which cost a long time to trace back.
        $this->assertMatchesRegularExpression(
            '/\.catch\(\(error\)\s*=>\s*\{[\s\S]*?console\.error/',
            $script,
            'A chart that cannot be drawn must say so.',
        );
    }

    public function test_the_monthly_series_covers_every_month_even_when_silent(): void
    {
        $series = app(FinanceService::class)->monthlyOperatingSeries(6);

        $this->assertCount(6, $series['labels']);
        $this->assertCount(6, $series['revenue']);
        $this->assertCount(6, $series['expense']);

        // A gap would say "nothing to show"; a zero says "nothing happened",
        // and for a farm a month with no invoice raised is worth noticing.
        foreach ($series['revenue'] as $value) {
            $this->assertIsFloat($value);
            $this->assertGreaterThanOrEqual(0, $value);
        }
    }

    public function test_the_monthly_series_runs_on_sqlite_as_well_as_mysql(): void
    {
        // The first version grouped by `DATE_FORMAT`, which is MySQL-only, so it
        // worked in production and failed in every test.
        $this->assertStringNotContainsString(
            'DATE_FORMAT',
            $this->code(file_get_contents(app_path('Services/FinanceService.php'))),
            'Date grouping in SQL is not portable, and the suite runs on SQLite.',
        );
    }

    /**
     * A source file with its comments removed.
     *
     * Several of the rules above are about code, and the files explain at length
     * why a construct is avoided — naming it in the explanation. Asserting
     * against the raw file then matches the prose and fails for the wrong
     * reason, which is worse than not asserting at all: the check looks like it
     * is guarding something it is not.
     */
    private function code(string $source): string
    {
        return trim((string) preg_replace(
            ['#/\*.*?\*/#s', '#//[^\n]*#', '#^\s*\*[^\n]*$#m'],
            '',
            $source,
        ));
    }

    /**
     * Render the component on its own, so the rules above can be checked without
     * arranging a dashboard that happens to produce the numbers required.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function renderChart(array $attributes): string
    {
        $view = view('components.chart', $attributes);

        return $view->render();
    }
}
