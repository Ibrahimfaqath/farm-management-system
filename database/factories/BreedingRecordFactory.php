<?php

namespace Database\Factories;

use App\Enums\BreedingRecordType;
use App\Models\Animal;
use App\Models\BreedingRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BreedingRecord> */
class BreedingRecordFactory extends Factory
{
    protected $model = BreedingRecord::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'record_type' => BreedingRecordType::Insemination,
            'breeding_date' => now()->subDays(fake()->numberBetween(10, 200))->toDateString(),
            'bull_id' => null,
            'semen_code' => fake()->randomElement(['BRF-01', 'JRS-02', 'LIM-03']),
            'semen_batch' => fake()->bothify('SB-####'),
            'inseminator' => fake()->name(),
            'heat_signs' => 'Gejala birahi terlihat',
            'expected_next_heat_date' => now()->addDays(21)->toDateString(),
            'notes' => null,
        ];
    }
}
