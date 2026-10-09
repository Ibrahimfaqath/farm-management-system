<?php

namespace Database\Factories;

use App\Enums\IncomeCategory;
use App\Enums\PaymentMethod;
use App\Models\Income;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Income> */
class IncomeFactory extends Factory
{
    protected $model = Income::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'income_number' => 'INC-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'income_date' => now()->subDays(fake()->numberBetween(0, 60))->toDateString(),
            'category' => fake()->randomElement(IncomeCategory::cases()),
            'amount' => fake()->randomFloat(0, 200_000, 8_000_000),
            'description' => fake()->randomElement([
                'Penjualan manure',
                'Selling bio-organic',
                'Penjualan anak',
                'Bantuan 향상 mutu',
            ]),
            'source_name' => fake()->company(),
            'reference' => 'REF-'.fake()->numerify('#####'),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'notes' => null,
        ];
    }
}
