<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /** @return array<string, mixed> */
    /*
     * Prefixed `T`, for the same reason as the other factories: the demo
     * seeder writes real invoice numbers in the same date-and-sequence shape,
     * and `fake()->unique()` restarts per Faker instance, so a test that seeds
     * and then builds an invoice lands on a number the seeder already used.
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(0, 2_000_000, 25_000_000);

        return [
            'invoice_number' => 'TINV-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'customer_id' => Customer::factory(),
            'sales_order_id' => null,
            'invoice_date' => now()->subDays(fake()->numberBetween(1, 45))->toDateString(),
            'payment_terms_days' => 30,
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal' => $total,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => $total,
            'paid_amount' => 0,
            'status' => InvoiceStatus::Unpaid,
            'notes' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'invoice_date' => now()->subDays(60)->toDateString(),
            'due_date' => now()->subDays(30)->toDateString(),
            'paid_amount' => 0,
            'status' => InvoiceStatus::Unpaid,
        ]);
    }
}
