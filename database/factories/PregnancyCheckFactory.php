<?php

namespace Database\Factories;

use App\Enums\PregnancyCheckMethod;
use App\Enums\PregnancyResult;
use App\Models\Animal;
use App\Models\PregnancyCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PregnancyCheck> */
class PregnancyCheckFactory extends Factory
{
    protected $model = PregnancyCheck::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'breeding_record_id' => null,
            'check_date' => now()->subDays(fake()->numberBetween(20, 120))->toDateString(),
            'method' => PregnancyCheckMethod::Rectal,
            'result' => PregnancyResult::ConfirmedPregnant,
            'estimated_gestation_days' => fake()->randomElement([60, 90, 120]),
            'expected_due_date' => now()->addDays(fake()->numberBetween(10, 90))->toDateString(),
            'fetus_count' => 1,
            'notes' => null,
            'veterinarian_id' => null,
        ];
    }
}
