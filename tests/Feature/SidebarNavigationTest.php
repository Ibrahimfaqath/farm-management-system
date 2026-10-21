<?php

namespace Tests\Feature;

use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_groups_are_collapsible_and_the_active_group_is_open(): void
    {
        $owner = $this->owner();
        $groups = Navigation::forUser($owner);

        $html = $this->actingAs($owner)
            ->get(route('animals.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            count($groups),
            substr_count($html, '<details'),
            'Every navigation category should be a collapsible group.',
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

    public function test_every_navigation_item_is_inside_a_category_summary(): void
    {
        $owner = $this->owner();

        $html = $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        foreach (Navigation::forUser($owner) as $group) {
            $this->assertStringContainsString('>'.e($group['label']).'</span>', $html);
        }
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
