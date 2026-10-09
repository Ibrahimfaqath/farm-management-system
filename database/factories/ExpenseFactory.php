<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Expense> */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'expense_number' => 'EXP-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'expense_date' => now()->subDays(fake()->numberBetween(0, 60))->toDateString(),
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount' => fake()->randomFloat(0, 150_000, 12_000_000),
            'description' => fake()->randomElement([
                'Pembelian obat hewan',
                'Gaji tenaga kerja',
                'Listrik kandang',
                'Perbaikan atap gudang',
                'Biaya BBM kendaraan',
                'Sewa alat pemotong rumput',
            ]),
            'vendor_name' => fake()->randomElement(['Toko Bangunan Jaya', 'PLN', 'CV Sinar Mandiri']),
            'reference' => 'REF-'.fake()->numerify('#####'),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'notes' => null,
        ];
    }
}
