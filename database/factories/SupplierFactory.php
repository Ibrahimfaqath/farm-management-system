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
            'code' => 'TSUP-'.fake()->unique()->numerify('####'),
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
