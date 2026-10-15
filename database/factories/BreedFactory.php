<?php

namespace Database\Factories;

use App\Models\Breed;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Breed> */
class BreedFactory extends Factory
{
    protected $model = Breed::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'BRD-'.fake()->unique()->numerify('##'),
            'name' => fake()->randomElement(['Holstein Friesian', 'Jersey', 'Limosin', 'Brahman', 'Madura']),
            'species' => 'cattle',
            'origin' => fake()->randomElement(['Denmark', 'UK', 'France', 'Australia', 'Indonesia']),
            'average_milk_liters' => fake()->randomFloat(1, 8, 25),
            'average_lactation_days' => fake()->numberBetween(240, 330),
            'adult_weight_kg' => fake()->randomFloat(1, 350, 650),
            'is_active' => true,
            'notes' => null,
        ];
    }
}
