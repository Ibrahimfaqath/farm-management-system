<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Two regressions in the header and the tables.
 *
 * Both came from the same mistake: putting a display utility on an element
 * whose component already owns one.
 *
 * DaisyUI hides a closed `.dropdown-content` with `display: none`. A `flex`
 * class on that element sits in the utilities layer, which is emitted after the
 * component layer, so it won — the notification panel stayed laid out at
 * `opacity: 0`, covering the page, and clicking anywhere inside its invisible
 * box opened it. The header behaved as though the whole bar were a trigger.
 *
 * The action column was right-aligned inside a table that runs flush to the
 * card's rounded edge, which left the heading and the ⋮ button pressed into the
 * corner.
 */
class HeaderAndTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    #[DataProvider('dropdowns')]
    public function test_a_closed_panel_is_removed_from_the_layout(string $name): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<div tabindex="0" role="menu"\s+class="dropdown-content[^"]*"/s',
            $html,
            'The notification panel no longer carries `dropdown-content`.',
        );

        if (! str_contains($html, $name)) {
            $this->markTestSkipped("{$name} is not on this page.");
        }
    }

    /**
     * @return list<array{0: string}>
     */
    public static function dropdowns(): array
    {
        return [
            'notification panel' => ['dropdown-content z-50 mt-2 w-80'],
            'account menu' => ['menu dropdown-content z-50 mt-2 w-72'],
        ];
    }

    public function test_no_header_panel_declares_its_own_display(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // Any display utility here overrides DaisyUI's `display: none` and puts
        // an invisible panel back over the page, which is what made a click
        // anywhere in that area open the notifications.
        preg_match_all('/<div[^>]*class="(dropdown-content[^"]*)"/', $html, $matches);

        $this->assertNotEmpty($matches[1], 'No dropdown panels were found in the header.');

        foreach ($matches[1] as $classes) {
            $tokens = preg_split('/\s+/', $classes) ?: [];

            foreach (['flex', 'block', 'grid', 'inline-flex', 'table', 'contents'] as $display) {
                $this->assertNotContains(
                    $display,
                    $tokens,
                    "A `{$display}` utility on a dropdown-content overrides the component's `display: none`.",
                );
            }
        }
    }

    public function test_the_bell_is_the_only_way_into_the_panel_from_the_header(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // Scoped to the header: every table row carries its own action menu
        // with the same attribute, so a count across the whole page would be
        // however many rows the list happens to have.
        preg_match('/<header.*?<\/header>/s', $html, $m);
        $header = $m[0] ?? '';

        $this->assertNotEmpty($header, 'No header was rendered.');

        // Only two things in the header open a panel. The bell declares a menu
        // with `aria-haspopup`; the account row is a disclosure holding a form,
        // so it names the panel with `aria-controls` instead. Counting one
        // attribute no longer describes both.
        $this->assertSame(
            1,
            substr_count($header, 'aria-haspopup="true"'),
            'The bell is the only menu trigger in the header.',
        );

        $this->assertSame(
            1,
            substr_count($header, 'aria-controls="account-menu-panel"'),
            'The account row is the only disclosure in the header.',
        );

        $this->assertSame(
            2,
            substr_count($header, 'aria-expanded='),
            'Only the bell and the account row should report a panel state.',
        );
    }

    public function test_the_action_column_is_centred_and_sized(): void
    {
        $css = $this->withoutComments(file_get_contents(resource_path('css/app.css')));

        // Comments stripped: the block above explains this rule at length and
        // names `text-right` while saying it is gone, so a raw search matches the
        // prose and passes or fails for the wrong reason.
        $this->assertMatchesRegularExpression(
            '/\.app-table \.row-actions \{[^}]*text-center/s',
            $css,
            'The action column is right-aligned against a flush table edge and reads as jammed into the corner.',
        );

        $this->assertMatchesRegularExpression(
            '/\.app-table \.row-actions \{[^}]*width: 4\.5rem/s',
            $css,
            'The column needs a width of its own so the heading sits at the same distance on every table.',
        );
    }

    public function test_every_list_page_marks_its_action_column_the_same_way(): void
    {
        $headers = glob(resource_path('views/*/index.blade.php')) ?: [];

        $marked = 0;

        foreach ($headers as $file) {
            $source = file_get_contents($file);

            if (! str_contains($source, 'x-actions-menu')) {
                continue;
            }

            $marked++;

            $this->assertStringContainsString(
                '<th class="row-actions">',
                $source,
                basename(dirname($file)).'/index.blade.php has an actions menu but no marked heading.',
            );
        }

        $this->assertGreaterThan(10, $marked, 'Far fewer action columns were found than expected.');
    }

    /**
     * A stylesheet with its comments removed.
     */
    private function withoutComments(string $css): string
    {
        return (string) preg_replace('#/\*.*?\*/#s', '', $css);
    }
}
