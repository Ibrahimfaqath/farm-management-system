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
            'farm_id' => Farm::factory(),
            'code' => 'TKAND-'.fake()->unique()->numerify('##'),
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
