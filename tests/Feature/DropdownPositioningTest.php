<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Dropdown panels are positioned by script, in viewport coordinates.
 *
 * That only works while the viewport really is the containing block, and
 * `animation-fill-mode: both` quietly took it away.
 *
 * Every entrance keyframe ends on `transform: none`, but a keyframe value is
 * interpolated as a matrix, so after the animation finished the animated element
 * kept `transform: matrix(1, 0, 0, 1, 0, 0)` written on it. An identity matrix
 * moves nothing — except that any element with a transform at all becomes the
 * containing block for its `position: fixed` descendants.
 *
 * So `left: 1205px`, measured from the viewport, was applied relative to the
 * table row sitting at x=277. Measured, the row action menu rendered at 1482px
 * on a 1440px screen: off the right edge entirely, not merely misaligned. Every
 * dropdown in the application was affected — the row menus and the filter
 * listboxes alike.
 *
 * `backwards` is the correct fill mode and gives an identical animation: it
 * holds the opening frame through the stagger delay, which is what stops a row
 * appearing before its turn, and holds nothing afterwards, because an element's
 * ordinary state is already opacity 1 and no transform.
 */
class DropdownPositioningTest extends TestCase
{
    private string $css = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = file_get_contents(resource_path('css/app.css'));
    }

    public function test_no_entrance_animation_holds_its_final_frame(): void
    {
        // The assertion is about the absence of `both`, not the presence of
        // `backwards`: any mode that keeps forwarding is the same defect.
        preg_match_all('/animation:\s*motion-[a-z-]+[^;]*;/', $this->css, $declarations);

        $this->assertNotEmpty($declarations[0]);

        foreach ($declarations[0] as $declaration) {
            $this->assertStringNotContainsString(
                'both',
                $declaration,
                'Fill mode `both` leaves an identity matrix on the element, which captures every fixed '
                    .'dropdown inside it: {$declaration}',
            );
        }
    }

    public function test_the_stagger_still_needs_a_backwards_fill(): void
    {
        // Removing the fill mode altogether would fix the dropdowns and break
        // the animation: a row with a 210ms delay would sit at opacity 1 during
        // that delay and then flash away, so the stagger would read as a glitch.
        $this->assertMatchesRegularExpression(
            '/animation: motion-row-in[^;]*backwards;/s',
            $this->css,
            'The row stagger must hold its opening frame through the delay.',
        );

        $this->assertMatchesRegularExpression(
            '/nth-child\(n \+ (\d+)\)\s*\{\s*animation-delay:\s*(\d+)ms/s',
            $this->css,
            'A stagger with no delay would not need a fill mode and would not be a stagger.',
        );
    }

    public function test_the_keyframes_end_on_an_untransformed_state(): void
    {
        // If the closing frame were itself a transform rather than `none`, the
        // residue would be a real offset rather than an identity matrix, and
        // the dropdowns would be displaced by an amount that changed with the
        // animation rather than by a fixed amount.
        foreach (['motion-page-in', 'motion-row-in'] as $name) {
            $this->assertMatchesRegularExpression(
                '/@keyframes '.$name.'\s*\{.*?to\s*\{[^}]*transform:\s*none/s',
                $this->css,
                "{$name} should finish on `transform: none`.",
            );
        }
    }

    public function test_the_panels_are_pinned_to_the_viewport_and_measured_there(): void
    {
        // The script's own contract: read the anchor in viewport coordinates,
        // place the panel in viewport coordinates, and only then let the browser
        // clip and flip it.
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('getBoundingClientRect', $script, 'The anchor must be measured, not guessed.');
        $this->assertStringContainsString("panel.style.position = 'fixed'", $script);

        // Anything measuring in document coordinates while placing in viewport
        // coordinates reintroduces the same skew by another route.
        $this->assertStringNotContainsString(
            'getBoundingClientRect().top + window.scrollY',
            $script,
            'The anchor is read in viewport coordinates, so it must not have scroll added back.',
        );
    }

    public function test_positioning_is_reverted_when_a_dropdown_closes(): void
    {
        // The panel keeps inline `position`, `top` and `left`. Anything left
        // behind would apply to the next open as well, and to the other panel
        // in the same row.
        $script = file_get_contents(resource_path('js/app.js'));

        foreach (['position', 'top', 'left'] as $property) {
            $this->assertMatchesRegularExpression(
                '/panel\.style\.'.$property.'\s*=\s*\'\'/s',
                $script,
                "A closed dropdown must clear its inline {$property}.",
            );
        }
    }
}
