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
            'po_number' => 'TPO-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
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
