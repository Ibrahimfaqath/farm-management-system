<?php

namespace Database\Factories;

use App\Enums\UnitOfMeasure;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'PRD-'.fake()->unique()->numerify('###'),
            'name' => fake()->randomElement([
                'Susu Segar',
                'Susu Pasteurisasi',
                'Pupuk Kaporit',
                'Yogurt',
                'Keju Pestas',
            ]),
            'unit' => fake()->randomElement([UnitOfMeasure::Liter, UnitOfMeasure::Kilogram, UnitOfMeasure::Sack]),
            'default_price' => fake()->randomFloat(0, 5_000, 25_000),
            'category' => fake()->randomElement(['milk', 'byproduct', 'other']),
            'is_active' => true,
            'notes' => null,
        ];
    }
}
