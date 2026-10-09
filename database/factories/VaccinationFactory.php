<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Vaccination;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vaccination> */
class VaccinationFactory extends Factory
{
    protected $model = Vaccination::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'vaccine_name' => fake()->randomElement([
                'Brucellosis',
                'FMD',
                'Haemorrhagic Septicaemia',
                'Anthrax',
                'Newcastle (AI)',
                'BVD',
            ]),
            'batch_number' => 'VXC-'.fake()->bothify('??####'),
            'administered_date' => now()->subDays(fake()->numberBetween(30, 300))->toDateString(),
            'next_due_date' => now()->addDays(fake()->numberBetween(-20, 240))->toDateString(),
            'dose' => fake()->randomElement(['2 ml IM', '5 ml SC', '1 ml SC']),
            'route' => fake()->randomElement(['IM', 'SC', 'Oral']),
            'cost' => fake()->randomFloat(0, 25_000, 150_000),
            'administered_by' => fake()->name(),
            'veterinarian_id' => null,
            'notes' => null,
        ];
    }

    public function dueSoon(): static
    {
        return $this->state(fn () => [
            'next_due_date' => now()->addDays(fake()->numberBetween(1, 21))->toDateString(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'next_due_date' => now()->subDays(fake()->numberBetween(3, 60))->toDateString(),
        ]);
    }
}
