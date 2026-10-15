<?php

namespace Database\Factories;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GoodsReceipt> */
class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    /** @return array<string, mixed> */
    /*
     * Prefixed `T`, in a range the demo seeder never writes.
     *
     * `fake()->unique()` does not prevent a collision with seeded data: its
     * counter is per Faker instance and Laravel supplies a fresh one per test,
     * so a test that seeds and then builds a document can land on a number the
     * seeder already used. Which test failed depended on the order the suite
     * happened to run in.
     */
    public function definition(): array
    {
        return [
            'receipt_number' => 'TGR-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
            'purchase_order_id' => null,
            'supplier_id' => Supplier::factory(),
            'received_date' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
            'status' => GoodsReceiptStatus::Draft,
            'delivery_note_reference' => 'DO-'.fake()->numerify('#####'),
            'total_amount' => 0,
            'quality_notes' => null,
            'notes' => null,
            'posted_at' => null,
        ];
    }
}
