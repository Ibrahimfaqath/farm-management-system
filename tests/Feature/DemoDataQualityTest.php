<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\BirthRecord;
use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\FeedConsumption;
use App\Models\FeedItem;
use App\Models\GoodsReceipt;
use App\Models\HealthRecord;
use App\Models\Income;
use App\Models\InventoryBatch;
use App\Models\Invoice;
use App\Models\MilkProduction;
use App\Models\Payment;
use App\Models\PregnancyCheck;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Treatment;
use App\Models\Vaccination;
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
        $this->assertSame(8, Breed::count());
        $this->assertGreaterThanOrEqual(70, Animal::count());
    }

    public function test_breed_origins_match_the_breed(): void
    {
        // The catalogue is dairy cattle only; a beef breed filed under a
        // second origin used to slip in when the database held old rows.
        $origins = Breed::pluck('origin', 'name');

        $this->assertSame('Switzerland', $origins['Brown Swiss']);
        $this->assertSame('Netherlands', $origins['Holstein Friesian']);
        $this->assertSame('Pakistan', $origins['Sahiwal']);
        $this->assertSame('Jersey Island', $origins['Jersey']);
        $this->assertFalse($origins->keys()->contains('Limosin'));
        $this->assertFalse($origins->keys()->contains('Madura'));
        $this->assertSame(8, $origins->count());
        $this->assertSame(0, Breed::where('species', '!=', 'cattle')->count());
        $this->assertSame(0, Breed::where('is_active', false)->count());
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

        $this->assertSame(
            0,
            Animal::query()
                ->whereNull('breed_id')
                ->orWhereNull('barn_id')
                ->orWhereNull('date_of_birth')
                ->count(),
            'Some animals are missing a breed, barn, or date of birth.',
        );

        $this->assertSame(
            0,
            Animal::query()
                ->active()
                ->whereDoesntHave('breed', fn ($query) => $query->where('is_active', true))
                ->count(),
            'An active animal points at an inactive breed.',
        );
    }

    public function test_all_modules_have_realistic_data_and_sales_are_cow_milk_only(): void
    {
        $minimums = [
            Barn::class => 7,
            FeedItem::class => 15,
            Supplier::class => 3,
            PurchaseOrder::class => 4,
            GoodsReceipt::class => 3,
            InventoryBatch::class => 15,
            FeedConsumption::class => 30,
            StockMovement::class => 50,
            HealthRecord::class => 15,
            Vaccination::class => 60,
            Treatment::class => 5,
            BreedingRecord::class => 10,
            PregnancyCheck::class => 6,
            BirthRecord::class => 10,
            MilkProduction::class => 1_500,
            Customer::class => 7,
            SalesOrder::class => 10,
            SalesOrderItem::class => 10,
            Invoice::class => 7,
            Payment::class => 5,
            Expense::class => 12,
            Income::class => 3,
            CashTransaction::class => 20,
            ActivityLog::class => 20,
        ];

        foreach ($minimums as $model => $minimum) {
            $this->assertGreaterThanOrEqual(
                $minimum,
                $model::count(),
                class_basename($model).' does not have enough seeded data.',
            );
        }

        $milk = Product::where('code', Product::COW_MILK_CODE)->firstOrFail();

        $this->assertSame(1, Product::count(), 'The catalogue should only contain cow milk.');
        $this->assertSame(1, Product::where('is_active', true)->count());
        $this->assertSame('Susu Sapi Segar', $milk->name);
        $this->assertSame(0, SalesOrderItem::query()->where('product_id', '!=', $milk->id)->count());
        $this->assertSame(0, SalesOrderItem::query()->whereNull('product_id')->count());
        $this->assertSame(0, SalesOrderItem::query()->where('unit', '!=', 'liter')->count());

        foreach (SalesOrderItem::all() as $item) {
            $this->assertStringContainsStringIgnoringCase('susu', $item->description);
        }

        $this->actingAs($this->owner())
            ->get(route('sales-orders.create'))
            ->assertOk()
            ->assertSee('Susu Sapi Segar')
            ->assertSee('data-unit="liter"', false)
            ->assertDontSee('Yoghurt')
            ->assertDontSee('Pupuk Kotor');

        $otherProduct = Product::create([
            'code' => 'PRD-UJI',
            'name' => 'Produk Uji',
            'unit' => 'liter',
            'default_price' => 10_000,
            'category' => 'other',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner())
            ->post(route('sales-orders.store'), [
                'customer_id' => Customer::firstOrFail()->id,
                'order_date' => now()->toDateString(),
                'items' => [[
                    'product_id' => $otherProduct->id,
                    'description' => 'Produk selain susu sapi',
                    'quantity' => 1,
                    'unit' => 'liter',
                    'unit_price' => 10_000,
                ]],
            ])
            ->assertSessionHasErrors('items.0.product_id');
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
