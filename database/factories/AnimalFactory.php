<?php

namespace Database\Factories;

use App\Enums\AcquisitionType;
use App\Enums\AnimalStatus;
use App\Enums\BreedingStatus;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\Breed;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Animal> */
class AnimalFactory extends Factory
{
    protected $model = Animal::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $birthDate = fake()->dateTimeBetween('-7 years', '-3 months');

        return [
            'farm_id' => Farm::factory(),
            'barn_id' => null,
            'breed_id' => Breed::factory(),
            'animal_code' => 'T-'.fake()->unique()->numerify('#####'),
            'name' => fake()->boolean(40) ? fake()->firstNameFemale() : null,
            'status' => AnimalStatus::Active,
            'production_status' => ProductionStatus::Calf,
            'breeding_status' => BreedingStatus::Open,
            'sex' => Sex::Female,
            'date_of_birth' => $birthDate,
            'acquisition_date' => $birthDate,
            'acquisition_type' => AcquisitionType::Birth,
            'acquisition_cost' => 0,
            'source_name' => null,
            'current_barn_id' => null,
            'notes' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Animal $animal): void {
            $animal->barn_id ??= Barn::factory()->create(['farm_id' => $animal->farm_id])->id;
            $animal->current_barn_id ??= $animal->barn_id;
        });
    }

    public function lactating(): static
    {
        return $this->state(fn () => [
            'sex' => Sex::Female,
            'production_status' => ProductionStatus::Lactating,
            'date_of_birth' => fake()->dateTimeBetween('-8 years', '-2 years'),
        ]);
    }

    public function dry(): static
    {
        return $this->state(fn () => [
            'sex' => Sex::Female,
            'production_status' => ProductionStatus::Dry,
        ]);
    }

    public function pregnant(): static
    {
        return $this->state(fn () => [
            'sex' => Sex::Female,
            'production_status' => ProductionStatus::Lactating,
            'breeding_status' => BreedingStatus::ConfirmedPregnant,
        ]);
    }

    public function calf(): static
    {
        return $this->state(fn () => [
            'production_status' => ProductionStatus::Calf,
            'date_of_birth' => fake()->dateTimeBetween('-8 months', '-1 month'),
        ]);
    }

    public function male(): static
    {
        return $this->state(fn () => [
            'sex' => Sex::Male,
            'breeding_status' => BreedingStatus::NotApplicable,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn () => [
            'status' => AnimalStatus::Sold,
            'exit_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'exit_type' => AnimalStatus::Sold->value,
            'exit_value' => fake()->randomFloat(2, 8_000_000, 25_000_000),
            'exit_reference' => 'TRX-'.fake()->numerify('#####'),
        ]);
    }

    public function deceased(): static
    {
        return $this->state(fn () => [
            'status' => AnimalStatus::Deceased,
            'exit_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'exit_type' => AnimalStatus::Deceased->value,
            'exit_notes' => 'Mati setelah sakit',
        ]);
    }

    public function transferred(): static
    {
        return $this->state(fn () => [
            'status' => AnimalStatus::Transferred,
            'exit_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'exit_type' => AnimalStatus::Transferred->value,
        ]);
    }
}
