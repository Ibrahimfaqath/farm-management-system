<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

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
            'code' => 'TCUS-'.fake()->unique()->numerify('###'),
            'name' => fake()->randomElement([
                'Koperasi Susu Sejahtera',
                'Toko Segar Jaya',
                'Warung Sumber ASI',
                'CV Dairy Grosir',
                'Rumah Susu Kita',
            ]).' '.fake()->randomNumber(1, 99),
            'type' => fake()->randomElement(['retail', 'wholesale', 'partner']),
            'contact_person' => fake()->name(),
            'phone' => '08'.fake()->numerify('##########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Sidoarjo', 'Surabaya', 'Malang', 'Gresik']),
            'credit_limit' => fake()->randomFloat(0, 10_000_000, 150_000_000),
            'payment_terms_days' => fake()->randomElement([0, 7, 14, 30]),
            'is_active' => true,
            'notes' => null,
        ];
    }
}
