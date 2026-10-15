<?php

namespace Database\Factories;

use App\Enums\CashCategory;
use App\Enums\CashDirection;
use App\Enums\PaymentMethod;
use App\Models\CashTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashTransaction> */
class CashTransactionFactory extends Factory
{
    protected $model = CashTransaction::class;

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
            'transaction_number' => 'TTRX-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'transaction_date' => now()->subDays(fake()->numberBetween(0, 60))->toDateString(),
            'direction' => CashDirection::In,
            'category' => CashCategory::OtherIncome,
            'amount' => fake()->randomFloat(0, 100_000, 5_000_000),
            'description' => 'Transaksi kas',
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'reference_number' => null,
            'notes' => null,
        ];
    }

    public function outflow(): static
    {
        return $this->state(fn () => [
            'direction' => CashDirection::Out,
            'category' => CashCategory::OtherOutflow,
        ]);
    }
}
