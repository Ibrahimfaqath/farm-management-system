<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Guards the UI contracts that were silently broken before: duplicate class
 * attributes on form controls, theme markup in the layout, and the absence of
 * inline JS handlers that should live in app.js.
 */
class UiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_controls_emit_exactly_one_class_attribute(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
        $this->app['view']->share('errors', new ViewErrorBag);

        $html = Blade::render(
            '<x-form.input name="a" label="A" class="sm:col-span-2" minlength="8" />'
            .'<x-form.select name="b" label="B" :options="[1 => 1]" class="lg:col-span-2" />'
            .'<x-form.textarea name="c" label="C" rows="2" maxlength="10" />'
            .'<x-form.money name="d" label="D" />'
        );

        // A control must not carry two class attributes: the second one is
        // dropped by the HTML parser, which silently removed the DaisyUI
        // styling from every input that took an extra attribute.
        preg_match_all('/<(input|select|textarea)\b[^>]*>/', $html, $controls);

        $this->assertNotEmpty($controls[0], 'No controls were rendered.');

        foreach ($controls[0] as $control) {
            $this->assertSame(
                1,
                preg_match_all('/\bclass=/', $control),
                "Control renders a duplicated class attribute: {$control}"
            );
        }

        // The wrapper keeps the grid span, the control keeps the DaisyUI
        // styling, and the extra attributes land on the control only.
        $this->assertStringContainsString('<div class="form-field w-full sm:col-span-2">', $html);
        $this->assertStringContainsString('<div class="form-field w-full lg:col-span-2">', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*class="[^"]*\binput\b[^"]*"/', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*class="[^"]*\bselect\b[^"]*"/', $html);
        $this->assertStringContainsString('minlength="8"', $html);
        $this->assertStringContainsString('min="0"', $html, 'Money inputs keep their minimum.');
        $this->assertStringNotContainsString('<div class="form-field w-full" minlength', $html);
    }

    public function test_layout_ships_both_themes_and_a_switcher(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = $this->actingAs($this->owner())->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        // A single toggle button, not a two-option menu: with only two themes a
        // menu is a click that can only reach one other state.
        $this->assertStringContainsString('data-theme-toggle-click', $html, 'Theme toggle is missing.');
        $this->assertStringContainsString('aria-pressed="false"', $html);
        $this->assertStringContainsString('data-label-dark', $html);
        $this->assertStringContainsString('data-label-light', $html);
        $this->assertStringNotContainsString('name="farm-theme"', $html, 'The theme menu should be gone.');
        $this->assertStringContainsString("localStorage.getItem('farm-theme')", $html);
        $this->assertStringContainsString('data-theme', $html);
    }

    public function test_pages_keep_dialog_markup_and_drop_inline_handlers(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = $this->actingAs($this->owner())->get(route('inventory.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-modal-open="adjust-modal"', $html);
        $this->assertStringNotContainsString("getElementById('adjust-modal').showModal()", $html);
    }

    /**
     * The theme button must paint one glyph, never two.
     *
     * The glyphs were switched with a `hidden` class, which did nothing. The
     * icon component hardcodes `inline-block` when a size is given, and both
     * are `display` utilities in the same layer — Tailwind emits
     * `.inline-block` after `.hidden`, so it always won and the sun and the
     * moon appeared together.
     */
    public function test_the_theme_button_paints_a_single_glyph(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = $this->actingAs($this->owner())->get(route('dashboard'))->assertOk()->getContent();

        // Both glyphs ship; visibility is decided by CSS keyed off the
        // attribute the inline head script sets before paint.
        $this->assertSame(2, substr_count($html, 'data-theme-icon='), 'Expected a sun and a moon glyph.');

        // The class-based approach must not come back.
        $this->assertStringNotContainsString('theme-icon-light', $html);
        $this->assertStringNotContainsString('theme-icon-dark', $html);

        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString("[data-theme-icon='sun']", $css);
        $this->assertStringContainsString("[data-theme-icon='moon']", $css);
        $this->assertStringContainsString(":root[data-theme='black'] [data-theme-icon='sun']", $css);
        $this->assertStringContainsString(":root[data-theme='black'] [data-theme-icon='moon']", $css);

        // One rule must hide each glyph, or the pair paints together.
        $this->assertMatchesRegularExpression(
            "/\[data-theme-icon='moon'\]\s*\{\s*display:\s*none/",
            $css,
            'The moon is never hidden in the light theme.',
        );
    }

    /**
     * A submit button that carries its own value must survive the duplicate
     * guard.
     *
     * `initSubmitGuards` disables the pressed control on submit, and a disabled
     * control is left out of the form data set. The language control posts
     * `locale` from the button itself, so the guard used to strip the field and
     * the server answered "The locale field is required."
     */
    public function test_the_submit_guard_preserves_a_named_submit_button_value(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('event.submitter', $js, 'The guard still guesses which button was pressed.');
        $this->assertStringContainsString('control.name', $js, 'A named submit button would be disabled without carrying its value.');
        $this->assertStringContainsString('data-submit-guard-carrier', $js, 'Stale carriers are never cleared.');
    }
}
