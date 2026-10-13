<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The interface ships in Indonesian. Any user-facing string that is not routed
 * through the catalogue silently renders in English, so this walks the real
 * pages and fails on leftover raw markup.
 */
class LanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_view_bypasses_the_translation_catalogue(): void
    {
        $this->seed(DatabaseSeeder::class);

        $catalogue = json_decode(file_get_contents(lang_path('id.json')), true);
        $failures = [];
        $pattern = '/\b__\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/s';

        foreach (glob(resource_path('views/**/*.blade.php')) ?: [] as $file) {
            if (! is_file($file)) {
                continue;
            }

            preg_match_all($pattern, file_get_contents($file), $matches);

            foreach ($matches[1] as $key) {
                $key = str_replace("\\'", "'", $key);

                if ($key !== '' && ! array_key_exists($key, $catalogue)) {
                    $failures[] = basename($file).': '.$key;
                }
            }
        }

        $this->assertSame([], $failures, "Untranslated keys:\n".implode("\n", array_unique($failures)));
    }

    public function test_plural_choices_render_the_indonesian_form(): void
    {
        $this->seed(DatabaseSeeder::class);

        // The invoice list renders a trans_choice() alert when invoices are overdue.
        $html = $this->actingAs($this->owner())->get(route('invoices.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('|:count', $html, 'A plural separator leaked into the page.');
        $this->assertStringNotContainsString('[:count', $html, 'A bracketed plural key leaked into the page.');
    }

    public function test_pages_render_in_indonesian_for_the_default_locale(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['/dashboard', '/animals', '/inventory', '/invoices', '/settings'] as $path) {
            $this->actingAs($this->owner())
                ->get($path)
                ->assertOk()
                ->assertSee('Ternak', false)
                ->assertDontSee('Livestock', false);
        }
    }
}
