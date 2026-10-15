<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseOrder> */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'po_number' => 'PO-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
            'supplier_id' => Supplier::factory(),
            'order_date' => now()->subDays(fake()->numberBetween(3, 45))->toDateString(),
            'expected_date' => now()->addDays(fake()->numberBetween(1, 10))->toDateString(),
            'status' => PurchaseOrderStatus::Approved,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'notes' => null,
        ];
    }
}
