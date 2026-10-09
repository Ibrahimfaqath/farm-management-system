@extends('layouts.app', ['title' => __('Dashboard'), 'subtitle' => __('Operational summary and follow-up items')])

@section('content')
    <x-page-header :title="__('Dashboard')"
                   :subtitle="__('Built from operational data, not decorative numbers.')"
                   icon="dashboard" />

    @if (count($attentionItems) > 0)
        <section class="mb-6">
            <x-section-title :title="__('Needs attention')" :count="count($attentionItems)" icon="bell" />
            <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($attentionItems as $item)
                    <x-alert-row :tone="$item['tone']" :title="$item['title']"
                                 :description="$item['description'] ?? null" :url="$item['url']" />
                @endforeach
            </div>
        </section>
    @else
        <section class="mb-6">
            <x-alert-row tone="info" :title="__('Nothing requires immediate action')"
                         :description="__('Stock, receivables and schedule are within normal range.')" />
        </section>
    @endif

    <section class="mb-6">
        <x-section-title :title="__('Livestock population')" icon="cow" />
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-7">
            <x-stat :label="__('Active')" :value="$animalStats['active']" tone="primary" />
            <x-stat :label="__('Lactating')" :value="$animalStats['lactating']" />
            <x-stat :label="__('Pregnant')" :value="$animalStats['pregnant']" tone="info" />
            <x-stat :label="__('Calf')" :value="$animalStats['calves']" />
            <x-stat :label="__('Sold')" :value="$animalStats['sold']" />
            <x-stat :label="__('Deceased')" :value="$animalStats['deceased']" tone="error" />
            <x-stat :label="__('Transferred')" :value="$animalStats['transferred']" tone="warning" />
        </div>
    </section>

    <section class="mb-6">
        <x-section-title :title="__('Production & feed')" icon="milk" />
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat :label="__('Milk today')"
                   :value="number_format($productionToday['per_day'], 1).' L'"
                   :hint="$productionToday['records'].' '.__('records')"
                   tone="primary">
                <x-slot:icon><x-icon name="milk" /></x-slot:icon>
            </x-stat>
            <x-stat :label="__('Milk this month')"
                   :value="number_format($productionMonth['total'], 0).' L'"
                   :hint="__('Average').' '.number_format($productionMonth['per_day'], 1).' '.__('L/day')" />
            <x-stat :label="__('Trend this month')"
                   :value="$productionTrend['change_pct'] === null ? '—' : ($productionTrend['change_pct'] > 0 ? '+' : '').$productionTrend['change_pct'].'%'"
                   :tone="match ($productionTrend['direction']) { 'up' => 'success', 'down' => 'warning', default => 'neutral' }"
                   :hint="__('vs previous period')" />
            <x-stat :label="__('Feed value today')" :value="$feedToday['amount_formatted']"
                   :hint="number_format($feedToday['quantity'], 1).' '.__('unit')" />
        </div>

        @if ($decliningAnimals !== [])
            <div class="mt-3">
                <x-panel :title="__('Production changes worth checking')"
                         :description="__('Facts per animal. The system does not infer a cause.')">
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('Livestock') }}</th>
                                    <th class="text-right">{{ __('Previous period') }}</th>
                                    <th class="text-right">{{ __('Current period') }}</th>
                                    <th class="text-right">{{ __('Change') }}</th>
                                    <th class="text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($decliningAnimals as $row)
                                    <tr>
                                        <td>
                                            <a href="{{ route('animals.show', $row['animal']) }}"
                                               class="link link-hover font-medium text-primary">
                                                {{ $row['animal']->animal_code }}
                                            </a>
                                            <span class="block text-[11px] text-base-content/45">{{ $row['animal']->barn?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-right tabular-nums text-base-content/65">{{ number_format($row['previous'], 2) }} L</td>
                                        <td class="text-right tabular-nums">{{ number_format($row['current'], 2) }} L</td>
                                        <td class="text-right">
                                            <x-badge :label="$row['change_pct'].'%'" class="badge-soft badge-warning" />
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('animals.show', $row['animal'], ['tab' => 'produksi']) }}"
                                               class="btn btn-ghost btn-xs">{{ __('Check') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-panel>
            </div>
        @endif
    </section>

    <section class="mb-6">
        <x-section-title :title="__('Finance')" icon="wallet" />
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <x-stat :label="__('Revenue this month')"
                   :value="'Rp'.number_format($monthSummary['revenue'], 0, ',', '.')" tone="success" />
            <x-stat :label="__('Expenses this month')"
                   :value="'Rp'.number_format($monthSummary['operating_expense'], 0, ',', '.')" tone="warning" />
            <x-stat :label="__('Estimated profit')"
                   :value="'Rp'.number_format($monthSummary['profit_estimate'], 0, ',', '.')"
                   :tone="$monthSummary['profit_estimate'] >= 0 ? 'primary' : 'error'"
                   :hint="__('revenue − expenses')" />
            <x-stat :label="__('Receivable')"
                   :value="'Rp'.number_format($finance['receivable'], 0, ',', '.')"
                   :hint="$finance['overdueCount'].' '.__('overdue invoices')" />
            <x-stat :label="__('Cash balance')"
                   :value="'Rp'.number_format($finance['cash'], 0, ',', '.')"
                   :hint="__('supplier payable').' Rp'.number_format($finance['payable'], 0, ',', '.')"
                   tone="info" />
        </div>
    </section>

    <section class="mb-6">
        <x-section-title :title="__('Feed operations')" icon="inventory" />
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat :label="__('Low stock')" :value="$lowStockCount"
                   :tone="$lowStockCount > 0 ? 'warning' : 'success'">
                <x-slot:hint>{{ __('Reorder threshold') }}</x-slot:hint>
            </x-stat>
            <x-stat :label="__('Incomplete orders')" :value="$openPurchaseOrders"
                   :tone="$openPurchaseOrders > 0 ? 'warning' : 'success'">
                <x-slot:hint>{{ __('Awaiting receipt') }}</x-slot:hint>
            </x-stat>
            <x-stat :label="__('Overdue receivable')"
                   :value="'Rp'.number_format($finance['overdue'], 0, ',', '.')"
                   :tone="$finance['overdue'] > 0 ? 'error' : 'success'" />
            <x-stat :label="__('Active users')" :value="$staff" />
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-panel :title="__('Top producers')" :description="__('This month')" padding="p-0">
            @if ($topProducers === [])
                <p class="p-4 text-sm text-base-content/55">{{ __('No production this month yet.') }}</p>
            @else
                <ul class="divide-y divide-base-300">
                    @foreach ($topProducers as $row)
                        <li class="flex items-center justify-between gap-3 px-4 py-2">
                            <div class="min-w-0">
                                <a href="{{ route('animals.show', $row['animal']) }}"
                                   class="link link-hover text-sm font-medium text-primary">
                                    {{ $row['animal']->animal_code }}
                                </a>
                                <p class="text-[11px] text-base-content/45">{{ $row['animal']->barn?->name ?? '—' }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm tabular-nums font-medium">{{ number_format($row['total'], 1) }} L</p>
                                <p class="text-[11px] text-base-content/45">
                                    ±{{ number_format($row['average'], 1) }} {{ __('L/day') }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel :title="__('Recent expenses')" :description="__('Current month')" padding="p-0">
            @if ($recentExpenses->isEmpty())
                <p class="p-4 text-sm text-base-content/55">{{ __('No expenses this month yet.') }}</p>
            @else
                <ul class="divide-y divide-base-300">
                    @foreach ($recentExpenses as $expense)
                        <li class="flex items-center justify-between gap-3 px-4 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm">{{ $expense->description }}</p>
                                <p class="text-[11px] text-base-content/45">
                                    {{ $expense->expense_date->translatedFormat('d M Y') }} · {{ $expense->category->label() }}
                                </p>
                            </div>
                            <x-money :amount="$expense->amount" class="shrink-0 text-sm" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel :title="__('Recent activity')" padding="p-0">
            @if ($recentActivity->isEmpty())
                <p class="p-4 text-sm text-base-content/55">{{ __('No activity recorded yet.') }}</p>
            @else
                <ul class="divide-y divide-base-300">
                    @foreach ($recentActivity as $log)
                        <li class="px-4 py-2">
                            <p class="text-xs">{{ $log->description }}</p>
                            <p class="text-[11px] text-base-content/45">
                                {{ $log->user_name ?? __('System') }} · {{ $log->created_at->translatedFormat('d M H:i') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <x-panel :title="__('Production, last 7 days')" padding="p-0">
            @if ($recentProductions->isEmpty())
                <p class="p-4 text-sm text-base-content/55">{{ __('No production in the last 7 days.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Source') }}</th>
                                <th>{{ __('Session') }}</th>
                                <th class="text-right">{{ __('Liter') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentProductions as $row)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $row->production_date->translatedFormat('d M Y') }}</td>
                                    <td>{{ $row->subjectLabel() }}</td>
                                    <td>{{ $row->session->label() }}</td>
                                    <td class="text-right tabular-nums">{{ number_format((float) $row->liters, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>

        <x-panel :title="__('Quick access')">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @php
                    $quickActions = [
                        ['ability' => 'production.record', 'route' => 'milk-productions.create', 'label' => __('Record production'), 'icon' => 'milk'],
                        ['ability' => 'feed.consume', 'route' => 'feed-consumptions.create', 'label' => __('Record feed'), 'icon' => 'feed'],
                        ['ability' => 'feed.receive', 'route' => 'goods-receipts.create', 'label' => __('Receive goods'), 'icon' => 'box'],
                        ['ability' => 'health.manage', 'route' => 'health-records.create', 'label' => __('Record health'), 'icon' => 'health'],
                        ['ability' => 'breeding.manage', 'route' => 'pregnancy-checks.create', 'label' => __('Pregnancy check'), 'icon' => 'breeding'],
                        ['ability' => 'sales.manage', 'route' => 'sales-orders.create', 'label' => __('Sales order'), 'icon' => 'sales'],
                        ['ability' => 'payment.record', 'route' => 'payments.create', 'label' => __('Receive payment'), 'icon' => 'cash'],
                        ['ability' => 'finance.manage', 'route' => 'expenses.create', 'label' => __('Record expense'), 'icon' => 'expense'],
                        ['ability' => 'animal.manage', 'route' => 'animals.index', 'label' => __('Livestock data'), 'icon' => 'cow'],
                        ['ability' => null, 'route' => 'inventory.index', 'label' => __('Feed stock'), 'icon' => 'inventory'],
                    ];
                @endphp

                @foreach ($quickActions as $action)
                    @if ($action['ability'] === null || auth()->user()->hasAbility($action['ability']))
                        <a href="{{ route($action['route']) }}" class="btn btn-outline btn-sm justify-start gap-2">
                            <x-icon :name="$action['icon']" :size="16" />
                            <span class="truncate">{{ $action['label'] }}</span>
                        </a>
                    @endif
                @endforeach

                @if (auth()->user()->hasAbility('report.view') || auth()->user()->hasAbility('settings.manage'))
                    <a href="{{ route('reports.animal.index') }}" class="btn btn-outline btn-sm justify-start gap-2">
                        <x-icon name="report" :size="16" />
                        <span class="truncate">{{ __('Reports') }}</span>
                    </a>
                @endif
            </div>
        </x-panel>
    </div>
@endsection
