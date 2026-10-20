<?php

namespace Tests\Feature;

use App\Support\Locale;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The language control in the account menu.
 *
 * It used to be a stack of rows, each a separate form holding a button that
 * was only as wide as its own text. The `li` and the form measured 230px while
 * the buttons measured 62px and 66px, so up to 156px to the right of each label
 * did nothing at all when clicked. It is now one form holding two buttons that
 * each fill half of a segmented control, so there is no dead space and no
 * scrolling to find the second option.
 */
class LanguageControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    private function menuHtml(): string
    {
        // Rendered through Blade so `$attributes` exists, exactly as the
        // layout renders it.
        return Blade::render('<x-account-menu />');
    }

    public function test_both_options_share_one_form(): void
    {
        $html = $this->menuHtml();

        // One form, not one per option.
        $this->assertSame(
            1,
            substr_count($html, 'action="'.route('locale.update').'"'),
            'The language control should be a single form.',
        );

        // The value moved from a hidden input onto the button itself, which is
        // what lets one form offer two options.
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*type="hidden"[^>]*name="locale"/',
            $html,
            'Each option still carries its own hidden input.',
        );

        // Both buttons now name the field, which is the whole point.
        $this->assertSame(
            2,
            substr_count($html, 'name="locale"'),
            'Each option should send the field from its own button.',
        );
    }

    public function test_each_option_is_a_named_submit_button(): void
    {
        $html = $this->menuHtml();

        foreach (Locale::SUPPORTED as $code) {
            $this->assertMatchesRegularExpression(
                '/<button[^>]*type="submit"[^>]*name="locale"[^>]*value="'.$code.'"/',
                $html,
                "The {$code} option is not a submit button carrying its own value.",
            );
        }
    }

    public function test_both_options_are_shown_at_once(): void
    {
        $html = $this->menuHtml();

        // A list hides the alternative behind the need to know it exists.
        $this->assertStringContainsString(__('Indonesian'), $html);
        $this->assertStringContainsString('English', $html);
    }

    public function test_the_panel_uses_one_row_shape(): void
    {
        $html = $this->menuHtml();

        // `menu-none` was written on six elements and defined nowhere, so the
        // rows it was meant to neutralise kept DaisyUI's menu padding *and* its
        // hover fill — the identity block lit up like a button. The panel is no
        // longer a menu widget, so there are no internals left to undo.
        $this->assertStringNotContainsString('menu-none', $html);
        $this->assertStringNotContainsString('<ul', $html);

        // Settings and Log out share one row class: same left inset, same 40px
        // height, same hover. Writing those utilities per row is how they
        // drifted to 10px and 12px before.
        $this->assertSame(2, substr_count($html, 'account-menu-row'));

        // The identity answers "which account is this?" even on a phone, where
        // the trigger hides the name and keeps only the avatar.
        $user = auth()->user();
        $this->assertStringContainsString($user->email, $html);
        $this->assertStringContainsString($user->role_label, $html);
    }

    public function test_the_segments_are_wide_enough_to_click(): void
    {
        $html = $this->menuHtml();

        // The old markup sized the button to its label; this is what makes each
        // half of the control a target instead of a label with dead space. The
        // track fills the panel and each segment takes an equal share of it,
        // whatever the two labels happen to be.
        $this->assertStringContainsString('tabs tabs-box w-full', $html);
        $this->assertSame(
            2,
            substr_count($html, 'class="tab flex-1 basis-0 text-xs"'),
            'Both segments should fill a half of the control.',
        );
    }

    public function test_the_active_option_is_marked_and_the_others_are_not(): void
    {
        $html = $this->menuHtml();

        $this->assertSame(1, substr_count($html, 'aria-current="true"'), 'Exactly one option is current.');

        // `tabs-box` lifts the segment carrying `aria-current` onto base-100.
        // The old `btn-active` fill was base-200, which on a white panel read
        // as a disabled button rather than a selected one.
        $this->assertStringContainsString('tabs tabs-box', $html);

        // The inactive option is still a real control, not a disabled remnant.
        $this->assertStringNotContainsString('disabled', $html);
    }

    public function test_switching_a_language_stores_it_in_the_session(): void
    {
        // The control posts a real PUT through the `@method` override.
        $this->put(route('locale.update'), ['locale' => 'en'])->assertRedirect();

        $this->assertSame('en', session('locale'));
    }

    public function test_an_unknown_language_is_rejected(): void
    {
        $this->from(route('dashboard'))
            ->put(route('locale.update'), ['locale' => 'fr'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('locale');

        $this->assertNull(session('locale'));
    }

    public function test_the_inactive_segment_answers_the_pointer(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', file_get_contents(resource_path('css/app.css')));

        // `tabs-box` styles the current segment and leaves the other flat, so a
        // hover only raised its text alpha — a change the black theme cannot
        // show, because both of its states are already near-white. The inactive
        // segment now takes the same tint as the header's buttons.
        $this->assertMatchesRegularExpression(
            '/\.account-menu \.tab:not\(\[aria-current=\'true\'\]\):hover[^{]*\{[^}]*background-color:\s*color-mix\(/s',
            $css,
            'Hovering the inactive language segment gives no visible surface.',
        );

        // And it fades in at the header's pace rather than snapping.
        $this->assertMatchesRegularExpression(
            '/\.account-menu \.tab \{[^}]*transition:[^}]*var\(--motion-fast\)/s',
            $css,
            'The segment should fade at the same pace as the buttons beside it.',
        );
    }

    public function test_every_page_keeps_a_reachable_language_control(): void
    {
        $html = $this->actingAs($this->owner())->get(route('animals.index'))->assertOk()->getContent();

        $this->assertStringContainsString('name="locale"', $html);
        $this->assertStringContainsString(route('locale.update'), $html);
    }
}
