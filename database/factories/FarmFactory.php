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
            'code' => 'FRM-'.fake()->unique()->numerify('###'),
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
