<?php

namespace App\Services;

use App\Enums\HealthRecordStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\FeedItem;
use App\Models\HealthRecord;
use App\Models\Invoice;
use App\Models\PregnancyCheck;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vaccination;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Builds the "what needs attention today" list shown on the dashboard.
 */
class AttentionService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly FinanceService $finance,
        private readonly ProductionAnalyticsService $production,
    ) {}

    /**
     * Every entry carries the module its link is guarded by, so the dashboard
     * can drop the ones this role may not open. A Farm Worker has no finance or
     * feed module, and was being shown overdue invoices and unpaid supplier
     * balances — figures they can do nothing about, linking to pages that
     * answer 403.
     *
     * @return list<array{tone: string, title: string, description?: string, url: string}>
     */
    public function items(): array
    {
        return [
            ...$this->lowStockItems(),
            ...$this->overdueInvoices(),
            ...$this->outstandingPurchaseOrders(),
            ...$this->dueVaccinations(),
            ...$this->openHealthIssues(),
            ...$this->dueBirths(),
            ...$this->negativeStock(),
            ...$this->productionDeclines(),
            ...$this->unpaidSupplierLiabilities(),
        ];
    }

    /**
     * The same list, minus anything this role may not open.
     *
     * Showing a link the visitor cannot follow is worse than showing nothing:
     * it advertises a figure they are not cleared for, then answers 403 when
     * they act on it. A Farm Worker has no finance or feed module and was being
     * shown overdue invoice totals and unpaid supplier balances.
     *
     * The module is read back from the route's own `module:` middleware rather
     * than being declared alongside the item. Hand-tagged modules drifted
     * immediately — every one was off by one, so the unpaid supplier balance was
     * filed under `production` and reached exactly the role it must not.
     *
     * @return list<array{tone: string, title: string, description?: string, url: string}>
     */
    public function itemsFor(?Authenticatable $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return array_values(array_filter(
            $this->items(),
            function (array $item) use ($user): bool {
                $modules = $this->modulesForUrl($item['url']);

                // A route with no module guard is open to any signed-in role.
                return $modules === []
                    || array_intersect($modules, $user->role->modules()) !== [];
            },
        ));
    }

    /**
     * Which modules guard the route behind a URL.
     *
     * @return list<string>
     */
    private function modulesForUrl(string $url): array
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (\Throwable) {
            return [];
        }

        $modules = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'module:')) {
                $modules = array_merge($modules, explode(',', Str::after($middleware, 'module:')));
            }
        }

        return $modules;
    }

    /** @return list<array{tone: string, title: string, description?: string, url: string}> */
    private function lowStockItems(): array
    {
        return $this->inventory->lowStockItems()->take(3)->map(fn ($item) => [
            'tone' => ((float) $item->current_stock <= 0) ? 'error' : 'warning',
            'title' => "Stok {$item->name} menipis",
            'description' => 'Tersisa '.number_format((float) $item->current_stock, 1).' '.$item->unit->shortLabel()
                .', batas pemesanan ulang '.number_format((float) $item->reorder_point, 1).'.',
            'url' => route('inventory.index'),
        ])->all();
    }

    /** @return list<array{tone: string, title: string, description?: string, url: string}> */
    private function overdueInvoices(): array
    {
        $count = Invoice::query()->overdue()->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'tone' => 'error',
            'title' => $count.' faktur lewat jatuh tempo',
            'description' => 'Total piutang terlambat Rp'.number_format($this->finance->overdueReceivable(), 0).'.',
            'url' => route('invoices.index', ['overdue' => 1]),
        ]];
    }

    /** @return list<array{tone: string, title: string, description?: string, url: string}> */
    private function outstandingPurchaseOrders(): array
    {
        $purchaseOrders = PurchaseOrder::query()
            ->whereIn('status', [PurchaseOrderStatus::Approved->value, PurchaseOrderStatus::PartiallyReceived->value])
            ->with('supplier')
            ->get();

        if ($purchaseOrders->isEmpty()) {
            return [];
        }

        $items = [];

        foreach ($purchaseOrders as $order) {
            $outstanding = $order->totalOutstanding();

            if ($outstanding <= 0) {
                continue;
            }

            $items[] = [
                'tone' => 'warning',
                'title' => "{$order->po_number} belum diterima penuh",
                'description' => 'Sisa '.number_format($outstanding, 2).' unit dari '.$order->supplier->name.'.',
                'url' => route('purchase-orders.show', $order),
            ];
        }

        return array_slice($items, 0, 3);
    }

    /** @return list<array{tone: string, title: string, description?: string, url: string}> */
    private function dueVaccinations(): array
    {
        $overdue = Vaccination::query()
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<', now()->toDateString())
            ->count();

        if ($overdue === 0) {
            return [];
        }

        return [[
            'tone' => 'warning',
            'title' => $overdue.' vaksinasi lewat jadwal',
            'description' => 'Segera dijadwalkan ulang agar proteksi.kafka tetap terjaga.',
            'url' => route('vaccinations.index', ['overdue' => 1]),
        ]];
    }

    /** @return list<array{tone: string, title: string, description?: string, url: string}> */
    private function openHealthIssues(): array
    {
        $records = HealthRecord::query()
            ->where('status', HealthRecordStatus::Open->value)
            ->with('animal')
            ->orderBy('record_date')
            ->limit(3)
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        return $records->map(fn (HealthRecord $record) => [
            'tone' => 'warning',
            'title' => "Rekam terbuka: {$record->animal->animal_code}",
            'description' => $record->record_date->translatedFormat('d M Y').' · '.($record->symptoms ?? $record->diagnosis ?? 'tanpa keterangan'),
            'url' => route('health-records.show', $record),
        ])->all();
    }

    /** @return list<array{tone: string, title: string, description?: string, url: string}> */
    private function dueBirths(): array
    {
        $checks = PregnancyCheck::query()
            ->whereIn('result', ['pregnant', 'confirmed_pregnant'])
            ->whereNotNull('expected_due_date')
            ->whereDate('expected_due_date', '<=', now()->addDays(7)->toDateString())
            ->whereDoesntHave('birthRecord')
            ->with('animal')
            ->limit(3)
            ->get();

        if ($checks->isEmpty()) {
            return [];
        }

        return $checks->map(fn ($check) => [
            'tone' => 'info',
            'title' => "Perkiraan lahir: {$check->animal->animal_code}",
            'description' => $check->expectedDueDate()?->translatedFormat('d M Y').' · '.$check->result->label(),
            'url' => route('birth-records.create', ['pregnancy_check_id' => $check->id]),
        ])->all();
    }

    /** @return list<array{tone: string, title: string, description?: string, url: string}> */
    private function negativeStock(): array
    {
        $count = FeedItem::query()
            ->whereIn('id', StockMovement::query()->outbound()->distinct()->pluck('feed_item_id'))
            ->get()
            ->filter(fn (FeedItem $item) => $item->currentStock() < 0)
            ->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'tone' => 'error',
            'title' => $count.' item pakan berada di bawah nol',
            'description' => 'Pemakaian tercatat melebihi stok. Periksa catatan pemakaian dan batch.',
            'url' => route('inventory.movements'),
        ]];
    }

    /** @return list<array{tone: string, title: string, description?: string, url: string}> */
    private function productionDeclines(): array
    {
        $declines = $this->production->decliningAnimals();

        if ($declines === []) {
            return [];
        }

        return [[
            'tone' => 'info',
            'title' => count($declines).' hewan mengalami penurunan produksi',
            'description' => 'Penurunan produksi adalah fakta angka untuk diperiksa manusia, bukan diagnosis penyakit.',
            'url' => route('reports.production.index'),
        ]];
    }

    /** @return list<array{tone: string, title: string, description?: string, url: string}> */
    private function unpaidSupplierLiabilities(): array
    {
        $total = $this->finance->accountsPayable();

        if ($total <= 0) {
            return [];
        }

        return [[
            'tone' => 'warning',
            'title' => 'Utang ke pemasok Rp'.number_format($total, 0),
            'description' => 'Dari penerimaan barang terkonfirmasi yang belum dibayar.',
            'url' => route('supplier-payments.index'),
        ]];
    }
}
