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

    public function test_the_counting_rules_hold_on_every_shape_the_server_emits(): void
    {
        $script = <<<'JS'
            import { readValue, format } from '__COUNTERS__';

            const cases = [
                ['254.8 L', 254.8, 1], ['9,650 L', 9650, 0], ['45', 45, 0], ['1', 1, 0],
                ['Rp8.978.000', 8978000, 0], ['Rp5.292.000', 5292000, 0],
                ['Rp-33.318.000', -33318000, 0], ['Rp-8.320.000', -8320000, 0],
                ['-29.9%', -29.9, 1], ['1.234.567', 1234567, 0], ['0.5', 0.5, 1],
                ['1,250,000', 1250000, 0], ['12,5', 12.5, 1], ['253.9', 253.9, 1],
                ['1.000', 1000, 0], ['Rp0', 0, 0],
            ];

            const wrong = [];

            for (const [text, wantValue, wantDecimals] of cases) {
                const parsed = readValue(text);

                if (! parsed) {
                    wrong.push(`${text}: not parsed`);
                    continue;
                }

                if (Math.abs(parsed.value - wantValue) > 1e-6) {
                    wrong.push(`${text}: read ${parsed.value}, wanted ${wantValue}`);
                }

                if (parsed.decimals !== wantDecimals) {
                    wrong.push(`${text}: ${parsed.decimals} decimals, wanted ${wantDecimals}`);
                }

                // Writing the parsed value back has to reproduce the digits it
                // came from, or the figure jumps notation as it settles.
                const token = text.match(/-?\d[\d.,]*\d|-?\d/)[0];

                if (format(parsed.value, parsed) !== token) {
                    wrong.push(`${text}: rewritten as "${format(parsed.value, parsed)}"`);
                }
            }

            console.log(JSON.stringify(wrong));
            JS;

        // Substituted rather than interpolated: the cases contain a literal
        // `%`, which `sprintf` would read as a placeholder.
        $program = str_replace('__COUNTERS__', resource_path('js/counters.js'), $script);

        $output = shell_exec(
            'node --input-type=module -e '.escapeshellarg($program).' 2>&1'
        );

        $this->assertNotNull($output, 'Node is unavailable, so the counting rules could not be checked.');

        $wrong = json_decode(trim((string) $output), true);

        $this->assertIsArray($wrong, "The check did not return a list of failures: {$output}");
        $this->assertSame([], $wrong, 'Counting rules failed: '.implode('; ', $wrong));
    }

    public function test_the_counters_are_wired_into_the_entry_point(): void
    {
        $entry = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("import { initCounters } from './counters.js'", $entry);
        $this->assertMatchesRegularExpression('/initCounters\(\);/', $entry);

        // Static would put the module on every page; it is small enough that
        // this is about staying inside the existing bundle rather than a new one.
        $this->assertStringNotContainsString("import('./counters.js')", $entry);
    }

    public function test_reduced_motion_is_honoured_before_anything_is_animated(): void
    {
        $script = $this->code(file_get_contents(resource_path('js/counters.js')));

        // The check has to come before the elements are read, or the count-up is
        // already scheduled by the time the preference is noticed.
        $this->assertMatchesRegularExpression(
            "/matchMedia\('\(prefers-reduced-motion: reduce\)'\)\.matches\) \{\s*return;/",
            $script,
            'Reduced motion must skip the counters outright.',
        );
    }

    public function test_the_count_lands_on_the_servers_own_string(): void
    {
        $script = $this->code(file_get_contents(resource_path('js/counters.js')));

        // The last write restores the original text rather than a formatted
        // value, so an interrupted count — a navigation, a hidden tab, a dropped
        // frame — leaves the correct figure rather than a rounded approximation.
        $this->assertMatchesRegularExpression(
            '/element\.textContent = text;/',
            $script,
            'The count-up must finish on the server\'s own string.',
        );
    }

    public function test_the_feedback_rules_exist(): void
    {
        // The press rule was already here — `.btn:active { transform:
        // scale(0.97) }` — and a second one was added beside it using the
        // `scale` property instead. Both applied at once, so every button
        // shrank by 0.97 twice and the tap looked heavier than intended.
        // Only the tick and the badge are new.
        $this->assertMatchesRegularExpression(
            '/\.checkbox:checked[^{]*\{[^}]*animation: motion-tick/s',
            $this->css,
        );
        $this->assertStringContainsString('motion-badge-in', $this->css);

        // No second press rule hiding behind the other property.
        $this->assertDoesNotMatchRegularExpression(
            '/\.btn:active[^{]*\{[^}]*[^a-z]scale:\s*0\./s',
            $this->css,
            'A second `.btn:active` rule scales the press twice.',
        );

        $this->assertStringContainsString('@keyframes motion-tick', $this->css);
        $this->assertStringContainsString('@keyframes motion-badge-in', $this->css);
    }

    public function test_the_notification_badge_carries_the_class(): void
    {
        $html = $this->get(route('animals.index'))->assertOk()->getContent();

        $this->assertStringContainsString('notification-badge', $html);
    }

    /**
     * The body of one CSS rule, by selector.
     */
    private function rule(string $selector): string
    {
        $escaped = preg_quote($selector, '/');

        return preg_match("/{$escaped}[^{]*\{([^}]*)\}/s", $this->css, $m) === 1 ? $m[1] : '';
    }

    /**
     * A source file with its comments removed.
     */
    private function code(string $source): string
    {
        return trim((string) preg_replace(
            ['#/\*.*?\*/#s', '#//[^\n]*#', '#^\s*\*[^\n]*$#m'],
            '',
            $source,
        ));
    }

    public function test_the_badge_swell_does_not_leave_a_transform_resident(): void
    {
        // This stylesheet has a bug on record where a fill-mode left `transform`
        // on an element, which made it the containing block for every
        // `position: fixed` descendant and threw the dropdowns across the page.
        // `backwards` applies the from-state before the run and releases
        // everything at the end; `both` would keep the final transform.
        $badge = $this->rule('.notification-badge');

        $this->assertStringContainsString('motion-badge-in', $badge);
        $this->assertStringNotContainsString(
            'both',
            $badge,
            'A `both` fill-mode would keep the final transform resident.',
        );
    }
}
