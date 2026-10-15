<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplierPayment> */
class SupplierPaymentFactory extends Factory
{
    protected $model = SupplierPayment::class;

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
            'payment_number' => 'TSP-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'supplier_id' => Supplier::factory(),
            'goods_receipt_id' => null,
            'payment_date' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'amount' => fake()->randomFloat(0, 500_000, 15_000_000),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'reference_number' => 'TRX-'.fake()->bothify('??######'),
            'notes' => null,
        ];
    }
}
