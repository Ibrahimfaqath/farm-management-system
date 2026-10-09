<?php

namespace Database\Factories;

use App\Enums\BarnType;
use App\Models\Barn;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Barn> */
class BarnFactory extends Factory
{
    protected $model = Barn::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'code' => 'KAND-'.fake()->unique()->numerify('##'),
            'name' => 'Kandang '.fake()->randomElement(['A', 'B', 'C', 'D', 'E']),
            'type' => fake()->randomElement(BarnType::cases()),
            'capacity' => fake()->numberBetween(10, 60),
            'area_m2' => fake()->randomFloat(1, 80, 400),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function dairy(): static
    {
        return $this->state(fn () => ['type' => BarnType::Dairy]);
    }

    public function calf(): static
    {
        return $this->state(fn () => ['type' => BarnType::Calf]);
    }
}
