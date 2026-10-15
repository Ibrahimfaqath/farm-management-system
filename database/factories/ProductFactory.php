<?php

namespace Database\Factories;

use App\Enums\UnitOfMeasure;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /*
     * Prefixed `T`, in a range the demo seeder never writes.
     *
     * These used to share their prefixes and their numbering with the seeder,
     * and `fake()->unique()` does not prevent a collision: its counter is per
     * Faker instance, and Laravel supplies a fresh one per test. Two tests that
     * both seed the demo data and then build a record each restart at 001, and
     * the second hits a UNIQUE violation. Which test lost depended on the order
     * the suite ran in.
     */
    /** @return array<string, mixed> */
    public function definition(): array

    /*
     * Prefixed `T`, in a range the demo seeder never writes.

     * These used to share their prefixes and their numbering with the seeder,
     * and `fake()->unique()` does not prevent a collision: its counter is per
     * Faker instance, and Laravel supplies a fresh one per test. Two tests that
     * both seed the demo data and then build a record each restart at 001, and
     * the second hits a UNIQUE violation. Which test lost depended on the order
     * the suite ran in.
     */
    {
        return [
            'code' => 'TPRD-'.fake()->unique()->numerify('###'),
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
