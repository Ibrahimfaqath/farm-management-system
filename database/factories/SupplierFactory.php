<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'SUP-'.fake()->unique()->numerify('###'),
            'name' => fake()->randomElement([
                'CV Sumber Pakan Sejahtera',
                'UD Tani Makmur',
                'PT Nijag Agrikultura',
                'CV Mitraternak Jaya',
                'UD Berkah Pakan',
                'PT Wahana Tani',
            ]).' '.fake()->randomNumber(1, 99),
            'contact_person' => fake()->name(),
            'phone' => '08'.fake()->numerify('##########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Sidoarjo', 'Malang', 'Gresik', 'Kediri', 'Jember']),
            'tax_number' => fake()->numerify('01.234.567.8-901.000'),
            'payment_terms_days' => fake()->randomElement([0, 7, 14, 30]),
            'category' => 'feed',
            'is_active' => true,
            'notes' => null,
        ];
    }
}
