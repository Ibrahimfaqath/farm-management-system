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
            'name' => 'Susu Sapi Segar',
            'unit' => UnitOfMeasure::Liter,
            'default_price' => fake()->randomFloat(0, 11_000, 13_000),
            'category' => 'milk',
            'is_active' => true,
            'notes' => 'Susu sapi segar dari pemerahan harian.',
        ];
    }
}
