<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Breed;
use App\Models\Customer;
use App\Models\Farm;
use App\Models\FeedItem;
use App\Models\Supplier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The demo dataset has to read as a real Indonesian dairy farm.
 *
 * Faker defaults slipped through in places where the reference data looked
 * hand-written: customer contacts came out as "Zakary Wehner MD" and
 * "Mr. Taurean Crist", a feed item was named "Konsentrat 414", and a farm
 * address had been mangled to "Jl. Raya義務animal No. 88". Individually trivial,
 * together they made the whole application look like a test fixture rather than
 * a product.
 */
class DemoDataQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Honorifics and Latin surnames that Faker emits, plus the English words
     * that only appear when a value was generated instead of written.
     */
    private const FAKER_TELLS = [
        'MD', 'DVM', 'PhD', 'Jr.', 'Sr.', 'III', 'IV',
        'Mr.', 'Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.',
    ];

    public function test_customer_contacts_are_named_people_not_generated_names(): void
    {
        foreach (Customer::all() as $customer) {
            $this->assertNotEmpty($customer->contact_person, "{$customer->code} has no contact person.");

            foreach (self::FAKER_TELLS as $tell) {
                $this->assertStringNotContainsString(
                    $tell,
                    $customer->contact_person,
                    "{$customer->code} has a generated contact name: {$customer->contact_person}",
                );
            }
        }
    }

    public function test_customer_emails_are_not_random_mailbox_addresses(): void
    {
        foreach (Customer::all() as $customer) {
            $this->assertStringEndsWith(
                '@contoh.id',
                $customer->email,
                "{$customer->code} has a generated email address: {$customer->email}",
            );
        }
    }

    public function test_reference_names_are_written_not_generated(): void
    {
        $names = FeedItem::pluck('name')
            ->merge(Breed::pluck('name'))
            ->merge(Supplier::pluck('name'))
            ->merge(Customer::pluck('name'))
            ->merge(Farm::pluck('name'));

        foreach ($names as $name) {
            foreach (self::FAKER_TELLS as $tell) {
                $this->assertStringNotContainsString($tell, $name, "Generated name: {$name}");
            }

            // A bare digit run is the shape of a Faker product name
            // ("Konsentrat 414"). A grade such as "8-16" is legitimate, so
            // only a run that is neither preceded by a hyphen nor glued to a
            // word counts.
            $this->assertDoesNotMatchRegularExpression(
                '/(?<![-\w])\d{2,}(?![-\w])/',
                $name,
                "Name looks generated: {$name}",
            );
        }
    }

    public function test_addresses_are_written_in_the_local_convention(): void
    {
        foreach (Farm::all() as $farm) {
            $this->assertMatchesRegularExpression(
                '/^Jl\./',
                $farm->address,
                "{$farm->code} address is not a local street address: {$farm->address}",
            );

            // Hangul survived a bad string replacement in a past seed.
            $this->assertDoesNotMatchRegularExpression(
                '/\p{Hangul}/u',
                $farm->address,
                "{$farm->code} address contains a stray character: {$farm->address}",
            );
        }
    }

    public function test_the_demo_dataset_is_reproducible(): void
    {
        $before = Farm::count();

        // Seeding again must not double anything up.
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($before, Farm::count(), 'Re-seeding duplicated the farm.');
        $this->assertSame(1, Farm::count(), 'The demo dataset should be a single farm.');
        $this->assertSame(5, Breed::count());
    }

    public function test_breed_origins_match_the_breed(): void
    {
        // Limosin is French; a duplicate filed under "UK" sat beside it when the
        // database still held rows from an earlier seeding.
        $origins = Breed::pluck('origin', 'name');

        $this->assertSame('France', $origins['Limosin']);
        $this->assertSame('Denmark', $origins['Holstein Friesian']);
        $this->assertSame('Indonesia', $origins['Madura']);
        $this->assertSame(
            $origins->count(),
            $origins->unique()->count(),
            'The same breed is defined twice.',
        );
    }

    public function test_every_demo_role_can_sign_in(): void
    {
        $accounts = [
            'owner' => 'owner@farm.test',
            'manager' => 'manager@farm.test',
            'worker' => 'worker@farm.test',
            'veterinarian' => 'vet@farm.test',
            'finance' => 'finance@farm.test',
        ];

        foreach ($accounts as $role => $email) {
            $this->assertDatabaseHas('users', ['email' => $email, 'role' => $role]);
        }
    }

    public function test_every_animal_carries_a_distinct_name(): void
    {
        // The name is how a keeper refers to an animal across the yard, the
        // milk book and the treatment record. A blank column rendered the whole
        // livestock index as a wall of dashes, which reads like unfinished data.
        $unnamed = Animal::where(fn ($query) => $query->whereNull('name')->orWhere('name', ''))->count();

        $this->assertSame(0, $unnamed, "{$unnamed} animals have no name.");

        // Two animals under one name would leave a keeper with no way to say
        // which one they meant.
        $repeated = Animal::whereNotNull('name')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('count(*) > 1')
            ->pluck('name');

        $this->assertTrue($repeated->isEmpty(), 'Animals share a name: '.$repeated->implode(', '));
    }

    public function test_animal_names_are_drawn_from_the_pool_not_generated(): void
    {
        $names = Animal::pluck('name')->filter()->unique();

        $this->assertGreaterThan(20, $names->count(), 'The herd has fewer names than expected.');

        // Nothing machine-made: no digits, no separators, no faker output.
        foreach ($names as $name) {
            $this->assertMatchesRegularExpression(
                '/^[A-Z][a-z]+( \d+)?$/',
                $name,
                "The name \"{$name}\" does not look like something a keeper would call an animal.",
            );
        }
    }
}
