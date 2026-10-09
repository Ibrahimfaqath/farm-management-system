<?php

namespace Database\Factories;

use App\Enums\MilkSession;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\MilkProduction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MilkProduction> */
class MilkProductionFactory extends Factory
{
    protected $model = MilkProduction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'animal_id' => null,
            'barn_id' => null,
            'production_date' => now()->toDateString(),
            'session' => MilkSession::Mixed,
            'liters' => fake()->randomFloat(1, 4, 28),
            'fat_percent' => null,
            'protein_percent' => null,
            'notes' => null,
        ];
    }

    public function forAnimal(Animal $animal): static
    {
        return $this->state(fn () => [
            'farm_id' => $animal->farm_id,
            'animal_id' => $animal->id,
            'barn_id' => $animal->barn_id,
        ]);
    }

    public function on(string $date): static
    {
        return $this->state(fn () => ['production_date' => $date]);
    }

    public function liters(float $liters): static
    {
        return $this->state(fn () => ['liters' => $liters]);
    }
}
