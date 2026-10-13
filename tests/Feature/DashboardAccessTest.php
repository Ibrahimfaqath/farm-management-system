<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\AttentionService;
use App\Support\Navigation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Nothing the interface offers may be a dead end.
 *
 * Two separate faults were found this way. The sidebar declared a "Production
 * report" link inside the Production group, but the route behind it sits behind
 * `module:reports`, so a Farm Worker and a Veterinarian were both offered a link
 * that answered 403. And the dashboard rendered receivables, overdue invoice
 * totals, unpaid supplier balances and the audit trail for every role, so the
 * same worker read figures they have no permission to see and could not act on.
 *
 * These tests walk what each role is actually shown and ask the application for
 * every link that appears on the page.
 */
class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_sidebar_links_match_real_access_for_every_role(): void
    {
        $all = [];

        foreach (Role::cases() as $role) {
            $user = User::where('role', $role)->firstOrFail();

            $links = [];
            foreach (Navigation::forUser($user) as $group) {
                foreach ($group['items'] as $item) {
                    $links[$item['label']] = route($item['route']);
                }
            }

            $broken = [];
            foreach ($links as $label => $url) {
                $status = $this->actingAs($user)->get($url)->getStatusCode();

                if ($status >= 400) {
                    $broken[] = "{$label} ({$url}) -> {$status}";
                }
            }

            $all[$role->value] = $broken;
        }

        $this->assertSame([], array_filter($all), 'The sidebar offers pages the role cannot open.');
    }

    public function test_the_production_report_is_hidden_from_roles_without_the_reports_module(): void
    {
        $worker = User::where('role', Role::Worker)->firstOrFail();
        $veterinarian = User::where('role', Role::Veterinarian)->firstOrFail();
        $owner = User::where('role', Role::Owner)->firstOrFail();

        $labels = fn (array $groups) => array_column(array_merge(...array_column($groups, 'items')), 'label');

        $report = __('Production report');

        $this->assertNotContains($report, $labels(Navigation::forUser($worker)));
        $this->assertNotContains($report, $labels(Navigation::forUser($veterinarian)));
        $this->assertContains($report, $labels(Navigation::forUser($owner)));

        // And the route really is closed to them.
        $this->actingAs($worker)->get(route('reports.production.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('reports.production.index'))->assertOk();
    }

    public function test_every_dashboard_link_is_reachable_for_its_role(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::where('role', $role)->firstOrFail();

            $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

            preg_match_all('/href="([^"#]+)"/', $html, $matches);
            $urls = array_values(array_unique(array_filter(
                $matches[1],
                fn (string $href) => str_starts_with($href, '/')
                    && ! str_starts_with($href, '/logout')
                    && ! str_starts_with($href, '/_boost'),
            )));

            $broken = [];
            foreach ($urls as $url) {
                $status = $this->actingAs($user)->get($url)->getStatusCode();

                if ($status >= 400) {
                    $broken[] = "[{$role->value}] {$url} -> {$status}";
                }
            }

            $this->assertSame([], $broken, "[{$role->value}] the dashboard links to pages it cannot open.");
        }
    }

    public function test_finance_figures_are_hidden_from_roles_without_the_finance_module(): void
    {
        $worker = User::where('role', Role::Worker)->firstOrFail();
        $finance = User::where('role', Role::Finance)->firstOrFail();

        $workerHtml = $this->actingAs($worker)->get(route('dashboard'))->assertOk()->getContent();
        $financeHtml = $this->actingAs($finance)->get(route('dashboard'))->assertOk()->getContent();

        foreach ([__('Receivables'), __('Cash balance'), __('Supplier payable'), __('Recent expenses'), __('Revenue this month'), __('Estimated profit')] as $label) {
            $this->assertStringNotContainsString($label, $workerHtml, "A worker is shown \"{$label}\".");
            $this->assertStringContainsString($label, $financeHtml, "Finance cannot see \"{$label}\".");
        }

        // Currency figures would leak through even if the heading did not.
        $this->assertStringNotContainsString('Rp', $workerHtml);
    }

    public function test_the_audit_trail_panel_needs_the_audit_ability(): void
    {
        $manager = User::where('role', Role::Manager)->firstOrFail();
        $owner = User::where('role', Role::Owner)->firstOrFail();

        $managerHtml = $this->actingAs($manager)->get(route('dashboard'))->assertOk()->getContent();
        $ownerHtml = $this->actingAs($owner)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString(__('Recent activity'), $managerHtml);
        $this->assertStringContainsString(__('Recent activity'), $ownerHtml);
    }

    public function test_the_activity_trail_link_hides_from_roles_without_audit_ability(): void
    {
        $manager = User::where('role', Role::Manager)->firstOrFail();

        $labels = array_column(
            array_merge(...array_column(Navigation::forUser($manager), 'items')),
            'label',
        );

        $this->assertNotContains(__('Activity trail'), $labels);
        $this->actingAs($manager)->get(route('activity-logs.index'))->assertOk();
    }

    public function test_attention_items_are_filtered_to_the_role_modules(): void
    {
        $worker = User::where('role', Role::Worker)->firstOrFail();

        $items = app(AttentionService::class)->itemsFor($worker);

        $this->assertNotSame([], app(AttentionService::class)->items(), 'The fixture should produce attention items.');
        $this->assertSame(
            [],
            array_values(array_filter($items, fn (array $i) => ! $worker->canAccessModule($i['module']))),
            'Attention items leaked from a module the role cannot open.',
        );
    }

    public function test_the_navigation_never_offers_an_unregistered_route(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::where('role', $role)->firstOrFail();

            foreach (Navigation::forUser($user) as $group) {
                foreach ($group['items'] as $item) {
                    $this->assertTrue(
                        Route::has($item['route']),
                        "[{$role->value}] the sidebar links to unregistered route {$item['route']}.",
                    );
                }
            }
        }
    }
}
