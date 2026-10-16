<?php

namespace Database\Factories;

use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Farm> */
class FarmFactory extends Factory
{
    protected $model = Farm::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            /*
             * Prefixed `T` so a code built here can never collide with one the
             * seeders write.
             *
             * `fake()->unique()` only guarantees uniqueness within a single
             * Faker instance, which is rebuilt for every test — it knows nothing
             * about the rows the seeder already inserted. Both draw from the same
             * 000-999 space, so a run would fail on a UNIQUE constraint about
             * one time in a hundred and for no reason connected to what it was
             * testing. `fake()` also has no memory of earlier `numerify` calls
             * made outside its own `unique()` chain.
             */
            'code' => 'TFRM-'.fake()->unique()->numerify('####'),
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
