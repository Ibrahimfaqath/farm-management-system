@extends('layouts.app', ['title' => __('Stock movements')])

@section('content')
    <x-page-header :title="__('Stock movements')" :subtitle="__('The audit trail behind every stock change.')"
                   :back="route('inventory.index')" />

    <x-panel padding="p-0">
        <x-filter-bar :reset="route('inventory.movements')" :with-search="false"
                      columns="sm:grid-cols-2 lg:grid-cols-4">
            <x-filter-select name="feed_item_id"
                :options="$feedItems"
                :selected="$filters['feed_item_id'] ?? ''"
                :placeholder="__('All items') "
                :label="__('Filter by feed item') " />

            <x-filter-select name="type"
                :options="$types"
                :selected="$filters['type'] ?? ''"
                :placeholder="__('All types') "
                :label="__('Filter by movement type') " />

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('To date') }}">
        </x-filter-bar>

        @if ($movements->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No stock movements yet')"
                              :description="__('Movements appear automatically when a goods receipt, consumption or adjustment is recorded.')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="num">{{ __('Quantity') }}</th>
                            <th>{{ __('Batch') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th>{{ __('By') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($movements as $movement)
                            <tr>
                                <td class="whitespace-nowrap">{{ $movement->movement_date->translatedFormat('d M Y') }}</td>
                                <td class="font-medium">{{ $movement->feedItem?->name ?? '—' }}</td>
                                <td><x-badge :label="$movement->type->label()" :class="$movement->type->badgeClass()" /></td>
                                <td class="num font-medium {{ $movement->direction()->sign() > 0 ? 'text-tone-success' : 'text-tone-warning' }}">
                                    {{ $movement->direction()->sign() > 0 ? '+' : '−' }}{{ number_format((float) $movement->quantity, 2) }}
                                    <span class="text-meta text-base-content/50">{{ $movement->unit->shortLabel() }}</span>
                                </td>
                                <td class="text-meta font-mono">{{ $movement->batch?->batch_code ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $movement->reference ?? '—' }}</td>
                                <td class="max-w-xs truncate text-base-content/65">{{ $movement->reason ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $movement->creator?->name ?? __('System') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$movements" />
        @endif
    </x-panel>
@endsection