@extends('layouts.app', ['title' => __('Dashboard'), 'moduleLabel' => __('Overview')])

@section('content')
    <x-page-header :title="__('What needs attention today')"
                   :subtitle="__('Live figures from livestock, production, stock and finance records.')"
                   icon="dashboard" />

    {{--
    One row, not ten cards. See `x-attention-strip` for why the full list was
    removed and why it is not removed outright.
--}}
    <x-attention-strip :attention-items="$attentionItems" />

    {{--
        "Today's position" only lists the figures this role may see. A Farm
        Worker has no finance module, so the stock tile is the only inventory
        figure that appears, and a Veterinarian sees neither stock nor milk.
    --}}
    @php
        $positionStats = [];

        if ($showProduction) {
            $positionStats[] = ['label' => __('Milk today'), 'value' => number_format($productionToday['per_day'], 1).' L',
                'hint' => $productionToday['records'].' '.__('records'), 'tone' => 'primary', 'icon' => 'milk'];
            $positionStats[] = ['label' => __('Milk this month'), 'value' => number_format($productionMonth['total'], 0).' L',
                'hint' => __('Average').' '.number_format($productionMonth['per_day'], 1).' '.__('L/day'), 'tone' => null, 'icon' => null];
        }

        if ($showFarm) {
            $positionStats[] = ['label' => __('Active livestock'), 'value' => $animalStats['active'],
                'hint' => $animalStats['lactating'].' '.__('lactating'), 'tone' => 'info', 'icon' => 'cow'];
        }

        if ($showFeed) {
            $positionStats[] = ['label' => __('Low stock items'), 'value' => $lowStockCount,
                'hint' => __('Below reorder point'), 'tone' => $lowStockCount > 0 ? 'warning' : 'success', 'icon' => 'inventory'];
        }
    @endphp

    @if ($positionStats !== [])
        <section class="mb-6">
            <x-section-title :title="__('Today’s position')" icon="chart" />
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ($positionStats as $stat)
                    <x-stat :label="$stat['label']" :value="$stat['value']"
                           :hint="$stat['hint'] ?? null" :tone="$stat['tone'] ?? null">
                        @if ($stat['icon'])
                            <x-slot:icon><x-icon :name="$stat['icon']" /></x-slot:icon>
                        @endif
                    </x-stat>
                @endforeach
            </div>
        </section>
    @endif

    {{--
        The receivables panel and the quick-action panel are independent: a
        Veterinarian gets the actions alone, taking the full width rather than
        leaving a hole where the finance figures were.
    --}}
    <div @class(['mb-6 grid items-start gap-4', 'lg:grid-cols-3' => $showFinance])>
        @if ($showFinance)
            <x-panel :title="__('Receivables')"
                     :description="__('Raised when an invoice is issued, not when cash moves.')"
                     class="min-w-0 lg:col-span-2">
                <div class="grid grid-cols-2 gap-3">
                    <x-stat :label="__('Receivable')"
                           :value="'Rp'.number_format($finance['receivable'], 0, ',', '.')" />
                    <x-stat :label="__('Overdue')"
                           :value="'Rp'.number_format($finance['overdue'], 0, ',', '.')"
                           :tone="$finance['overdue'] > 0 ? 'error' : 'success'"
                           :hint="$finance['overdueCount'].' '.__('overdue invoices')" />
                    <x-stat :label="__('Supplier payable')"
                           :value="'Rp'.number_format($finance['payable'], 0, ',', '.')"
                           :tone="$finance['payable'] > 0 ? 'warning' : 'neutral'" />
                    <x-stat :label="__('Cash balance')"
                           :value="'Rp'.number_format($finance['cash'], 0, ',', '.')"
                           tone="info" />
                </div>
            </x-panel>
        @endif

        <x-panel :title="__('Quick access')">
            @php
                $quickActions = [
                    ['ability' => 'production.record', 'module' => 'production', 'route' => 'milk-productions.create', 'label' => __('Record production'), 'icon' => 'milk'],
                    ['ability' => 'feed.consume', 'module' => 'feed', 'route' => 'feed-consumptions.create', 'label' => __('Record feed'), 'icon' => 'feed'],
                    ['ability' => 'feed.receive', 'module' => 'feed', 'route' => 'goods-receipts.create', 'label' => __('Receive goods'), 'icon' => 'box'],
                    ['ability' => 'health.manage', 'module' => 'health', 'route' => 'health-records.create', 'label' => __('Record health'), 'icon' => 'health'],
                    ['ability' => 'breeding.manage', 'module' => 'breeding', 'route' => 'pregnancy-checks.create', 'label' => __('Pregnancy check'), 'icon' => 'breeding'],
                    ['ability' => 'sales.manage', 'module' => 'sales', 'route' => 'sales-orders.create', 'label' => __('Sales order'), 'icon' => 'sales'],
                    ['ability' => 'payment.record', 'module' => 'sales', 'route' => 'payments.create', 'label' => __('Receive payment'), 'icon' => 'cash'],
                    ['ability' => 'finance.manage', 'module' => 'finance', 'route' => 'expenses.create', 'label' => __('Record expense'), 'icon' => 'expense'],
                    ['ability' => 'animal.manage', 'module' => 'farm', 'route' => 'animals.index', 'label' => __('Livestock data'), 'icon' => 'cow'],
                    ['ability' => null, 'module' => 'feed', 'route' => 'inventory.index', 'label' => __('Feed stock'), 'icon' => 'inventory'],
                ];

                // The ability is the fine-grained gate, but a create route also
                // sits behind its module. Checking both is what stops the panel
                // from offering a button that answers 403.
                $user = auth()->user();
                $quickActions = array_values(array_filter($quickActions, fn (array $action) => $user->canAccessModule($action['module'])
                    && ($action['ability'] === null || $user->hasAbility($action['ability']))));
            @endphp

            @if ($quickActions === [])
                <p class="py-4 text-sm text-base-content/55">{{ __('No actions available for your role yet.') }}</p>
            @else
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($quickActions as $action)
                        <a href="{{ route($action['route']) }}" class="btn btn-ghost btn-sm justify-start gap-2 px-2">
                            <x-icon :name="$action['icon']" :size="16" class="text-base-content/50" />
                            <span class="min-w-0 text-left leading-tight">{{ $action['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-panel>
    </div>

    @if ($showFinance || $showProduction)
        <section class="mb-6">
            {{-- One neutral heading covers both cases. "Monthly operating
                 result" describes money and would be wrong above the production
                 trend alone, while repeating the tile's own label adds a row
                 that says the same thing twice. --}}
            <x-section-title :title="__('This month')" icon="wallet" />
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @if ($showFinance)
                    <x-stat :label="__('Revenue this month')"
                           :value="'Rp'.number_format($monthSummary['revenue'], 0, ',', '.')" tone="success" />
                    <x-stat :label="__('Expenses this month')"
                           :value="'Rp'.number_format($monthSummary['operating_expense'], 0, ',', '.')" tone="warning" />
                    <x-stat :label="__('Estimated profit')"
                           :value="'Rp'.number_format($monthSummary['profit_estimate'], 0, ',', '.')"
                           :tone="$monthSummary['profit_estimate'] >= 0 ? 'primary' : 'error'"
                           :hint="__('revenue − expenses')" />
                @endif

                @if ($showProduction)
                    <x-stat :label="__('Production trend')"
                           :value="$productionTrend['change_pct'] === null ? '—' : ($productionTrend['change_pct'] > 0 ? '+' : '').$productionTrend['change_pct'].'%'"
                           :tone="match ($productionTrend['direction']) { 'up' => 'success', 'down' => 'warning', default => 'neutral' }"
                           :hint="__('vs previous period')" />
                @endif
            </div>
        </section>
    @endif

    @if ($operatingSeries !== null)
        {{--
            The month tiles above say what this month looks like in isolation.
            This says whether the shape is improving, which is the question
            someone opening a dashboard at the start of a day actually has.

            Revenue and expense are drawn as two lines on one axis because the
            comparison between them *is* the information: a revenue line alone
            looks good while the farm quietly loses money on what it buys.
        --}}
        <section class="mb-6">
            <x-panel :title="__('Revenue and expense, last 6 months')"
                     :description="__('Issued invoices against recorded expenses.')"
                     padding="p-4">
                <x-chart type="line"
                         :labels="$operatingSeries['labels']"
                         :series="[
                             ['label' => __('Revenue'), 'data' => $operatingSeries['revenue'], 'tone' => 'primary'],
                             ['label' => __('Expense'), 'data' => $operatingSeries['expense'], 'tone' => 'warning'],
                         ]"
                         :label="__('Revenue and expense, last 6 months')"
                         :height="240"
                         :showLegend="true" />
            </x-panel>
        </section>
    @endif

    @if ($showProduction || $showFarm)
        <div @class(['mb-6 grid items-start gap-4', 'lg:grid-cols-2' => $showProduction && $showFarm])>
            @if ($showProduction)
                <x-panel :title="__('Milk production, last 7 days')"
                         :description="__('Daily total recorded across the herd.')"
                         padding="p-4">
                    {{--
                        Drawn by Chart.js rather than the CSS bar chart.

                        The bar chart had no vertical axis, so the only thing a
                        reader could take from it was "higher or lower than
                        yesterday" — and the reader had to do the comparison
                        themselves, by eye, against a bar with no number on it.
                        Here the grid gives the scale and the tooltip gives the
                        exact litres without leaving the chart.
                    --}}
                    {{-- `collect()` first rather than `array_column()`: the series
                         arrives as a plain array, which `array_column` reads but
                         which has no `pluck()`, and as an empty array on some
                         paths. Wrapping handles both. Skipping this drew a
                         chart whose axis ran 0 to 1 with no bars in it, because
                         the data came back empty. --}}
                    <x-chart type="bar"
                             :labels="collect($productionSeries)->pluck('label')->all()"
                             :series="[['label' => __('Liters'), 'data' => collect($productionSeries)->pluck('value')->all(), 'tone' => 'primary']]"
                             :label="__('Milk production, last 7 days')"
                             :height="220"
                             :valueFormatter="true" />
                </x-panel>
            @endif

            @if ($showFarm)
                <x-panel :title="__('Livestock population')" padding="p-0">
                    <ul class="divide-y divide-base-300">
                        @foreach ([
                            ['label' => __('Active'), 'value' => $animalStats['active'], 'tone' => 'text-primary'],
                            ['label' => __('Lactating'), 'value' => $animalStats['lactating'], 'tone' => ''],
                            ['label' => __('Pregnant'), 'value' => $animalStats['pregnant'], 'tone' => 'text-tone-info'],
                            ['label' => __('Calf'), 'value' => $animalStats['calves'], 'tone' => ''],
                            ['label' => __('Sold'), 'value' => $animalStats['sold'], 'tone' => ''],
                            ['label' => __('Deceased'), 'value' => $animalStats['deceased'], 'tone' => 'text-tone-error'],
                            ['label' => __('Transferred'), 'value' => $animalStats['transferred'], 'tone' => 'text-tone-warning'],
                        ] as $row)
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <span class="text-sm text-base-content/70">{{ $row['label'] }}</span>
                                <span class="text-sm font-semibold tabular-nums {{ $row['tone'] }}">{{ $row['value'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-panel>
            @endif
        </div>
    @endif

    @php
        $bottomPanels = array_filter([
            $showProduction ? 'top' : null,
            $showFinance ? 'expenses' : null,
            $showAudit ? 'activity' : null,
        ]);
    @endphp

    @if ($bottomPanels !== [])
        <div @class(['mb-6 grid items-start gap-4', 'lg:grid-cols-'.count($bottomPanels) => count($bottomPanels) > 1])>
            @if ($showProduction)
                <x-panel :title="__('Top producers')" :description="__('This month')" padding="p-0">
                    @if ($topProducers === [])
                        <p class="p-4 text-sm text-base-content/55">{{ __('No production this month yet.') }}</p>
                    @else
                        <ul class="divide-y divide-base-300">
                            @foreach ($topProducers as $row)
                                <li class="flex items-center justify-between gap-3 px-4 py-2">
                                    <div class="min-w-0">
                                        <a href="{{ route('animals.show', $row['animal']) }}"
                                           class="link link-hover truncate text-sm font-medium text-primary">
                                            {{ $row['animal']->animal_code }}
                                        </a>
                                        <p class="text-meta truncate text-base-content/45">{{ $row['animal']->barn?->name ?? '—' }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-medium tabular-nums">{{ number_format($row['total'], 1) }} L</p>
                                        <p class="text-meta tabular-nums text-base-content/45">
                                            &plusmn;{{ number_format($row['average'], 1) }} {{ __('L/day') }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-panel>
            @endif

            @if ($showFinance)
                <x-panel :title="__('Recent expenses')" :description="__('Current month')" padding="p-0">
                    @if ($recentExpenses->isEmpty())
                        <p class="p-4 text-sm text-base-content/55">{{ __('No expenses this month yet.') }}</p>
                    @else
                        <ul class="divide-y divide-base-300">
                            @foreach ($recentExpenses as $expense)
                                <li class="flex items-center justify-between gap-3 px-4 py-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm">{{ $expense->description }}</p>
                                        <p class="text-meta truncate text-base-content/45">
                                            {{ $expense->expense_date->translatedFormat('d M Y') }} · {{ $expense->category->label() }}
                                        </p>
                                    </div>
                                    <x-money :amount="$expense->amount" class="shrink-0 text-sm" />
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-panel>
            @endif

            @if ($showAudit)
                <x-panel :title="__('Recent activity')" padding="p-0">
                    <x-slot:actions>
                        <a href="{{ route('activity-logs.index') }}" class="btn btn-ghost btn-sm">
                            {{ __('View all activity') }}
                        </a>
                    </x-slot:actions>

                    @if ($recentActivity->isEmpty())
                        <p class="p-4 text-sm text-base-content/55">{{ __('No activity recorded yet.') }}</p>
                    @else
                        <ul class="divide-y divide-base-300">
                            @foreach ($recentActivity as $log)
                                <li class="px-4 py-2">
                                    <p class="text-xs">{{ $log->description }}</p>
                                    <p class="text-meta text-base-content/45">
                                        @if ($log->user_id)
                                            <a href="{{ route('activity-logs.index', ['user_id' => $log->user_id]) }}"
                                               class="link link-hover">
                                                {{ $log->user_name ?? __('System') }}
                                            </a>
                                        @else
                                            {{ $log->user_name ?? __('System') }}
                                        @endif
                                        · {{ $log->created_at->translatedFormat('d M H:i') }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-panel>
            @endif
        </div>
    @endif

    @if ($decliningAnimals !== [])
        <x-panel :title="__('Production changes worth checking')"
                 :description="__('Facts per animal. The system does not infer a cause.')"
                 padding="p-0">
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Livestock') }}</th>
                            <th class="num">{{ __('Previous period') }}</th>
                            <th class="num">{{ __('Current period') }}</th>
                            <th class="num">{{ __('Change') }}</th>
                            <th class="row-actions">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($decliningAnimals as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('animals.show', $row['animal']) }}"
                                       class="link link-hover font-medium text-primary">
                                        {{ $row['animal']->animal_code }}
                                    </a>
                                    <span class="text-meta block truncate text-base-content/45">{{ $row['animal']->barn?->name ?? '—' }}</span>
                                </td>
                                <td class="num text-base-content/65">{{ number_format($row['previous'], 2) }} L</td>
                                <td class="num">{{ number_format($row['current'], 2) }} L</td>
                                <td class="num">
                                    <x-badge :label="$row['change_pct'].' %'" class="badge-soft badge-warning" />
                                </td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for :code', ['code' => $row['animal']->animal_code])" width="w-44">
                                        <x-menu-link :href="route('animals.show', $row['animal'], ['tab' => 'produksi'])" icon="milk">
                                            {{ __('Check') }}
                                        </x-menu-link>
                                    </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>
    @endif
@endsection