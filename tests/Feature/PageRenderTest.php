<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Barn;
use App\Models\BirthRecord;
use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\Customer;
use App\Models\Farm;
use App\Models\FeedConsumption;
use App\Models\FeedItem;
use App\Models\GoodsReceipt;
use App\Models\HealthRecord;
use App\Models\Invoice;
use App\Models\PregnancyCheck;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    private function sampleId(string $param): ?int
    {
        return match ($param) {
            'farm' => Farm::value('id'),
            'barn' => Barn::value('id'),
            'breed' => Breed::value('id'),
            'animal' => Animal::value('id'),
            'health_record' => HealthRecord::value('id'),
            'breeding_record' => BreedingRecord::value('id'),
            'pregnancy_check' => PregnancyCheck::value('id'),
            'birth_record' => BirthRecord::value('id'),
            'feed_item' => FeedItem::value('id'),
            'supplier' => Supplier::value('id'),
            'purchase_order' => PurchaseOrder::value('id'),
            'goods_receipt' => GoodsReceipt::value('id'),
            'feed_consumption' => FeedConsumption::value('id'),
            'customer' => Customer::value('id'),
            'sales_order' => SalesOrder::value('id'),
            'invoice' => Invoice::value('id'),
            default => null,
        };
    }

    public function test_every_get_page_renders_for_every_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $users = User::all();
        $failures = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (str_starts_with($uri, 'api') || str_starts_with($uri, '_') || $uri === 'up') {
                continue;
            }

            if (in_array($route->methods()[0] ?? '', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                continue;
            }

            foreach ($users as $user) {
                $params = [];

                foreach ($route->parameterNames() as $name) {
                    $id = $this->sampleId($name);

                    if ($id === null) {
                        continue 2;
                    }

                    $params[$name] = $id;
                }

                try {
                    $response = $this->actingAs($user)->get('/'.ltrim($uri, '/'), $params);

                    if ($response->getStatusCode() >= 500) {
                        $failures[] = $uri.' ['.$user->role->value.'] → '.$this->rootCause($response);
                    }
                } catch (\Throwable $e) {
                    $failures[] = $uri.' ['.$user->role->value.'] ! '.$e::class.': '.mb_substr($e->getMessage(), 0, 140);
                }
            }
        }

        $this->assertSame([], $failures, "Pages returned server errors:\n".implode("\n", array_unique($failures)));
    }

    /**
     * A page that takes no parameters has to answer, whatever the records look
     * like. Three create pages answered 404 because the matching `{model}` show
     * route was declared first and swallowed the static segment, and a render
     * test that only fails on 5xx let them pass: the URL still resolved to a
     * route, it just bound "create" as a model id.
     */
    public function test_parameterless_get_routes_are_reachable(): void
    {
        $this->seed(DatabaseSeeder::class);

        $failures = [];
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || $route->parameterNames() !== []) {
                continue;
            }

            if (str_starts_with($route->uri(), 'api') || str_starts_with($route->uri(), '_') || $route->uri() === 'up') {
                continue;
            }

            $checked++;
            $status = $this->actingAs($this->owner())->get('/'.ltrim($route->uri(), '/'))->getStatusCode();

            if ($status === 404) {
                $failures[] = '/'.$route->uri().' returned 404 — a parameterised route declared earlier is shadowing it';
            }
        }

        $this->assertGreaterThan(20, $checked, 'Too few routes were checked for the test to mean anything.');
        $this->assertSame([], $failures, "Unreachable pages:\n".implode("\n", $failures));
    }

    private function rootCause($response): string
    {
        $exception = $response->exception;

        if (! $exception) {
            return 'HTTP '.$response->getStatusCode();
        }

        $previous = $exception;

        while ($previous->getPrevious()) {
            $previous = $previous->getPrevious();
        }

        return $previous::class.': '.mb_substr($previous->getMessage(), 0, 160)
            .' @ '.basename($previous->getFile()).':'.$previous->getLine();
    }
}
