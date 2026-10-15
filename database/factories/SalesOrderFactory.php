<?php

namespace Database\Factories;

use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesOrder> */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

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
            'so_number' => 'TSO-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'customer_id' => Customer::factory(),
            'order_date' => now()->subDays(fake()->numberBetween(1, 60))->toDateString(),
            'status' => SalesOrderStatus::Confirmed,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'notes' => null,
        ];
    }
}
