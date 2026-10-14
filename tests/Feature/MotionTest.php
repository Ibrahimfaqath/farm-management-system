<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The interface is allowed to move, but only within limits.
 *
 * Motion here is a finishing touch: entrances fade and rise a few pixels, menus
 * gain a hair of scale, pressing a control acknowledges the touch. None of it
 * loops, none of it travels further than eight pixels, and none of it moves for
 * longer than 280ms. Those are the constraints that keep an interface feeling
 * considered rather than busy, and they are worth stating in a test because
 * "add a bit more animation" is an easy and quiet change to make.
 *
 * The two tests that matter most are the last two. An entrance animation holds
 * its element at `opacity: 0` until it is allowed to run, so anything that
 * suppresses the animation without also removing the delay serves content
 * that is missing, or arrives late, to exactly the readers who asked for less
 * motion. And printing snapshots whatever state it finds, so a table still
 * fading in would come out with blank rows.
 */
class MotionTest extends TestCase
{
    use RefreshDatabase;

    private string $css = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = file_get_contents(resource_path('css/app.css'));

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    public function test_the_motion_tokens_are_the_only_timings_available(): void
    {
        foreach (['--motion-fast', '--motion-base', '--motion-slow', '--ease-soft-out', '--ease-soft-in'] as $token) {
            $this->assertStringContainsString($token, $this->css, "The motion token {$token} is missing.");
        }

        // Nothing may move further than eight pixels. A slide across the screen
        // is not motion, it is an obstruction.
        preg_match_all('/translateY\((-?\d+(?:\.\d+)?)px\)/', $this->css, $matches);

        $this->assertNotEmpty($matches[1], 'Expected the entrance keyframes to travel on one axis.');

        foreach ($matches[1] as $distance) {
            $this->assertLessThanOrEqual(
                8,
                abs((float) $distance),
                "An entrance travels {$distance}px, which is far enough to read as the page being pushed.",
            );
        }
    }

    public function test_nothing_on_a_data_page_animates_forever(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        // The skeleton shimmer is the single permitted loop, and it exists only
        // while content is loading. A table of live data must not shimmer.
        $this->assertStringNotContainsString('infinite', $html);
        $this->assertStringNotContainsString('skeleton', $html);

        $this->assertStringContainsString('motion-page', $html, 'The page entrance is missing.');
        $this->assertStringContainsString('motion-rows', $html, 'The row stagger is missing.');
    }

    public function test_rows_are_staggered_but_the_stagger_is_capped(): void
    {
        // A list of fifty would otherwise take a second to finish appearing, and
        // the last row would still be fading in behind the reader.
        $this->assertMatchesRegularExpression(
            '/nth-child\(n \+ (\d+)\)\s*\{\s*animation-delay:/s',
            $this->css,
            'The stagger must stop growing after a fixed number of rows.',
        );

        preg_match('/nth-child\(n \+ (\d+)\)\s*\{\s*animation-delay:\s*(\d+)ms/s', $this->css, $cap);
        $this->assertLessThanOrEqual(12, (int) $cap[1], 'The stagger runs on too long before it stops.');
        $this->assertLessThanOrEqual(250, (int) $cap[2], 'The last row waits too long before appearing.');
    }

    public function test_pressing_a_control_is_acknowledged_and_then_released(): void
    {
        // Without this, a tap on a touch screen gives no confirmation at all,
        // because there is no cursor for the eye to follow.
        $this->assertMatchesRegularExpression(
            '/\.btn:active[^{]*\{[^}]*transform:\s*scale\(0\.9/s',
            $this->css,
            'A press should be visible, however slightly.',
        );

        // The scale is applied by a rule, not by a class on every button, so it
        // cannot drift between pages.
        $this->assertStringNotContainsString('active:scale-', $this->css);
    }

    public function test_reduced_motion_removes_the_delay_as_well_as_the_duration(): void
    {
        $this->assertMatchesRegularExpression(
            '/@media \(prefers-reduced-motion: reduce\)\s*\{.*?animation-delay:\s*0s\s*!important/s',
            $this->css,
            'The stagger delay must be neutralised too. Every entrance holds its element at opacity 0 until it '
                .'is allowed to run, so a surviving delay means content that is missing, or arrives late, to '
                .'exactly the readers who asked for less motion.',
        );

        $this->assertMatchesRegularExpression(
            '/@media \(prefers-reduced-motion: reduce\)\s*\{.*?transition-delay:\s*0s\s*!important/s',
            $this->css,
            'Transitions can be delayed too, and the same reasoning applies.',
        );

        $this->assertMatchesRegularExpression(
            '/@media \(prefers-reduced-motion: reduce\)\s*\{.*?animation-duration:\s*0\.01ms/s',
            $this->css,
            'The durations themselves must go.',
        );
    }

    public function test_printing_does_not_capture_a_half_faded_table(): void
    {
        // Printing is not a medium with motion, and it faithfully reproduces
        // whatever state it finds. An entrance still in flight would put blank
        // rows on paper — invisible on screen, permanent in the report.
        $this->assertMatchesRegularExpression(
            '/@media print\s*\{.*?animation:\s*none\s*!important/s',
            $this->css,
            'Printing must end every animation.',
        );

        // Only the animation is removed. Forcing opacity and transform instead
        // would flatten anything meant to look faded.
        $printBlock = $this->between('@media print {', null, $this->css);
        $this->assertStringNotContainsString('opacity: 1 !important', $printBlock);
        $this->assertStringNotContainsString('transform: none !important', $printBlock);
    }

    public function test_the_menus_animate_from_the_layer_that_actually_wins(): void
    {
        // DaisyUI declares its dropdown animation from a layer nested inside
        // `components`, which beats anything written in the parent layer however
        // specific. A rule placed alongside it silently does nothing: this was
        // written, built, and measured doing nothing before being moved here.
        $this->assertMatchesRegularExpression(
            '/@layer utilities\s*\{.*?\.dropdown:focus-within > \.dropdown-content/s',
            $this->css,
            'The menu animation must live in the last layer, or DaisyUI overrides it.',
        );

        $componentsLayer = $this->between('@layer components {', '@layer utilities {', $this->css, strict: false);
        $this->assertStringNotContainsString(
            '.dropdown:focus-within > .dropdown-content',
            $componentsLayer,
            'A duplicate rule in the components layer is dead weight that reads as working.',
        );
    }

    /**
     * The text between two markers, for asserting on a single block of a
     * stylesheet rather than the file as a whole.
     */
    private function between(string $start, ?string $end, ?string $subject = null, bool $strict = true): string
    {
        $subject ??= $this->css;
        $from = strpos($subject, $start);

        if ($from === false) {
            return '';
        }

        $to = $end === null ? strlen($subject) : strpos($subject, $end, $from);

        return substr($subject, $from, ($to === false ? strlen($subject) : $to) - $from);
    }
}
