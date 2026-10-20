<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filters without a page load.
 *
 * Every filter bar is a GET form. Applying one used to replace the whole
 * document, which re-downloads the shell, replays the page entrance and hands
 * the change to the cross-document fade. Only the content actually changes, so
 * the entry point fetches the same URL and swaps `#page-content`.
 *
 * These tests pin the contract on both sides: the page must expose the markers
 * the script looks for, and the script must keep the server as the only thing
 * that decides what a filter means.
 */
class LiveFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    public function test_the_filter_bar_and_page_content_are_marked_for_live_updates(): void
    {
        $html = $this->get(route('invoices.index', ['status' => 'unpaid']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<form[^>]*data-live-filter[^>]*>/s',
            $html,
            'The filter form is not marked for in-place updates.',
        );

        $this->assertStringContainsString('id="page-content"', $html);
        $this->assertStringContainsString('data-live-filter-message="', $html);
        $this->assertStringContainsString('id="live-filter-status"', $html);
        $this->assertStringContainsString('id="live-filter-progress"', $html);
        $this->assertStringContainsString('data-live-filter-reset', $html);
    }

    public function test_the_clear_link_only_exists_when_there_is_something_to_clear(): void
    {
        $clean = $this->get(route('invoices.index'))->assertOk()->getContent();
        $filtered = $this->get(route('invoices.index', ['status' => 'unpaid']))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-live-filter-reset', $clean);
        $this->assertStringContainsString('data-live-filter-reset', $filtered);
    }

    public function test_the_entry_point_fetches_and_swaps_the_content(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        foreach ([
            'form[data-live-filter]',
            "getElementById('page-content')",
            'content.innerHTML = incoming.innerHTML',
            'new DOMParser()',
            'AbortController',
            'history.pushState',
            'history.replaceState',
            "cache: 'no-store'",
            "'aria-busy'",
            'document.title = page.title',
        ] as $needle) {
            $this->assertStringContainsString($needle, $script);
        }

        // A GET form must skip the duplicate-submit guard. That guard disables
        // the pressed button, and a live filter never reloads the page to give
        // it back — the Apply button would stay disabled forever.
        $this->assertStringContainsString(
            "form.method.toLowerCase() === 'get'",
            $script,
            'The submit guard would disable the Apply button on a live filter.',
        );
    }

    public function test_a_live_swap_does_not_replay_the_page_entrance(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/#page-content\[data-live-filtered\]\s+\.motion-rows\s*>\s*\*\s*\{\s*animation:\s*none;/',
            $css,
            'A swapped list must not replay the row stagger.',
        );

        $this->assertStringContainsString('.live-filter-progress', $css);
    }
}
