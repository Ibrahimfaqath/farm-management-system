<?php

namespace Database\Factories;

use App\Models\Breed;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Breed> */
class BreedFactory extends Factory
{
    protected $model = Breed::class;

    /*
     * Prefixed `T`, in a range the demo seeder never writes.
     *
     * These used to share their prefixes and their numbering with the seeder,
     * and `fake()->unique()` does not prevent a collision: its counter is per
     * Faker instance, and Laravel supplies a fresh one per test. Two tests that
     * both seed the demo data and then build a record each restart at 001, and
     * the second hits a UNIQUE violation. Which test lost depended on the order
     * the suite ran in.
     */
    /** @return array<string, mixed> */
    public function definition(): array

    /*
     * Prefixed `T`, in a range the demo seeder never writes.

     * These used to share their prefixes and their numbering with the seeder,
     * and `fake()->unique()` does not prevent a collision: its counter is per
     * Faker instance, and Laravel supplies a fresh one per test. Two tests that
     * both seed the demo data and then build a record each restart at 001, and
     * the second hits a UNIQUE violation. Which test lost depended on the order
     * the suite ran in.
     */
    {
        return [
            'code' => 'TBRD-'.fake()->unique()->numerify('##'),
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
