<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\FeedItem;
use App\Models\GoodsReceipt;
use App\Models\InventoryBatch;
use App\Models\Invoice;
use App\Models\MilkProduction;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\InventoryService;
use App\Services\ProductionAnalyticsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_dataset_seeds_without_error_and_preserves_business_invariants(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(30, Animal::count());
        $this->assertSame(6, User::count());

        // Scenario 1: a purchase order is only partially received.
        $partial = PurchaseOrder::query()
            ->where('status', 'partially_received')
            ->with('items')
            ->first();

        $this->assertNotNull($partial, 'Seeded data must include a partially received purchase order.');
        $this->assertSame(1000.0, $partial->totalOutstanding());

        // Scenario 2: consumption is traceable to a batch.
        $inventory = app(InventoryService::class);
        $concentrate = FeedItem::where('code', 'FD-KNS01')->firstOrFail();

        // 4.000 kg diterima, 1.400 kg dipakai (336 + 264 harian + 800 skenario).
        $this->assertSame(2600.0, $inventory->stockOf($concentrate));

        $batch = InventoryBatch::where('batch_code', 'KNS-2409-A')->firstOrFail();
        $this->assertSame(2600.0, (float) $batch->quantity_remaining);

        $this->assertGreaterThan(0, StockMovement::where('type', 'consumption')->count());

        // Scenario 3 and 5: receivables exist and at least one invoice is overdue.
        $finance = app(FinanceService::class);
        $this->assertGreaterThan(0, Invoice::count());
        $this->assertGreaterThan(0, Invoice::query()->overdue()->count());
        $this->assertGreaterThan(0, $finance->accountsReceivable());

        // Scenario 6: production recorded with a detectable decline.
        $this->assertGreaterThan(200, MilkProduction::count());
        $this->assertNotEmpty(
            app(ProductionAnalyticsService::class)->decliningAnimals(null)
        );

        // Scenario 7: exited animals remain with their history.
        $sold = Animal::where('status', 'sold')->firstOrFail();
        $this->assertDatabaseHas('milk_productions', ['animal_id' => $sold->id]);

        // Receivables exceed cash received: the two are not the same figure.
        $this->assertNotSame($finance->accountsReceivable(), $finance->cashBalance());

        $this->assertGreaterThan(0, GoodsReceipt::where('status', 'posted')->count());
    }
}
