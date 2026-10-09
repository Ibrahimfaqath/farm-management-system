<?php

namespace Database\Factories;

use App\Enums\BirthType;
use App\Models\Animal;
use App\Models\BirthRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BirthRecord> */
class BirthRecordFactory extends Factory
{
    protected $model = BirthRecord::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'dam_id' => Animal::factory(),
            'sire_id' => null,
            'animal_id' => null,
            'pregnancy_check_id' => null,
            'birth_date' => now()->subDays(fake()->numberBetween(1, 180))->toDateString(),
            'birth_time' => fake()->time(),
            'birth_type' => BirthType::Normal,
            'birth_weight_kg' => fake()->randomFloat(1, 28, 45),
            'colostrum_intake' => '2 jam pertama',
            'calf_sex' => 0,
            'assistance_notes' => null,
        ];
    }
}
