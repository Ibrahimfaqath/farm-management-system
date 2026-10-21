<?php

namespace Tests\Feature;

use App\Models\Barn;
use App\Models\Breed;
use App\Models\Customer;
use App\Models\Farm;
use App\Models\FeedItem;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Codes are application data, not user input.
 *
 * Every master record used to ask for a code by hand. The next number is now
 * derived from the existing codes with the same prefix, and a submitted code is
 * ignored so a stale form or a crafted request cannot create a gap or a
 * duplicate.
 */
class AutomaticCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_codes_are_generated_sequentially(): void
    {
        $user = $this->owner();

        $farm = Farm::factory()->create(['code' => 'FRM-001']);
        $this->actingAs($user)->post(route('farms.store'), [
            'code' => 'MANUAL-FARM',
            'name' => 'Peternakan Baru',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('farms', ['name' => 'Peternakan Baru', 'code' => 'FRM-002']);
        $this->assertDatabaseMissing('farms', ['code' => 'MANUAL-FARM']);

        $barn = Barn::factory()->create(['farm_id' => $farm->id, 'code' => 'KAND-001']);
        $this->actingAs($user)->post(route('barns.store'), [
            'code' => 'MANUAL-BARN',
            'farm_id' => $farm->id,
            'name' => 'Kandang Baru',
            'type' => 'dairy',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('barns', ['name' => 'Kandang Baru', 'code' => 'KAND-002']);
        $this->assertDatabaseMissing('barns', ['code' => 'MANUAL-BARN']);

        Breed::factory()->create(['code' => 'BRD-001']);
        $this->actingAs($user)->post(route('breeds.store'), [
            'code' => 'MANUAL-BREED',
            'name' => 'Guernsey',
            'species' => 'buffalo',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('breeds', ['name' => 'Guernsey', 'code' => 'BRD-002', 'species' => 'cattle']);
        $this->assertDatabaseMissing('breeds', ['code' => 'MANUAL-BREED']);

        FeedItem::factory()->create(['code' => 'FD-001']);
        $this->actingAs($user)->post(route('feed-items.store'), [
            'code' => 'MANUAL-FEED',
            'name' => 'Rumput Baru',
            'category' => 'forage',
            'unit' => 'kg',
            'price_per_unit' => 2500,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('feed_items', ['name' => 'Rumput Baru', 'code' => 'FD-002']);
        $this->assertDatabaseMissing('feed_items', ['code' => 'MANUAL-FEED']);

        Supplier::factory()->create(['code' => 'SUP-001']);
        $this->actingAs($user)->post(route('suppliers.store'), [
            'code' => 'MANUAL-SUPPLIER',
            'name' => 'Pemasok Baru',
            'category' => 'feed',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('suppliers', ['name' => 'Pemasok Baru', 'code' => 'SUP-002']);
        $this->assertDatabaseMissing('suppliers', ['code' => 'MANUAL-SUPPLIER']);

        Customer::factory()->create(['code' => 'CUS-001']);
        $this->actingAs($user)->post(route('customers.store'), [
            'code' => 'MANUAL-CUSTOMER',
            'name' => 'Pelanggan Baru',
            'type' => 'retail',
            'payment_terms_days' => 30,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', ['name' => 'Pelanggan Baru', 'code' => 'CUS-002']);
        $this->assertDatabaseMissing('customers', ['code' => 'MANUAL-CUSTOMER']);
    }

    public function test_legacy_non_numeric_codes_do_not_break_the_sequence(): void
    {
        $barn = Barn::factory()->create(['code' => 'KAND-A']);

        $response = $this->actingAs($this->owner())
            ->get(route('barns.create', ['farm_id' => $barn->farm_id]))
            ->assertOk();

        $this->assertSame('KAND-001', $response->viewData('nextCode'));
    }
}
