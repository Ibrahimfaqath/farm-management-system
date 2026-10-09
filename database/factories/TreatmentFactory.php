<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Treatment> */
class TreatmentFactory extends Factory
{
    protected $model = Treatment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'health_record_id' => null,
            'medication_name' => fake()->randomElement([
                'Oxytetracycline 20%',
                'Penicillin Prokain',
                'Ivermectin',
                'Ketoprofen',
                'Vitamin B Complex',
            ]),
            'dosage' => fake()->randomElement(['20 ml IM', '10 ml SC', '1 ml/50 kg']),
            'route' => fake()->randomElement(['IM', 'SC', 'Oral', 'Topikal']),
            'frequency' => fake()->randomElement(['1x sehari', '2x sehari', '3x sehari']),
            'treatment_date' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
            'end_date' => now()->subDays(fake()->numberBetween(0, 5))->toDateString(),
            'withdrawal_period_days' => fake()->randomElement([3, 7, 14, null]),
            'cost' => fake()->randomFloat(0, 50_000, 900_000),
            'veterinarian_id' => null,
            'notes' => null,
        ];
    }
}
