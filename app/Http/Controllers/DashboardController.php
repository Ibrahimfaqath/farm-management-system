<?php

namespace App\Http\Controllers;

use App\Enums\AnimalStatus;
use App\Enums\ProductionStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\ActivityLog;
use App\Models\Animal;
use App\Models\Expense;
use App\Models\FeedConsumption;
use App\Models\Invoice;
use App\Models\MilkProduction;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\AttentionService;
use App\Services\FinanceService;
use App\Services\InventoryService;
use App\Services\ProductionAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        AttentionService $attention,
        FinanceService $finance,
        InventoryService $inventory,
        ProductionAnalyticsService $production,
    ): View {
        $farmId = $request->integer('farm_id') ?: null;

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $animals = Animal::query()
            ->when($farmId, fn ($q) => $q->where('farm_id', $farmId));

        $productionToday = $production->summary($farmId, now()->startOfDay(), now()->endOfDay());
        $productionMonth = $production->summary($farmId, now()->startOfMonth(), now()->endOfMonth());
        $trend = $production->compareWindows($farmId, now()->startOfMonth(), now()->endOfMonth());

        return view('dashboard.index', [
            'attentionItems' => $attention->items(),

            'animalStats' => [
                'active' => (clone $animals)->active()->count(),
                'lactating' => (clone $animals)->active()->lactating()->count(),
                'pregnant' => (clone $animals)->active()->pregnant()->count(),
                'calves' => (clone $animals)->active()->where('production_status', ProductionStatus::Calf->value)->count(),
                'sold' => Animal::query()->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
                    ->where('status', AnimalStatus::Sold->value)->count(),
                'deceased' => Animal::query()->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
                    ->where('status', AnimalStatus::Deceased->value)->count(),
                'transferred' => Animal::query()->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
                    ->where('status', AnimalStatus::Transferred->value)->count(),
            ],

            'productionToday' => $productionToday,
            'productionMonth' => $productionMonth,
            'productionTrend' => $trend,

            'feedToday' => [
                'amount_formatted' => 'Rp'.number_format((float) FeedConsumption::query()
                    ->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
                    ->whereDate('consumption_date', $today)
                    ->sum('total_amount'), 0, ',', '.'),
                'amount' => (float) FeedConsumption::query()
                    ->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
                    ->whereDate('consumption_date', $today)
                    ->sum('total_amount'),
                'quantity' => (float) FeedConsumption::query()
                    ->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
                    ->whereDate('consumption_date', $today)
                    ->sum('total_quantity'),
            ],
            'lowStockCount' => $inventory->lowStockItems()->count(),
            'openPurchaseOrders' => PurchaseOrder::query()
                ->whereIn('status', [PurchaseOrderStatus::Approved->value, PurchaseOrderStatus::PartiallyReceived->value])
                ->count(),

            'finance' => [
                'receivable' => $finance->accountsReceivable(),
                'payable' => $finance->accountsPayable(),
                'overdue' => $finance->overdueReceivable(),
                'overdueCount' => Invoice::query()->overdue()->count(),
                'cash' => $finance->cashBalance(),
            ],
            'monthSummary' => $finance->periodSummary($monthStart, $today),

            'decliningAnimals' => $production->decliningAnimals($farmId),

            'recentExpenses' => Expense::query()
                ->whereDate('expense_date', '>=', $monthStart)
                ->orderByDesc('expense_date')
                ->limit(6)
                ->get(),

            'recentActivity' => ActivityLog::query()
                ->with('subject')
                ->latest()
                ->limit(8)
                ->get(),

            'topProducers' => $production->topAnimals($farmId, now()->startOfMonth(), now()->endOfMonth(), 5),

            'recentProductions' => MilkProduction::query()
                ->forFarm($farmId)
                ->whereDate('production_date', '>=', now()->subDays(7)->toDateString())
                ->with(['animal', 'barn'])
                ->latest('production_date')
                ->limit(6)
                ->get(),

            'staff' => User::query()->whereIn('role', ['owner', 'manager', 'worker', 'veterinarian', 'finance'])->count(),
        ]);
    }
}
