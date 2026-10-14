@extends('layouts.app', ['title' => __('Production report')])

@section('content')
    <x-page-header :title="__('Production report')"
                   :subtitle="__('Daily, weekly and monthly milk volume, per animal and per barn.')" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 print:hidden">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <x-filter-select name="farm_id"
                :options="$farms"
                :selected="$filters['farm_id'] ?? ''"
                :placeholder="__('All farms') "
                :label="__('Filter by farm') " />
            <input type="date" name="date_from" value="{{ $from }}"
                   class="input input-sm w-full bg-base-100 sm:w-40" aria-label="{{ __('From date') }}">
            <input type="date" name="date_to" value="{{ $to }}"
                   class="input input-sm w-full bg-base-100 sm:w-40" aria-label="{{ __('To date') }}">
            <select name="group_by" class="select select-sm w-full bg-base-100 sm:w-36" aria-label="{{ __('Grouping') }}">
                @foreach (['day' => __('By day'), 'week' => __('By week'), 'month' => __('By month'), 'raw' => __('Raw')] as $value => $label)
                    <option value="{{ $value }}" @selected($grouping === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-form.submit size="sm">{{ __('Apply') }}</x-form.submit>
            <a href="{{ route('reports.production.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear filters') }}</a>
        </form>

        <button type="button" class="btn btn-sm" onclick="window.print()">
            <x-icon name="print" :size="16" />
            {{ __('Print') }}
        </button>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat :label="__('Total liters')" :value="number_format($summary['total'], 1).' L'" tone="primary" />
        <x-stat :label="__('Daily average')" :value="number_format($summary['per_day'], 1).' L'" />
        <x-stat :label="__('Per lactating head')" :value="number_format($summary['per_lactating_head'], 1).' L'" />
        <x-stat :label="__('Records')" :value="$summary['records']" />
        <x-stat :label="__('vs previous period')"
               :value="$trend['change_pct'] === null ? '—' : ($trend['change_pct'] > 0 ? '+' : '').$trend['change_pct'].'%'"
               :tone="match ($trend['direction']) { 'up' => 'success', 'down' => 'warning', default => 'neutral' }" />
    </div>

    @if ($trend['direction'] === 'down')
        <div class="mb-4">
            <x-alert-row tone="warning"
                         :title="__('Production is down :pct% versus the previous period', ['pct' => abs($trend['change_pct'])])"
                         :description="__('This is a fact about the numbers, not a diagnosis. Review feed, health and animal condition records.')" />
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Production chart')"
                     :description="match ($grouping) {
                         'day' => __('Daily totals'),
                         'week' => __('Weekly totals'),
                         'month' => __('Monthly totals'),
                         default => __('Raw records'),
                     }">
                @if ($series === [])
                    <p class="text-sm text-base-content/55">{{ __('No production in this period.') }}</p>
                @else
                    @php
                        $max = max(array_map(fn ($point) => (float) $point['total'], $series)) ?: 1;
                    @endphp
                    <div class="flex h-56 items-end gap-1 overflow-x-auto" role="img"
                         aria-label="{{ implode(', ', array_map(fn ($point) => $point['label'].' '.number_format($point['total'], 2).' L', $series)) }}">
                        @foreach ($series as $point)
                            <div class="tooltip tooltip-top flex min-w-8 flex-1 flex-col items-center justify-end gap-1"
                                 data-tip="{{ $point['label'] }} · {{ number_format($point['total'], 2) }} L">
                                <div class="w-full rounded-t bg-primary/70 transition-colors hover:bg-primary"
                                     style="height: {{ max(2, (float) $point['total'] / $max * 180) }}px"></div>
                                <span class="text-meta text-base-content/45">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-panel>

            <x-panel :title="__('Production per animal')" padding="p-0">
                @if ($topAnimals === [])
                    <p class="p-4 text-sm text-base-content/55">{{ __('No production in this period.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Barn') }}</th>
                                    <th class="num">{{ __('Total') }}</th>
                                    <th class="num">{{ __('Average') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($topAnimals as $row)
                                    <tr>
                                        <td>
                                            <a href="{{ route('animals.show', $row['animal']) }}" class="link link-hover font-medium text-primary">
                                                {{ $row['animal']->animal_code }}
                                            </a>
                                        </td>
                                        <td class="text-base-content/70">{{ $row['animal']->barn?->name ?? '—' }}</td>
                                        <td class="num">{{ number_format($row['total'], 2) }} L</td>
                                        <td class="num">{{ number_format($row['average'], 2) }} L</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel :title="__('Production per barn')" padding="p-0">
                @if ($byBarn === [])
                    <p class="p-4 text-sm text-base-content/55">{{ __('No data yet.') }}</p>
                @else
                    <ul class="divide-y divide-base-300">
                        @foreach ($byBarn as $row)
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <span class="truncate text-sm">{{ $row['barn'] }}</span>
                                <span class="shrink-0 text-sm font-medium tabular-nums">{{ number_format($row['total'], 1) }} L</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>

            <x-panel :title="__('Declines to check')" padding="p-0"
                     :description="__('A real change. The cause needs a human to investigate it.')">
                @if ($declining === [])
                    <p class="p-4 text-sm text-base-content/55">{{ __('No significant decline in the comparison period.') }}</p>
                @else
                    <ul class="divide-y divide-base-300">
                        @foreach ($declining as $row)
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <a href="{{ route('animals.show', $row['animal'], ['tab' => 'produksi']) }}"
                                   class="link link-hover text-sm text-primary">
                                    {{ $row['animal']->animal_code }}
                                </a>
                                <span class="text-sm font-medium tabular-nums text-tone-warning">{{ $row['change_pct'] }} %</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>
        </div>
    </div>
@endsection