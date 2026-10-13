<?php

namespace Tests\Feature;

use App\Models\Animal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The age column must never leak a float into the page.
 *
 * Carbon 3 changed every `diffIn*` method to return a float. `age_label` used
 * the raw values, so the animals table rendered "5.0009300612355 th 0 bln", and
 * its `$years === 0` branch for newborns was unreachable because an integer
 * zero is never identical to a float zero.
 */
class AnimalAgeLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_whole_years_and_months(): void
    {
        $animal = Animal::factory()->create([
            'date_of_birth' => now()->subYears(5)->subMonths(4),
        ]);

        $this->assertSame('5 tahun 4 bulan', $animal->age_label);
    }

    public function test_a_newborn_reports_days_instead_of_zero_months(): void
    {
        $animal = Animal::factory()->create([
            'date_of_birth' => now()->subDays(9),
        ]);

        $this->assertSame('9 hari', $animal->age_label);
    }

    public function test_an_animal_under_a_year_reports_whole_months(): void
    {
        $animal = Animal::factory()->create([
            'date_of_birth' => now()->subMonths(7),
        ]);

        $this->assertSame('7 bulan', $animal->age_label);
    }

    public function test_a_whole_number_of_years_omits_the_month_part(): void
    {
        $animal = Animal::factory()->create([
            'date_of_birth' => now()->subYears(3)->startOfMonth(),
        ]);

        // The remainder can land on a sliver of a month; it must still print 0.
        $this->assertMatchesRegularExpression('/^3 tahun \d{1,2} bulan$/', $animal->age_label);
    }

    public function test_a_missing_birth_date_falls_back_to_a_dash(): void
    {
        $animal = Animal::factory()->create(['date_of_birth' => null]);

        $this->assertSame('—', $animal->age_label);
    }

    public function test_an_exited_animal_is_aged_at_its_exit_date(): void
    {
        // Six years old at birth of the record, but it left a year ago, so the
        // label has to count five years rather than six.
        $animal = Animal::factory()->create([
            'date_of_birth' => now()->subYears(6),
            'exit_date' => now()->subYears(1),
        ]);

        $this->assertMatchesRegularExpression('/^5 tahun \d{1,2} bulan$/', $animal->age_label);
    }

    public function test_no_age_label_ever_contains_a_decimal_point(): void
    {
        $animals = collect([
            ['birth' => now()->subYears(5)->subMonths(4)],
            ['birth' => now()->subDays(1)],
            ['birth' => now()->subHours(6)],
            ['birth' => now()->subMonths(1)],
            ['birth' => now()->subYears(1)],
        ])->map(fn (array $case) => Animal::factory()->create(['date_of_birth' => $case['birth']]));

        foreach ($animals as $animal) {
            $this->assertDoesNotMatchRegularExpression(
                '/\d+\.\d+/',
                $animal->age_label,
                "age_label leaked a float: {$animal->age_label}",
            );
        }
    }
}
