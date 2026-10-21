<?php

namespace Tests\Feature;

use App\Enums\AnimalStatus;
use App\Enums\BreedingRecordType;
use App\Enums\BreedingStatus;
use App\Enums\PregnancyResult;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\BirthRecord;
use App\Models\BreedingRecord;
use App\Models\MilkProduction;
use App\Models\PregnancyCheck;
use App\Services\BreedingService;
use App\Services\ProductionAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreedingAndProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_mating_alone_does_not_mark_the_animal_pregnant(): void
    {
        $user = $this->veterinarian();
        $animal = Animal::factory()->lactating()->create(['breeding_status' => BreedingStatus::Open]);
        $bull = Animal::factory()->male()->create();

        $record = BreedingRecord::create([
            'animal_id' => $animal->id,
            'record_type' => BreedingRecordType::Insemination,
            'breeding_date' => now()->subDays(10)->toDateString(),
            'bull_id' => $bull->id,
            'created_by' => $user->id,
        ]);

        app(BreedingService::class)->recordMating($animal, $record->id, $user);

        $this->assertSame(BreedingStatus::Bred, $animal->fresh()->breeding_status);
        $this->assertFalse($animal->fresh()->breeding_status->isPregnant());
    }

    public function test_heat_observation_never_changes_breeding_status(): void
    {
        $user = $this->veterinarian();
        $animal = Animal::factory()->lactating()->create(['breeding_status' => BreedingStatus::Open]);

        BreedingRecord::create([
            'animal_id' => $animal->id,
            'record_type' => BreedingRecordType::HeatObserved,
            'breeding_date' => now()->toDateString(),
            'heat_signs' => 'Gejala birahi terlihat',
            'created_by' => $user->id,
        ]);

        $this->assertSame(BreedingStatus::Open, $animal->fresh()->breeding_status);
    }

    public function test_negative_pregnancy_check_returns_the_animal_to_open(): void
    {
        $user = $this->veterinarian();
        $animal = Animal::factory()->lactating()->create(['breeding_status' => BreedingStatus::ConfirmedPregnant]);

        $check = PregnancyCheck::create([
            'animal_id' => $animal->id,
            'check_date' => now()->subDays(45)->toDateString(),
            'method' => 'rectal',
            'result' => PregnancyResult::NotPregnant,
        ]);

        app(BreedingService::class)->applyPregnancyCheck($check, $user);

        $this->assertSame(BreedingStatus::Open, $animal->fresh()->breeding_status);
    }

    public function test_expected_due_date_is_derived_from_the_mating_date(): void
    {
        $breeding = BreedingRecord::create([
            'animal_id' => Animal::factory()->create()->id,
            'record_type' => BreedingRecordType::Insemination,
            'breeding_date' => now()->subDays(100)->toDateString(),
        ]);

        $check = PregnancyCheck::create([
            'animal_id' => $breeding->animal_id,
            'breeding_record_id' => $breeding->id,
            'check_date' => now()->toDateString(),
            'method' => 'rectal',
            'result' => PregnancyResult::ConfirmedPregnant,
        ]);

        $this->assertTrue($check->expectedDueDate()->isAfter(now()->addDays(175)));
    }

    public function test_birth_links_genealogy_and_reactivates_the_dam(): void
    {
        $user = $this->manager();
        $dam = Animal::factory()->dry()->create(['production_status' => ProductionStatus::Dry]);
        $sire = Animal::factory()->male()->create();

        $birth = app(BreedingService::class)->recordBirth([
            'dam_id' => $dam->id,
            'sire_id' => $sire->id,
            'birth_date' => now()->toDateString(),
            'birth_type' => 'normal',
        ], $user);

        $dam->refresh();

        $this->assertSame(BreedingStatus::Open, $dam->breeding_status);
        $this->assertSame(ProductionStatus::Lactating, $dam->production_status);
        $this->assertSame($dam->id, $birth->dam_id);
        $this->assertSame($sire->id, $birth->sire_id);
    }

    public function test_birth_record_validation_rejects_inconsistent_parent_sex(): void
    {
        $user = $this->manager();
        $male = Animal::factory()->male()->create();

        $response = $this->actingAs($user)->post(route('birth-records.store'), [
            'dam_id' => $male->id,
            'birth_date' => now()->toDateString(),
            'birth_type' => 'normal',
        ]);

        $response->assertSessionHasErrors('dam_id');
    }

    public function test_production_totals_and_trend_are_calculated_correctly(): void
    {
        $animal = Animal::factory()->lactating()->create();

        MilkProduction::factory()->forAnimal($animal)->liters(20)->on(now()->subDays(2)->toDateString())->create();
        MilkProduction::factory()->forAnimal($animal)->liters(22)->on(now()->subDays(1)->toDateString())->create();
        MilkProduction::factory()->forAnimal($animal)->liters(24)->on(now()->toDateString())->create();

        $analytics = app(ProductionAnalyticsService::class);

        $today = $analytics->summary(null, now()->startOfDay(), now()->endOfDay());
        $this->assertSame(24.0, $today['total']);
        $this->assertSame(1, $today['records']);

        $threeDays = $analytics->summary(null, now()->subDays(2)->startOfDay(), now()->endOfDay());
        $this->assertSame(66.0, $threeDays['total']);
        $this->assertSame(3, $threeDays['records']);

        $daily = $analytics->dailyTotals(null, now()->subDays(2)->startOfDay(), now()->endOfDay());
        $this->assertCount(3, $daily);
        $this->assertSame(24.0, $daily[2]['total']);
    }

    public function test_decline_is_reported_without_any_medical_claim(): void
    {
        $animal = Animal::factory()->lactating()->create();

        // Stable 20 L/day across the previous comparison window (14 to 27 days ago).
        for ($i = 14; $i <= 27; $i++) {
            MilkProduction::factory()->forAnimal($animal)->liters(20)->on(now()->subDays($i)->toDateString())->create();
        }

        // Only 10 L/day across the current comparison window (0 to 13 days ago).
        for ($i = 0; $i <= 13; $i++) {
            MilkProduction::factory()->forAnimal($animal)->liters(10)->on(now()->subDays($i)->toDateString())->create();
        }

        $declines = app(ProductionAnalyticsService::class)->decliningAnimals(null);

        $this->assertCount(1, $declines);
        $this->assertSame($animal->id, $declines[0]['animal']->id);
        $this->assertSame(-50.0, $declines[0]['change_pct']);

        // The report carries numbers only — no diagnosis is asserted anywhere.
        $this->assertSame(20.0, $declines[0]['previous']);
        $this->assertSame(10.0, $declines[0]['current']);
    }

    public function test_milk_production_requires_animal_or_barn_not_both(): void
    {
        $user = $this->worker();
        $animal = Animal::factory()->lactating()->create();
        $barn = $animal->barn;

        $response = $this->actingAs($user)->post(route('milk-productions.store'), [
            'farm_id' => $animal->farm_id,
            'animal_id' => $animal->id,
            'barn_id' => $barn->id,
            'production_date' => now()->toDateString(),
            'session' => 'mixed',
            'liters' => 12,
        ]);

        $response->assertSessionHasErrors('barn_id');
    }

    public function test_milk_production_rejects_animal_that_is_not_lactating(): void
    {
        $user = $this->worker();
        $dry = Animal::factory()->dry()->create();

        $response = $this->actingAs($user)->post(route('milk-productions.store'), [
            'farm_id' => $dry->farm_id,
            'animal_id' => $dry->id,
            'production_date' => now()->toDateString(),
            'session' => 'mixed',
            'liters' => 12,
        ]);

        $response->assertSessionHasErrors('animal_id');
    }

    public function test_sold_animal_history_stays_queryable(): void
    {
        $animal = Animal::factory()->lactating()->sold()->create();

        $this->assertSame(AnimalStatus::Sold, $animal->status);
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'status' => 'sold']);

        // The record is still reachable and its production history intact.
        $this->actingAs($this->owner())->get(route('animals.show', $animal))->assertOk();
    }

    public function test_birth_record_links_new_calf_to_parents(): void
    {
        $user = $this->manager();
        $dam = Animal::factory()->lactating()->create(['sex' => Sex::Female->value]);
        $sire = Animal::factory()->male()->create();

        $this->actingAs($user)->post(route('birth-records.store'), [
            'dam_id' => $dam->id,
            'sire_id' => $sire->id,
            'birth_date' => now()->subDay()->toDateString(),
            'birth_type' => 'normal',
            'birth_weight_kg' => 38,
            'register_calf' => 1,
            'calf_sex' => 'female',
        ])->assertSessionHasNoErrors();

        $calf = Animal::where('dam_id', $dam->id)->where('production_status', ProductionStatus::Calf)->firstOrFail();

        $this->assertMatchesRegularExpression('/^T-\d{5}$/', $calf->animal_code);
        $this->assertSame($dam->id, $calf->dam_id);
        $this->assertSame($sire->id, $calf->sire_id);
        $this->assertTrue($calf->is($dam->calves->first()));
        $this->assertDatabaseHas('birth_records', ['dam_id' => $dam->id, 'animal_id' => $calf->id]);
        $this->assertSame(ProductionStatus::Calf, $calf->production_status);
    }

    public function test_stillborn_calf_cannot_be_registered_as_an_animal(): void
    {
        $user = $this->manager();
        $dam = Animal::factory()->lactating()->create();

        $this->actingAs($user)->post(route('birth-records.store'), [
            'dam_id' => $dam->id,
            'birth_date' => now()->toDateString(),
            'birth_type' => 'stillborn',
            'register_calf' => 1,
            'calf_sex' => 'male',
        ])->assertSessionHasErrors('register_calf');
    }

    public function test_birth_record_is_created_for_a_live_calf(): void
    {
        $user = $this->manager();
        $dam = Animal::factory()->lactating()->create();

        $this->actingAs($user)->post(route('birth-records.store'), [
            'dam_id' => $dam->id,
            'birth_date' => now()->toDateString(),
            'birth_type' => 'normal',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('birth_records', ['dam_id' => $dam->id, 'birth_type' => 'normal']);
        $this->assertInstanceOf(BirthRecord::class, BirthRecord::where('dam_id', $dam->id)->first());
    }
}
