<?php

namespace Tests\Feature;

use App\Support\Navigation;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_categories_with_multiple_items_are_collapsible_and_the_active_group_is_open(): void
    {
        $owner = $this->owner();
        $groups = Navigation::forUser($owner);
        $collapsibleGroups = array_filter($groups, fn (array $group): bool => count($group['items']) > 1);

        $html = $this->actingAs($owner)
            ->get(route('animals.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            count($collapsibleGroups),
            substr_count($html, '<details'),
            'Only categories with more than one destination should be collapsible.',
        );

        $this->assertSame(
            1,
            preg_match_all('/<details\b[^>]*\bopen\b/', $html),
            'Only the group containing the current page should start open.',
        );

        $this->assertMatchesRegularExpression(
            '/<details\b(?=[^>]*\bopen\b)[^>]*>\s*<summary[^>]*>.*?'.preg_quote(__('Farm'), '/').'.*?<\/summary>/s',
            $html,
            'The Farm category should be open on the livestock page.',
        );

        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('href="'.route('animals.index').'"', $html);
    }

    public function test_single_destination_categories_render_as_direct_links(): void
    {
        $owner = $this->owner();
        $groups = Navigation::forUser($owner);
        $singleGroups = array_filter($groups, fn (array $group): bool => count($group['items']) === 1);

        $html = $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertNotEmpty($singleGroups);

        $document = new DOMDocument;
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);

        foreach ($singleGroups as $group) {
            $item = $group['items'][0];
            $links = $xpath->query(
                '//nav[@data-drawer-nav]/ul[contains(concat(" ", normalize-space(@class), " "), " menu ")]'
                .'/li/a[@href="'.route($item['route']).'"]'
            );

            $this->assertSame(
                1,
                $links->length,
                "The {$item['label']} link should be a direct sidebar item, not a one-item disclosure.",
            );
        }

        $this->assertMatchesRegularExpression(
            '/<a href="'.preg_quote(route('dashboard'), '/').'"\s+class="menu-active"\s+aria-current="page"\s+data-sidebar-active\s*>/',
            $html,
            'The direct Dashboard link should carry the active state.',
        );
    }

    public function test_every_navigation_destination_is_rendered(): void
    {
        $owner = $this->owner();

        $html = $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        foreach (Navigation::forUser($owner) as $group) {
            $this->assertStringContainsString('>'.e($group['label']).'</span>', $html);

            foreach ($group['items'] as $item) {
                $this->assertStringContainsString('href="'.route($item['route']).'"', $html);
            }
        }
    }

    public function test_category_summaries_have_no_group_icon_and_only_one_chevron(): void
    {
        $owner = $this->owner();
        $collapsibleGroups = array_filter(
            Navigation::forUser($owner),
            fn (array $group): bool => count($group['items']) > 1,
        );

        $html = $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        preg_match_all('/<summary\b[^>]*>.*?<\/summary>/s', $html, $matches);

        $this->assertCount(count($collapsibleGroups), $matches[0]);

        foreach ($matches[0] as $summary) {
            $this->assertSame(
                1,
                substr_count($summary, '<svg'),
                'A category summary should contain only the single disclosure chevron, not a group icon.',
            );
            $this->assertSame(1, substr_count($summary, 'data-sidebar-chevron="'));
        }
    }

    public function test_sidebar_hides_the_built_in_menu_chevron(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString(
            '[data-drawer-nav] .menu :where(li > details > summary)::after',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\[data-drawer-nav\][^{]*summary\)::after\s*\{[^}]*content:\s*none/s',
            $css,
        );
    }

    public function test_create_and_edit_pages_keep_their_section_marked_active(): void
    {
        $owner = $this->owner();

        foreach ([
            'animals.create' => 'animals.index',
            'barns.create' => 'barns.index',
            'breeds.create' => 'breeds.index',
            'farms.create' => 'farms.index',
        ] as $formRoute => $indexRoute) {
            $html = $this->actingAs($owner)
                ->get(route($formRoute))
                ->assertOk()
                ->getContent();

            $this->assertMatchesRegularExpression(
                '/<a href="'.preg_quote(route($indexRoute), '/').'"\s+class="menu-active"[^>]*data-sidebar-active/',
                $html,
                "The {$formRoute} form should keep {$indexRoute} active in the sidebar.",
            );

            $this->assertSame(
                1,
                preg_match_all('/<details\b[^>]*\bopen\b/', $html),
                "Only the {$formRoute} section should start open.",
            );
        }
    }

    public function test_the_sidebar_scrolls_to_the_active_item_after_navigation(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('data-sidebar-active', $script);
        $this->assertStringContainsString('data-drawer-nav', $script);
        $this->assertStringContainsString('nav.scrollTop', $script);
    }
}
