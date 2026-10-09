<?php

namespace Database\Factories;

use App\Enums\HealthRecordStatus;
use App\Enums\HealthRecordType;
use App\Enums\HealthSeverity;
use App\Models\Animal;
use App\Models\HealthRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HealthRecord> */
class HealthRecordFactory extends Factory
{
    protected $model = HealthRecord::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'record_date' => now()->subDays(fake()->numberBetween(1, 60))->toDateString(),
            'record_type' => HealthRecordType::Observation,
            'severity' => HealthSeverity::Mild,
            'symptoms' => fake()->randomElement([
                'Penurunan nafsu makan',
                'Dekos',
                'Gangguan pernapasan',
                'Kurang-active',
                'Bengkak ambing',
            ]),
            'diagnosis' => null,
            'notes' => null,
            'status' => HealthRecordStatus::Open,
            'follow_up_date' => null,
            'resolved_date' => null,
            'cost' => fake()->randomFloat(0, 25_000, 350_000),
            'veterinarian_id' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => HealthRecordStatus::Resolved,
            'resolved_date' => now()->toDateString(),
        ]);
    }
}
