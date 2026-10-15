<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\FeedConsumption;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedConsumption> */
class FeedConsumptionFactory extends Factory
{
    protected $model = FeedConsumption::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'consumption_number' => 'FC-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'farm_id' => Farm::factory(),
            'barn_id' => null,
            'consumption_date' => now()->toDateString(),
            'animal_count' => fake()->numberBetween(3, 40),
            'total_quantity' => 0,
            'total_amount' => 0,
            'average_per_animal' => 0,
            'allow_negative_stock' => false,
            'override_reason' => null,
            'notes' => null,
        ];
    }
}
