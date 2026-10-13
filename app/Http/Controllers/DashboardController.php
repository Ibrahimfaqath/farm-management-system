<?php

namespace App\Http\Controllers;

use App\Enums\AnimalStatus;
use App\Enums\ProductionStatus;
use App\Models\ActivityLog;
use App\Models\Animal;
use App\Models\Expense;
use App\Models\Invoice;
use App\Services\AttentionService;
use App\Services\FinanceService;
use App\Services\InventoryService;
use App\Services\ProductionAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The dashboard is assembled from optional panels.
 *
 * Every panel below belongs to a module. A role that cannot open that module
 * gets neither the figures nor the panel — the queries are skipped as well, so
 * a Farm Worker no longer runs the receivable, payable and expense queries at
 * all, and no longer sees overdue invoice totals or unpaid supplier balances
 * they have no way to act on.
 */
class DashboardController extends Controller
{
    public function index(
        Request $request,
        AttentionService $attention,
        FinanceService $finance,
        InventoryService $inventory,
        ProductionAnalyticsService $production,
    ): View {
        $user = $request->user();
        $farmId = $request->integer('farm_id') ?: null;

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $canFarm = $user->canAccessModule('farm');
        $canProduction = $user->canAccessModule('production');
        $canFinance = $user->canAccessModule('finance');
        $canFeed = $user->canAccessModule('feed');
        $canAudit = $user->hasAbility('audit.view');

        $animals = Animal::query()
            ->when($farmId, fn ($q) => $q->where('farm_id', $farmId));

        $productionToday = $canProduction
            ? $production->summary($farmId, now()->startOfDay(), now()->endOfDay())
            : null;

        $productionMonth = $canProduction
            ? $production->summary($farmId, now()->startOfMonth(), now()->endOfMonth())
            : null;

        $trend = $canProduction
            ? $production->compareWindows($farmId, now()->startOfMonth(), now()->endOfMonth())
            : null;

        return view('dashboard.index', [
            'attentionItems' => $attention->itemsFor($user),

            'showFarm' => $canFarm,
            'showProduction' => $canProduction,
            'showFinance' => $canFinance,
            'showFeed' => $canFeed,
            'showAudit' => $canAudit,

            'animalStats' => $canFarm ? [
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
            ] : null,

            'productionToday' => $productionToday,
            'productionMonth' => $productionMonth,
            'productionTrend' => $trend,

            // Zero-filled daily series for the dashboard chart, so a day without
            // production reads as zero rather than disappearing from the axis.
            // `dailyTotals()` keys by the raw date string, which carries a time
            // part, so the lookup normalises both sides to Y-m-d.
            'productionSeries' => $canProduction ? collect(
                $production->dailyTotals($farmId, now()->subDays(6)->startOfDay(), now()->endOfDay())
            )->mapWithKeys(fn (array $row) => [substr($row['date'], 0, 10) => $row])
                ->pipe(function ($totals) {
                    $series = [];

                    for ($day = 6; $day >= 0; $day--) {
                        $date = now()->subDays($day);

                        $series[] = [
                            'label' => $date->translatedFormat('D'),
                            'value' => (float) ($totals[$date->toDateString()]['total'] ?? 0),
                        ];
                    }

                    return $series;
                }) : [],

            'lowStockCount' => $canFeed ? $inventory->lowStockItems()->count() : 0,

            'finance' => $canFinance ? [
                'receivable' => $finance->accountsReceivable(),
                'payable' => $finance->accountsPayable(),
                'overdue' => $finance->overdueReceivable(),
                'overdueCount' => Invoice::query()->overdue()->count(),
                'cash' => $finance->cashBalance(),
            ] : null,

            'monthSummary' => $canFinance
                ? $finance->periodSummary($monthStart, $today)
                : null,

            'decliningAnimals' => $canProduction ? $production->decliningAnimals($farmId) : [],

            'recentExpenses' => $canFinance
                ? Expense::query()
                    ->whereDate('expense_date', '>=', $monthStart)
                    ->orderByDesc('expense_date')
                    ->limit(6)
                    ->get()
                : null,

            'recentActivity' => $canAudit
                ? ActivityLog::query()->with('subject')->latest()->limit(8)->get()
                : null,

            'topProducers' => $canProduction
                ? $production->topAnimals($farmId, now()->startOfMonth(), now()->endOfMonth(), 5)
                : null,
        ]);
    }
}
