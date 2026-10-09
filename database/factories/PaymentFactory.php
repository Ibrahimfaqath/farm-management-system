<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'invoice_id' => null,
            'customer_id' => Customer::factory(),
            'payment_date' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'amount' => fake()->randomFloat(0, 500_000, 10_000_000),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'reference_number' => 'TRX-'.fake()->bothify('??######'),
            'notes' => null,
        ];
    }
}
