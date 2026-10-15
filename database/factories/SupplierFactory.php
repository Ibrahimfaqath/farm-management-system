<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /*
     * Prefixed `T`, in a range the demo seeder never writes.
     *
     * These used to share their prefixes and their numbering with the seeder,
     * and `fake()->unique()` does not prevent a collision: its counter is per
     * Faker instance, and Laravel supplies a fresh one per test. Two tests that
     * both seed the demo data and then build a record each restart at 001, and
     * the second hits a UNIQUE violation. Which test lost depended on the order
     * the suite ran in.
     */
    /** @return array<string, mixed> */
    public function definition(): array

    /*
     * Prefixed `T`, in a range the demo seeder never writes.

     * These used to share their prefixes and their numbering with the seeder,
     * and `fake()->unique()` does not prevent a collision: its counter is per
     * Faker instance, and Laravel supplies a fresh one per test. Two tests that
     * both seed the demo data and then build a record each restart at 001, and
     * the second hits a UNIQUE violation. Which test lost depended on the order
     * the suite ran in.
     */
    {
        return [
            'code' => 'TSUP-'.fake()->unique()->numerify('###'),
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
