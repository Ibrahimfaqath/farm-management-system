<?php

namespace Tests\Feature;

use App\Enums\AnimalStatus;
use App\Enums\BreedingStatus;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\Breed;
use App\Models\Farm;
use App\Models\HealthRecord;
use App\Models\MilkProduction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_animal_code_is_generated_sequentially_and_ignores_the_submitted_code(): void
    {
        $user = $this->owner();
        $existing = Animal::factory()->create(['animal_code' => 'T-00001']);

        $this->actingAs($user)->post(route('animals.store'), [
            'animal_code' => 'T-99999',
            'farm_id' => $existing->farm_id,
            'breed_id' => $existing->breed_id,
            'sex' => Sex::Female->value,
            'status' => AnimalStatus::Active->value,
            'production_status' => ProductionStatus::Calf->value,
            'breeding_status' => BreedingStatus::Open->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Animal::count());
        $this->assertDatabaseHas('animals', ['animal_code' => 'T-00002']);
        $this->assertDatabaseMissing('animals', ['animal_code' => 'T-99999']);
    }

    public function test_adding_an_animal_from_a_barn_preselects_that_barn_and_farm(): void
    {
        $barn = Barn::factory()->create();

        $response = $this->actingAs($this->owner())
            ->get(route('animals.create', ['barn_id' => $barn->id]))
            ->assertOk();

        $animal = $response->viewData('animal');

        $this->assertSame($barn->id, $animal->barn_id);
        $this->assertSame($barn->farm_id, $animal->farm_id);
        $this->assertSame($barn->id, $animal->current_barn_id);
    }

    public function test_lineage_is_optional_when_registering_an_animal(): void
    {
        $user = $this->owner();
        $farm = Farm::factory()->create();
        $breed = Breed::factory()->create();

        $this->actingAs($user)->post(route('animals.store'), [
            'farm_id' => $farm->id,
            'breed_id' => $breed->id,
            'sex' => Sex::Female->value,
            'status' => AnimalStatus::Active->value,
            'production_status' => ProductionStatus::Calf->value,
            'breeding_status' => BreedingStatus::Open->value,
        ])->assertSessionHasNoErrors();

        $animal = Animal::query()->where('farm_id', $farm->id)->firstOrFail();

        $this->assertNull($animal->dam_id);
        $this->assertNull($animal->sire_id);
    }

    public function test_male_animal_cannot_be_lactating(): void
    {
        $user = $this->owner();
        $breed = Breed::factory()->create();

        $response = $this->actingAs($user)->post(route('animals.store'), [
            'animal_code' => 'B-001',
            'farm_id' => Farm::factory()->create()->id,
            'breed_id' => $breed->id,
            'sex' => Sex::Male->value,
            'status' => AnimalStatus::Active->value,
            'production_status' => ProductionStatus::Lactating->value,
            'breeding_status' => BreedingStatus::NotApplicable->value,
        ]);

        $response->assertSessionHasErrors('production_status');
    }

    public function test_animal_can_be_sold_without_being_deleted(): void
    {
        $user = $this->owner();
        $animal = Animal::factory()->lactating()->create();

        MilkProduction::factory()->create(['animal_id' => $animal->id, 'liters' => 18]);
        HealthRecord::factory()->create(['animal_id' => $animal->id]);

        $response = $this->actingAs($user)->post(route('animals.exit', $animal), [
            'exit_type' => AnimalStatus::Sold->value,
            'exit_date' => now()->subDay()->toDateString(),
            'exit_value' => 18_500_000,
            'exit_reference' => 'NOTA-991',
            'reason' => 'Dipesan pembeli dari\Domain Dairy',
        ]);

        $response->assertRedirect(route('animals.show', $animal));

        $animal->refresh();
        $this->assertSame(AnimalStatus::Sold, $animal->status);
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'status' => 'sold']);
        $this->assertDatabaseHas('milk_productions', ['animal_id' => $animal->id]);
        $this->assertDatabaseHas('health_records', ['animal_id' => $animal->id]);
    }

    public function test_genealogy_relationships_work(): void
    {
        $dam = Animal::factory()->lactating()->create(['sex' => Sex::Female->value]);
        $sire = Animal::factory()->male()->create();
        $calf = Animal::factory()->create(['dam_id' => $dam->id, 'sire_id' => $sire->id]);

        $this->assertTrue($calf->dam->is($dam));
        $this->assertTrue($calf->sire->is($sire));
        $this->assertCount(1, $dam->calves);
        $this->assertCount(1, $sire->offspringFromSire);
    }

    public function test_animal_cannot_be_its_own_parent(): void
    {
        $user = $this->owner();
        $animal = Animal::factory()->create();

        $response = $this->actingAs($user)->put(route('animals.update', $animal), [
            'animal_code' => $animal->animal_code,
            'farm_id' => $animal->farm_id,
            'breed_id' => $animal->breed_id,
            'sex' => Sex::Female->value,
            'status' => AnimalStatus::Active->value,
            'production_status' => ProductionStatus::Calf->value,
            'breeding_status' => BreedingStatus::Open->value,
            'dam_id' => $animal->id,
        ]);

        $response->assertSessionHasErrors('dam_id');
    }

    public function test_barn_rejects_animal_from_another_farm(): void
    {
        $user = $this->owner();
        $farm = Farm::factory()->create();
        $otherFarm = Farm::factory()->create();

        $response = $this->actingAs($user)->post(route('animals.store'), [
            'animal_code' => 'T-XYZ',
            'farm_id' => $farm->id,
            'barn_id' => Barn::factory()->create(['farm_id' => $otherFarm->id])->id,
            'breed_id' => Breed::factory()->create()->id,
            'sex' => Sex::Female->value,
            'status' => AnimalStatus::Active->value,
            'production_status' => ProductionStatus::Calf->value,
            'breeding_status' => BreedingStatus::Open->value,
        ]);

        $response->assertSessionHasErrors('barn_id');
    }
}
