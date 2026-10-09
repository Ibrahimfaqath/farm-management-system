<?php

namespace App\Http\Controllers;

use App\Enums\AnimalStatus;
use App\Enums\BreedingStatus;
use App\Enums\ExpenseCategory;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\Breed;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\FeedConsumptionItem;
use App\Models\FeedItem;
use App\Models\GoodsReceiptItem;
use App\Models\Invoice;
use App\Models\MilkProduction;
use App\Services\FeedConsumptionService;
use App\Services\FinanceService;
use App\Services\InventoryService;
use App\Services\ProductionAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    // ------------------------------------------------------------------
    // Animal report
    // ------------------------------------------------------------------

    public function animal(Request $request): View
    {
        $farmId = $request->integer('farm_id') ?: null;

        $base = Animal::query()->when($farmId, fn ($q) => $q->where('farm_id', $farmId));

        $byStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byProduction = (clone $base)
            ->where('status', AnimalStatus::Active->value)
            ->selectRaw('production_status, COUNT(*) as total')
            ->groupBy('production_status')
            ->pluck('total', 'production_status');

        $byBreeding = (clone $base)
            ->where('status', AnimalStatus::Active->value)
            ->selectRaw('breeding_status, COUNT(*) as total')
            ->groupBy('breeding_status')
            ->pluck('total', 'breeding_status');

        $byBreed = Breed::query()
            ->withCount(['animals' => fn ($q) => $q->when($farmId, fn ($sub) => $sub->where('farm_id', $farmId))])
            ->orderByDesc('animals_count')
            ->get();

        $byBarn = Barn::query()
            ->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
            ->with(['farm'])
            ->withCount(['animals' => fn ($q) => $q->where('status', AnimalStatus::Active->value)])
            ->orderByDesc('animals_count')
            ->get();

        $ageBuckets = (clone $base)
            ->where('status', AnimalStatus::Active->value)
            ->whereNotNull('date_of_birth')
            ->get(['date_of_birth'])
            ->map(function (Animal $animal): string {
                $months = (int) floor($animal->date_of_birth->diffInMonths(now()));

                return match (true) {
                    $months < 12 => '< 1 tahun',
                    $months < 36 => '1–2 tahun',
                    $months < 60 => '3–4 tahun',
                    $months < 120 => '5–9 tahun',
                    default => '> 9 tahun',
                };
            })
            ->countBy()
            ->sortKeys()
            ->all();

        $productionWindow = $this->window($request, 30);
        $topProducers = Animal::query()
            ->lactating()
            ->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
            ->get(['id', 'animal_code', 'name', 'barn_id', 'breed_id'])
            ->map(function (Animal $animal) use ($productionWindow): ?array {
                $total = (float) MilkProduction::query()
                    ->where('animal_id', $animal->getKey())
                    ->whereDate('production_date', '>=', $productionWindow['from']->toDateString())
                    ->whereDate('production_date', '<=', $productionWindow['to']->toDateString())
                    ->sum('liters');

                $days = $productionWindow['from']->diffInDays($productionWindow['to']) + 1;

                return [
                    'animal' => $animal,
                    'total' => round($total, 2),
                    'average' => round($total / $days, 2),
                ];
            })
            ->filter(fn ($row) => $row['total'] > 0)
            ->sortByDesc('total')
            ->take(15)
            ->values();

        return view('reports.animal', [
            'farmId' => $farmId,
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'byStatus' => $byStatus,
            'byProduction' => $byProduction,
            'byBreeding' => $byBreeding,
            'byBreed' => $byBreed,
            'byBarn' => $byBarn,
            'ageBuckets' => $ageBuckets,
            'topProducers' => $topProducers,
            'window' => $productionWindow,
            'statusOptions' => $this->enumOptions(AnimalStatus::class),
            'productionOptions' => $this->enumOptions(ProductionStatus::class),
            'breedingOptions' => $this->enumOptions(BreedingStatus::class),
            'sexOptions' => $this->enumOptions(Sex::class),
        ]);
    }

    // ------------------------------------------------------------------
    // Feed report
    // ------------------------------------------------------------------

    public function feed(Request $request, InventoryService $inventory, FeedConsumptionService $consumption): View
    {
        $farmId = $request->integer('farm_id') ?: null;
        $window = $this->window($request, 30);

        $rows = FeedItem::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (FeedItem $item) use ($inventory, $consumption, $window, $farmId) {
                $current = $inventory->stockOf($item);

                $purchaseQty = (float) GoodsReceiptItem::query()
                    ->where('feed_item_id', $item->getKey())
                    ->whereHas('goodsReceipt', fn ($q) => $q
                        ->where('status', 'posted')
                        ->whereDate('received_date', '>=', $window['from']->toDateString())
                        ->whereDate('received_date', '<=', $window['to']->toDateString()))
                    ->sum('quantity_received');

                $purchaseAmount = (float) GoodsReceiptItem::query()
                    ->where('feed_item_id', $item->getKey())
                    ->whereHas('goodsReceipt', fn ($q) => $q
                        ->where('status', 'posted')
                        ->whereDate('received_date', '>=', $window['from']->toDateString())
                        ->whereDate('received_date', '<=', $window['to']->toDateString()))
                    ->sum('line_total');

                $consumptionQty = (float) FeedConsumptionItem::query()
                    ->where('feed_item_id', $item->getKey())
                    ->whereHas('feedConsumption', fn ($q) => $q
                        ->whereDate('consumption_date', '>=', $window['from']->toDateString())
                        ->whereDate('consumption_date', '<=', $window['to']->toDateString())
                        ->when($farmId, fn ($sub) => $sub->where('farm_id', $farmId)))
                    ->sum('quantity');

                $consumptionAmount = (float) FeedConsumptionItem::query()
                    ->where('feed_item_id', $item->getKey())
                    ->whereHas('feedConsumption', fn ($q) => $q
                        ->whereDate('consumption_date', '>=', $window['from']->toDateString())
                        ->whereDate('consumption_date', '<=', $window['to']->toDateString())
                        ->when($farmId, fn ($sub) => $sub->where('farm_id', $farmId)))
                    ->sum('line_total');

                $daily = $consumption->averageDailyConsumption($item, $window['from']->diffInDays($window['to']) + 1, $farmId);

                $item->setAttribute('current_stock', $current);
                $item->setAttribute('usable_stock', $inventory->usableStock($item));
                $item->setAttribute('purchase_qty', round($purchaseQty, 2));
                $item->setAttribute('purchase_amount', round($purchaseAmount, 2));
                $item->setAttribute('consumption_qty', round($consumptionQty, 2));
                $item->setAttribute('consumption_amount', round($consumptionAmount, 2));
                $item->setAttribute('daily_consumption', $daily);
                $item->setAttribute('days_remaining', $daily > 0 ? (int) floor($current / $daily) : null);
                $item->setAttribute('stock_value', round($current * (float) $item->price_per_unit, 2));
                $item->setAttribute('is_low', (float) $item->reorder_point > 0 && $current <= (float) $item->reorder_point);

                return $item;
            });

        return view('reports.feed', [
            'farmId' => $farmId,
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'rows' => $rows,
            'window' => $window,
            'totalStockValue' => (float) $rows->sum('stock_value'),
            'totalConsumptionAmount' => (float) $rows->sum('consumption_amount'),
            'totalPurchaseAmount' => (float) $rows->sum('purchase_amount'),
            'lowStockCount' => $rows->where('is_low', true)->count(),
        ]);
    }

    // ------------------------------------------------------------------
    // Production report
    // ------------------------------------------------------------------

    public function production(Request $request, ProductionAnalyticsService $analytics): View
    {
        $farmId = $request->integer('farm_id') ?: null;
        $from = $request->date('date_from') ?? now()->startOfMonth()->toDateString();
        $to = $request->date('date_to') ?? now()->toDateString();

        $fromDate = Carbon::parse($from);
        $toDate = Carbon::parse($to);

        $daily = $analytics->dailyTotals($farmId, $fromDate, $toDate);
        $grouping = $request->string('group_by')->value ?: ($daily !== [] && count($daily) <= 45 ? 'day' : 'week');

        $series = $this->groupSeries($daily, $grouping);

        return view('reports.production', [
            'farmId' => $farmId,
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'summary' => $analytics->summary($farmId, $fromDate, $toDate),
            'trend' => $analytics->compareWindows($farmId, $fromDate, $toDate),
            'daily' => $daily,
            'series' => $series,
            'grouping' => $grouping,
            'byBarn' => $analytics->byBarn($farmId, $fromDate, $toDate),
            'topAnimals' => $analytics->topAnimals($farmId, $fromDate, $toDate, 15),
            'declining' => $analytics->decliningAnimals($farmId),
            'from' => $from,
            'to' => $to,
        ]);
    }

    // ------------------------------------------------------------------
    // Financial report
    // ------------------------------------------------------------------

    public function financial(Request $request, FinanceService $finance): View
    {
        $from = $request->date('date_from') ?? now()->startOfYear()->toDateString();
        $to = $request->date('date_to') ?? now()->toDateString();

        $summary = $finance->periodSummary($from, $to);

        $expensesByCategory = Expense::query()
            ->between($from, $to)
            ->groupBy('category')
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as records')
            ->orderByDesc('total')
            ->get();

        $revenueByCustomer = Invoice::query()
            ->whereDate('invoice_date', '>=', $from)
            ->whereDate('invoice_date', '<=', $to)
            ->where('status', '!=', 'cancelled')
            ->with('customer')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, SUM(total) as total, SUM(paid_amount) as paid')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'customer' => $row->customer,
                'total' => (float) $row->total,
                'paid' => (float) $row->paid,
            ]);

        $cashByMonth = CashTransaction::query()
            ->whereDate('transaction_date', '>=', Carbon::parse($from)->startOfMonth()->toDateString())
            ->whereDate('transaction_date', '<=', $to)
            ->get()
            ->groupBy(fn ($t) => $t->transaction_date->format('Y-m'))
            ->map(fn ($group) => [
                'in' => round((float) $group->where('direction', 'in')->sum('amount'), 2),
                'out' => round((float) $group->where('direction', 'out')->sum('amount'), 2),
            ]);

        return view('reports.financial', [
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
            'expensesByCategory' => $expensesByCategory->map(fn ($row) => [
                'category' => $row->category instanceof \BackedEnum ? $row->category->value : $row->category,
                'total' => $row->total,
                'records' => $row->records,
            ]),
            'revenueByCustomer' => $revenueByCustomer,
            'cashByMonth' => $cashByMonth,
            'receivable' => $finance->accountsReceivable(),
            'payable' => $finance->accountsPayable(),
            'overdue' => $finance->overdueReceivable(),
            'cash' => $finance->cashBalance(),
            'expenseCategories' => $this->enumOptions(ExpenseCategory::class),
        ]);
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    private function window(Request $request, int $defaultDays): array
    {
        return [
            'from' => $request->date('date_from') ?? now()->subDays($defaultDays - 1)->startOfDay(),
            'to' => $request->date('date_to') ?? now()->endOfDay(),
        ];
    }

    /**
     * @param  list<array{date: string, total: float, records: int}>  $daily
     * @return list<array{label: string, total: float, records: int}>
     */
    private function groupSeries(array $daily, string $grouping): array
    {
        if ($grouping === 'raw') {
            return array_map(fn ($row) => [
                'label' => Carbon::parse($row['date'])->format('d M'),
                'total' => $row['total'],
                'records' => $row['records'],
            ], $daily);
        }

        $key = $grouping === 'month' ? 'Y-m' : 'o-W';

        return collect($daily)
            ->groupBy(fn ($row) => Carbon::parse($row['date'])->format($key))
            ->map(function ($group, $groupKey) use ($grouping): array {
                $first = Carbon::parse($group->first()['date']);

                return [
                    'label' => $grouping === 'month' ? $first->format('M Y') : $first->startOfWeek()->format('d M'),
                    'total' => round((float) $group->sum('total'), 2),
                    'records' => (int) $group->sum('records'),
                ];
            })
            ->values()
            ->all();
    }
}
