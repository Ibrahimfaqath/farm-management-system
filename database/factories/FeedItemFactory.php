<?php

namespace Database\Factories;

use App\Enums\FeedCategory;
use App\Enums\UnitOfMeasure;
use App\Models\FeedItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedItem> */
class FeedItemFactory extends Factory
{
    protected $model = FeedItem::class;

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
            'code' => 'TFD-'.fake()->unique()->numerify('###'),
            'name' => fake()->randomElement([
                'Silase Jagung',
                'Konsentrat 414',
                'Beras Padi',
                'Dedak Jagung',
                'Susu Sapi Bubuk',
                'Mineral Blok',
                'Garam Mineral',
                'Kulit Soybean',
                'Pollard Gandul',
                'Vitamin ADE',
                'Rumput Segar',
                'Lumpur Babi',
            ]),
            'category' => fake()->randomElement(FeedCategory::cases()),
            'unit' => UnitOfMeasure::Kilogram,
            'price_per_unit' => fake()->randomFloat(0, 2_000, 12_000),
            'reorder_point' => fake()->randomFloat(1, 200, 2_000),
            'reorder_quantity' => fake()->randomFloat(1, 1_000, 5_000),
            'storage_location' => fake()->randomElement(['Gudang A', 'Silo 1', 'Silo 2', 'Kantor']),
            'shelf_life_days' => fake()->randomElement([90, 180, 365, null]),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function configured(float $price = 10_000): static
    {
        return $this->state(fn () => [
            'price_per_unit' => $price,
            'reorder_point' => 0,
        ]);
    }
}
