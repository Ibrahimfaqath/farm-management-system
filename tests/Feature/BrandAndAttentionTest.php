<?php

namespace Tests\Feature;

use App\Services\AttentionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The FARM mark, and the dashboard's attention strip.
 *
 * The strip was one card per item in a three-column grid. With ten items that
 * is 400px of a 900px screen, repeating what the header bell already showed on
 * the same screen, and it pushed the production charts 363px below the fold. It
 * is now a single row — but not removed, because a dashboard that looks healthy
 * while seven things are wrong is the same failure the bell had for its whole
 * life, only less visible.
 */
class BrandAndAttentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    #[DataProvider('pages')]
    public function test_the_mark_is_on_the_page(string $path): void
    {
        // `/login` redirects a signed-in visitor, and every test here is one.
        if ($path === '/login') {
            $this->signOut();
        }

        $html = $this->get($path)->assertOk()->getContent();

        $this->assertStringContainsString(
            'logo-mark',
            $html,
            'The mark should stand in for the letter that used to be there.',
        );

        // The green square with an "F" is what it replaced.
        $this->assertStringNotContainsString('text-primary-content">F<', $html);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function pages(): array
    {
        return [
            'sidebar' => ['/animals'],
            'login' => ['/login'],
        ];
    }

    public function test_the_mark_carries_a_text_alternative(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // It is a CSS background on a pseudo-element, so it is invisible to a
        // screen reader unless something names it.
        $this->assertMatchesRegularExpression(
            '/<span class="logo-mark[^"]*" role="img" aria-label="FARM">/',
            $html,
        );
    }

    public function test_both_theme_variants_ship(): void
    {
        $css = $this->withoutComments(file_get_contents(resource_path('css/app.css')));

        // Two supplied files: a black mark for the light theme and a white one
        // for the dark. The black alone would be invisible on the dark theme's
        // near-black surface, so the white is not a recolour of it.
        $this->assertStringContainsString("url('/images/logo-mark-black.png')", $css);
        $this->assertStringContainsString("url('/images/logo-mark-light.png')", $css);

        // The swap has to hang off `data-theme`, which is set before first paint.
        $this->assertMatchesRegularExpression(
            "/\[data-theme='black'\] \.logo-mark::after/",
            $css,
        );
    }

    public function test_the_two_variants_are_the_same_shape(): void
    {
        // The artwork is dark on transparent in one file and light on
        // transparent in the other, so their edges blend toward opposite
        // colours. Measured separately the two marks came out 336x433 and
        // 306x380 — the logo would visibly resize on a theme swap.
        //
        // Read straight from each PNG's IHDR: no image extension is required,
        // and a missing one would otherwise make this the only test in the
        // suite that cannot run.
        foreach (['logo-mark-black.png', 'logo-mark-light.png'] as $name) {
            $header = unpack('Nw/Nh', file_get_contents(public_path("images/{$name}"), false, null, 16, 8));

            $this->assertSame(128, $header['w'], "{$name} is not 128px wide.");
            $this->assertSame(128, $header['h'], "{$name} is not 128px tall.");
        }

        // Equal canvas, and both no heavier than the previous build, so the
        // swap costs one small fetch rather than two.
        $black = filesize(public_path('images/logo-mark-black.png'));
        $white = filesize(public_path('images/logo-mark-light.png'));

        $this->assertLessThan(15 * 1024, $black);
        $this->assertLessThan(15 * 1024, $white);
    }

    public function test_the_mark_is_scaled_by_the_box_not_by_its_own_size(): void
    {
        $css = $this->withoutComments(file_get_contents(resource_path('css/app.css')));

        // `content: url()` draws the image at its intrinsic size and ignores the
        // pseudo-element's box — which put a 128px mark through a 32px slot and
        // over the text beside it.
        $this->assertMatchesRegularExpression(
            '/\.logo-mark::after \{[^}]*content: \'\';[^}]*background-size: contain;/s',
            $css,
        );
    }

    public function test_the_logo_files_are_small_and_have_transparency(): void
    {
        foreach (['logo-mark-black.png', 'logo-mark-light.png'] as $name) {
            $path = public_path("images/{$name}");

            $this->assertFileExists($path);
            $this->assertLessThan(
                15 * 1024,
                filesize($path),
                "{$name} is heavier than a 32px mark needs to be.",
            );

            // The source is black on white with no alpha, so a single copy would
            // be a white square on the dark theme.
            $header = unpack('C*', file_get_contents($path, false, null, 0, 26));
            $this->assertSame(6, $header[26], "{$name} has no alpha channel.");
        }
    }

    public function test_every_page_links_the_favicon(): void
    {
        foreach (['/animals', '/login'] as $path) {
            if ($path === '/login') {
                $this->signOut();
            }

            $html = $this->get($path)->assertOk()->getContent();

            // There was no icon link at all, so every page requested a
            // /favicon.ico that does not exist.
            $this->assertStringContainsString('<link rel="icon" type="image/png"', $html, "No favicon on {$path}.");
        }
    }

    public function test_the_dashboard_strip_shows_a_count_and_a_way_in(): void
    {
        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-open-attention', $html);
        $this->assertStringContainsString(__('See all'), $html);

        // The full list is gone; it was the same content the bell already had.
        $this->assertStringNotContainsString('x-alert-row', $html);
    }

    public function test_the_strip_leads_with_the_most_serious_items(): void
    {
        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $strip = $this->strip($html);

        $this->assertNotNull($strip, 'The attention strip is missing.');

        $items = app(AttentionService::class)->itemsFor($this->owner());
        $errors = array_values(array_filter($items, fn (array $i) => ($i['tone'] ?? '') === 'error'));

        if ($errors !== []) {
            $this->assertStringContainsString(
                $errors[0]['title'],
                $strip,
                'An overdue invoice should never be displaced by a low-stock warning.',
            );
        }
    }

    public function test_the_strip_never_repeats_the_full_list(): void
    {
        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $strip = $this->strip($html);
        $this->assertNotNull($strip);

        // Two headlines at most. The descriptions and the rest live in the bell.
        $this->assertLessThanOrEqual(
            2,
            substr_count($strip, '<a href='),
            'The strip is meant to be one line; the full list belongs in the bell.',
        );
    }

    public function test_nothing_is_rendered_when_there_is_nothing_to_report(): void
    {
        $html = Blade::render(
            '<x-attention-strip :attention-items="$empty" />',
            ['empty' => []],
        );

        // An empty strip used to carry a success row saying so, which pushed
        // the real content down on the one screen where nothing was wrong.
        $this->assertStringNotContainsString('<section', $html);
        $this->assertStringNotContainsString('Nothing requires immediate action', $html);
    }

    public function test_the_strip_accepts_both_an_array_and_a_collection(): void
    {
        $items = collect(app(AttentionService::class)->itemsFor($this->owner()));

        // The controller hands over the array `itemsFor()` returns; the view
        // composer hands the header a collection. Interchanged once already, and
        // the mismatch surfaced as a fatal in the middle of the dashboard.
        foreach ([['array' => $items->all()], ['collection' => $items]] as $case) {
            $html = Blade::render(
                '<x-attention-strip :attention-items="$items" />',
                ['items' => reset($case)],
            );

            $this->assertStringContainsString('data-open-attention', $html, 'Failed for '.key($case).'.');
        }
    }

    public function test_the_rest_count_is_translated(): void
    {
        $dictionary = json_decode(file_get_contents(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR);

        foreach (['See all', 'and :count more', '1 item needs attention|*:count items need attention'] as $key) {
            $this->assertArrayHasKey($key, $dictionary);
            $this->assertNotSame($key, $dictionary[$key]);
        }

        // `trans_choice` with two identical branches never finds a JSON key, so
        // the English source leaked through while the dictionary looked correct.
        $this->assertSame(
            'dan 8 lainnya',
            __('and :count more', ['count' => 8]),
        );
    }

    /**
     * Leave the signed-in state for real.
     *
     * `auth()->logout()` clears the guard but leaves the session id that the
     * next request resumes from, so the redirect guard still saw a guest-less
     * visitor and sent them to the dashboard.
     */
    private function signOut(): void
    {
        $this->post(route('logout'));
        $this->assertGuest();
    }

    private function strip(string $html): ?string
    {
        if (preg_match('/<section class="mb-4">.*?<\/section>/s', $html, $m) !== 1) {
            return null;
        }

        return $m[0];
    }

    /**
     * A stylesheet with its comments removed.
     *
     * The blocks under test explain this exact work and name the selectors, so
     * reading the raw file matches the prose first and reports for the wrong
     * reason.
     */
    private function withoutComments(string $css): string
    {
        return (string) preg_replace('#/\*.*?\*/#s', '', $css);
    }
}
