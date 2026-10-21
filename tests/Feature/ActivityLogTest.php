<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_types_and_users_link_to_their_own_filtered_trails(): void
    {
        $owner = $this->owner();
        $manager = $this->manager();

        $invoiceLog = $this->log($owner, 'sales.invoice_created', 'Faktur INV-001 diterbitkan');
        $otherLog = $this->log($manager, 'feed.consumption_recorded', 'Pemakaian pakan di kandang B');

        $html = $this->actingAs($owner)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            route('activity-logs.index', ['action' => $invoiceLog->action]),
            $html,
        );
        $this->assertStringContainsString(
            route('activity-logs.index', ['user_id' => $owner->getKey()]),
            $html,
        );

        $this->actingAs($owner)
            ->get(route('activity-logs.index', ['action' => $invoiceLog->action]))
            ->assertOk()
            ->assertSee(__('Activity type: :type', ['type' => __('Sales invoice created')]))
            ->assertSee($invoiceLog->description)
            ->assertDontSee($otherLog->description);

        $this->actingAs($owner)
            ->get(route('activity-logs.index', ['user_id' => $owner->getKey()]))
            ->assertOk()
            ->assertSee(__('Activity trail for :name', ['name' => $owner->name]))
            ->assertSee($invoiceLog->description)
            ->assertDontSee($otherLog->description);
    }

    public function test_settings_links_each_user_to_their_activity_trail(): void
    {
        $owner = $this->owner();
        $manager = $this->manager();

        $this->actingAs($owner)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(route('activity-logs.index', ['user_id' => $manager->getKey()]), false);
    }

    private function log(User $user, string $action, string $description): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->getKey(),
            'user_name' => $user->name,
            'action' => $action,
            'description' => $description,
            'reference' => 'TEST-001',
        ]);
    }
}
