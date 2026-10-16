<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            /*
             * Prefixed `T` so a code built here can never collide with one the
             * seeders write.
             *
             * `fake()->unique()` only guarantees uniqueness within a single
             * Faker instance, which is rebuilt for every test — it knows nothing
             * about the rows the seeder already inserted. Both draw from the same
             * 000-999 space, so a run would fail on a UNIQUE constraint about
             * one time in a hundred and for no reason connected to what it was
             * testing. `fake()` also has no memory of earlier `numerify` calls
             * made outside its own `unique()` chain.
             */
            'code' => 'TCUS-'.fake()->unique()->numerify('####'),
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
