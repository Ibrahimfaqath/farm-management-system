@extends('layouts.app', ['title' => __('Feed report')])

@section('content')
    <x-page-header :title="__('Feed report')" :subtitle="__('Stock, purchases, consumption and consumption rate.')" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 print:hidden">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <x-filter-select name="farm_id"
                :options="$farms"
                :selected="$filters['farm_id'] ?? ''"
                :placeholder="__('All farms') "
                :label="__('Filter by farm') " />
            <input type="date" name="date_from" value="{{ $window['from']->toDateString() }}"
                   class="input input-sm w-full bg-base-100 sm:w-40" aria-label="{{ __('From date') }}">
            <input type="date" name="date_to" value="{{ $window['to']->toDateString() }}"
                   class="input input-sm w-full bg-base-100 sm:w-40" aria-label="{{ __('To date') }}">
            <x-form.submit size="sm">{{ __('Apply') }}</x-form.submit>
            <a href="{{ route('reports.feed.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear filters') }}</a>
        </form>

        <button type="button" class="btn btn-sm" onclick="window.print()">
            <x-icon name="print" :size="16" />
            {{ __('Print') }}
        </button>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Stock value')" :value="'Rp'.number_format($totalStockValue, 0, ',', '.')" tone="primary" />
        <x-stat :label="__('Purchase value')" :value="'Rp'.number_format($totalPurchaseAmount, 0, ',', '.')" tone="info" />
        <x-stat :label="__('Consumption value')" :value="'Rp'.number_format($totalConsumptionAmount, 0, ',', '.')" tone="warning" />
        <x-stat :label="__('Low stock items')" :value="$lowStockCount" :tone="$lowStockCount > 0 ? 'warning' : 'success'" />
    </div>

    <x-panel padding="p-0">
        <div class="table-scroll">
            <table class="table app-table">
                <thead>
                    <tr>
                        <th>{{ __('Feed item') }}</th>
                        <th class="num">{{ __('Stock') }}</th>
                        <th class="num">{{ __('Reorder point') }}</th>
                        <th class="num">{{ __('Purchased') }}</th>
                        <th class="num">{{ __('Consumed') }}</th>
                        <th class="num">{{ __('Daily use') }}</th>
                        <th class="num">{{ __('Runs out in') }}</th>
                        <th class="num">{{ __('Stock value') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $row->name }}</p>
                                <p class="text-meta text-base-content/45">{{ $row->code }} · {{ $row->category->label() }}</p>
                            </td>
                            <td class="num font-medium {{ (float) $row->current_stock <= 0 ? 'text-tone-error' : '' }}">
                                {{ number_format((float) $row->current_stock, 2) }} <span class="text-meta text-base-content/50">{{ $row->unit->shortLabel() }}</span>
                            </td>
                            <td class="num text-base-content/65">{{ number_format((float) $row->reorder_point, 2) }}</td>
                            <td class="num">
                                {{ number_format((float) $row->purchase_qty, 2) }}
                                <span class="text-meta block tabular-nums text-base-content/45">Rp{{ number_format((float) $row->purchase_amount, 0) }}</span>
                            </td>
                            <td class="num">
                                {{ number_format((float) $row->consumption_qty, 2) }}
                                <span class="text-meta block tabular-nums text-base-content/45">Rp{{ number_format((float) $row->consumption_amount, 0) }}</span>
                            </td>
                            <td class="num text-base-content/65">{{ number_format((float) $row->daily_consumption, 2) }}</td>
                            <td class="num">
                                @if ($row->days_remaining === null)
                                    <span class="text-base-content/45">—</span>
                                @else
                                    <span @class(['text-tone-warning' => $row->days_remaining <= 7])>
                                        {{ $row->days_remaining }} {{ __('days') }}
                                    </span>
                                @endif
                            </td>
                            <td class="num"><x-money :amount="$row->stock_value" /></td>
                            <td>
                                @if ((float) $row->current_stock <= 0)
                                    <x-badge :label="__('Out of stock')" class="badge-soft badge-error" />
                                @elseif ($row->is_low)
                                    <x-badge :label="__('Low stock')" class="badge-soft badge-warning" />
                                @else
                                    <x-badge :label="__('Healthy')" class="badge-soft badge-success" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-panel>
@endsection