<?php

namespace Database\Factories;

use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Farm> */
class FarmFactory extends Factory
{
    protected $model = Farm::class;

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
            'code' => 'TFRM-'.fake()->unique()->numerify('###'),
            'name' => fake()->company().' Farm',
            'address' => fake()->streetAddress(),
            'village' => fake()->word(),
            'district' => fake()->word(),
            'regency' => fake()->city(),
            'province' => 'Jawa Timur',
            'postal_code' => fake()->numerify('#####'),
            'phone' => '08'.fake()->numerify('##########'),
            'manager_name' => fake()->name(),
            'area_hectares' => fake()->randomFloat(1, 2, 40),
            'established_date' => fake()->dateTimeBetween('-12 years', '-1 year'),
            'notes' => null,
        ];
    }
}
