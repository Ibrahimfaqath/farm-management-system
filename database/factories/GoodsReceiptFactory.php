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
    public function definition(): array
    {
        return [
            'receipt_number' => 'GR-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
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
