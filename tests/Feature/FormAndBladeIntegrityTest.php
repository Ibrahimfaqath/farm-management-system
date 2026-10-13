<?php

namespace Tests\Feature;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UnitOfMeasure;
use App\Models\Customer;
use App\Models\FeedItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Two bugs that no GET-render test could catch.
 *
 * 1. A Form Request that called `Rule::enum()` without importing
 *    `Illuminate\Validation\Rule` resolved to `App\Http\Requests\Rule` and threw
 *    "Class not found", so saving a purchase order or a sales order returned a
 *    500 instead of validating. Every other request in the app had the import,
 *    which is exactly why these two were missed.
 *
 * 2. A Blade component tag that the tag compiler cannot parse is emitted into
 *    the page as literal text instead of being rendered. The page still returns
 *    200, so only an assertion on the markup catches it.
 */
class FormAndBladeIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function formRequestProvider(): array
    {
        $cases = [];

        foreach (glob(app_path('Http/Requests/*.php')) ?: [] as $file) {
            $class = 'App\\Http\\Requests\\'.basename($file, '.php');
            $cases[$class] = [$class, $file];
        }

        return $cases;
    }

    public function test_form_requests_resolve_every_class_they_reference(): void
    {
        $failures = [];

        foreach (glob(app_path('Http/Requests/*.php')) ?: [] as $file) {
            $class = 'App\\Http\\Requests\\'.basename($file, '.php');
            $source = file_get_contents($file);

            // `Rule::` inside a namespaced file means `App\Http\Requests\Rule`
            // unless the validation Rule was imported.
            if (str_contains($source, 'Rule::') && ! str_contains($source, 'use Illuminate\\Validation\\Rule;')) {
                $failures[] = basename($file).' calls Rule:: without importing Illuminate\Validation\Rule';
            }

            // Every type-hinted class in a signature must exist.
            try {
                $reflection = new ReflectionClass($class);
            } catch (\Throwable) {
                continue;
            }

            foreach ($reflection->getMethods() as $method) {
                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->getType();

                    if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                        continue;
                    }

                    $name = $type->getName();

                    // `self`/`static`/`parent` are keywords, not class names.
                    if (in_array($name, ['self', 'static', 'parent'], true)) {
                        continue;
                    }

                    if (! class_exists($name) && ! interface_exists($name) && ! enum_exists($name)) {
                        $failures[] = basename($file).'::'.$method->getName().' type-hints missing '.$name;
                    }
                }
            }
        }

        $this->assertSame([], $failures, "Unresolvable references:\n".implode("\n", $failures));
    }

    public function test_rules_can_actually_be_built_for_every_form_request(): void
    {
        $this->seed(DatabaseSeeder::class);

        $failures = [];
        $user = $this->owner();

        foreach (glob(app_path('Http/Requests/*.php')) ?: [] as $file) {
            $class = 'App\\Http\\Requests\\'.basename($file, '.php');

            $request = $class::createFrom(Request::create('/'));
            $request->setUserResolver(fn () => $user);

            try {
                // Building the rule array is what blew up on the missing import.
                $rules = $request->rules();

                foreach ($rules as $key => $rule) {
                    foreach ((array) $rule as $single) {
                        if (is_string($single) && str_contains($single, 'Rule:')) {
                            $failures[] = basename($file).' left an unbuilt rule on '.$key;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $failures[] = basename($file).'::rules() threw '.$e::class.': '.$e->getMessage();
            }
        }

        $this->assertSame([], $failures, "Rules that could not be built:\n".implode("\n", $failures));
    }

    public function test_saving_a_purchase_order_validates_instead_of_erroring(): void
    {
        $this->seed(DatabaseSeeder::class);

        $supplier = Supplier::firstOrFail();
        $feedItem = FeedItem::firstOrFail();

        $response = $this->actingAs($this->owner())->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['feed_item_id' => $feedItem->id, 'quantity' => 100, 'unit' => UnitOfMeasure::Kilogram->value, 'unit_price' => 9000],
            ],
        ]);

        // The regression was a 500; a created order redirects, invalid input 302s back.
        $response->assertRedirect();
        $this->assertNotSame(500, $response->getStatusCode());

        $this->assertDatabaseHas('purchase_orders', ['supplier_id' => $supplier->id]);
    }

    public function test_saving_a_sales_order_validates_instead_of_erroring(): void
    {
        $this->seed(DatabaseSeeder::class);

        $customer = Customer::firstOrFail();
        $feedItem = FeedItem::firstOrFail();

        $response = $this->actingAs($this->owner())->post(route('sales-orders.store'), [
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['feed_item_id' => $feedItem->id, 'quantity' => 10, 'unit' => UnitOfMeasure::Kilogram->value, 'unit_price' => 9000],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales_orders', ['customer_id' => $customer->id]);
    }

    public function test_no_rendered_page_contains_a_literal_component_tag(): void
    {
        $this->seed(DatabaseSeeder::class);

        $failures = [];
        $seen = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || $route->getName() === null) {
                continue;
            }

            // Only detail routes that still need a model binding are skipped;
            // PageRenderTest already covers those with real records.
            if (preg_match('#\{(\w+)\}#', $route->uri()) === 1) {
                continue;
            }

            $response = $this->actingAs($this->owner())->get('/'.ltrim($route->uri(), '/'));

            if (! $response->isSuccessful()) {
                continue;
            }

            $seen++;
            $html = $response->getContent();

            if (preg_match('/<x-[a-z][\w.-]*/i', $html, $match) === 1) {
                $failures[] = $route->getName().' renders a literal tag: '.substr($match[0], 0, 60);
            }
        }

        $this->assertGreaterThan(20, $seen, 'Too few collection pages were checked for the test to mean anything.');
        $this->assertSame([], $failures, "Blade failed to compile:\n".implode("\n", $failures));
    }

    public function test_receiving_goods_against_a_chosen_order_loads_that_order(): void
    {
        $this->seed(DatabaseSeeder::class);

        $supplier = Supplier::firstOrFail();
        $feedItem = FeedItem::firstOrFail();

        $makeOrder = function (float $quantity) use ($supplier, $feedItem) {
            $order = PurchaseOrder::factory()->create([
                'supplier_id' => $supplier->id,
                'status' => PurchaseOrderStatus::Approved,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'feed_item_id' => $feedItem->id,
                'quantity' => $quantity,
                'unit' => UnitOfMeasure::Kilogram->value,
                'unit_price' => 9000,
            ]);

            $order->recalculateTotals();

            return $order->fresh();
        };

        $first = $makeOrder(1000);
        $second = $makeOrder(2000);

        // A query-closure that calls findOrFail() returns a model, so the
        // trailing first() silently loaded the earliest order instead.
        $html = $this->actingAs($this->owner())
            ->get(route('goods-receipts.create', ['purchase_order_id' => $second->id]))
            ->assertOk()
            ->getContent();

        // The chosen order is the selected option and heads the summary panel.
        $this->assertMatchesRegularExpression(
            '/<option value="'.$second->id.'" selected/',
            $html,
            'The requested purchase order was not the selected one.'
        );
        $this->assertStringContainsString('Sisa pesanan '.$second->po_number, $html);
        $this->assertStringContainsString('2,000.00', $html, 'The outstanding quantity of the chosen order is missing.');
        $this->assertStringContainsString('name="supplier_id"', $html);

        // Its line rows carry that order's item, not the earliest order's.
        $this->assertStringContainsString(
            'name="items[0][purchase_order_item_id]" value="'.$second->items()->first()->id.'"',
            $html
        );
    }

    public function test_an_unknown_order_id_is_a_404_not_a_silent_fallback(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs($this->owner())
            ->get(route('goods-receipts.create', ['purchase_order_id' => 999999]))
            ->assertNotFound();
    }
}
