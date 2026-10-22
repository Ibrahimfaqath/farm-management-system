@extends('layouts.app', ['title' => __('Feed consumption')])

@section('content')
    <x-page-header :title="__('Feed consumption')"
                   :subtitle="__('Consumption reduces stock and is recorded in the movement trail.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.consume'))
                <a href="{{ route('inventory.index') }}" class="btn btn-sm">{{ __('Check stock') }}</a>
                <a href="{{ route('feed-consumptions.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record consumption') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Quantity used this month')" :value="\App\Support\Number::format($monthQuantity, 2)" />
        <x-stat :label="__('Value this month')" :value="'Rp'.number_format($monthTotal, 0, ',', '.')" tone="warning" />
        <x-stat :label="__('All records')" :value="$consumptions->total()" />
        <x-stat :label="__('Average per record')"
               :value="$consumptions->count() > 0 ? 'Rp'.number_format($monthTotal / max(1, $consumptions->count()), 0, ',', '.') : '—'" />
    </div>

    <x-panel padding="p-0">
        <x-filter-bar :reset="route('feed-consumptions.index')" :with-search="false"
                      columns="sm:grid-cols-2 lg:grid-cols-4">
            <x-filter-select name="farm_id"
                :options="$farms"
                :selected="$filters['farm_id'] ?? ''"
                :placeholder="__('All farms') "
                :label="__('Filter by farm') " />

            <x-filter-select name="barn_id"
                :options="$barns"
                :selected="$filters['barn_id'] ?? ''"
                :placeholder="__('All barns') "
                :label="__('Filter by barn') " />

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('To date') }}">
        </x-filter-bar>

        @if ($consumptions->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No feed consumption yet')"
                              :description="__('Record consumption so stock decreases and feed cost can be calculated.')"
                              :url="route('feed-consumptions.create')" :submit-label="__('Record consumption')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Barn') }}</th>
                            <th class="num">{{ __('Items') }}</th>
                            <th class="num">{{ __('Total quantity') }}</th>
                            <th class="num">{{ __('Value') }}</th>
                            <th>{{ __('Recorded by') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($consumptions as $consumption)
                            <tr>
                                <td>
                                    <a href="{{ route('feed-consumptions.show', $consumption) }}" class="link link-hover font-mono text-xs text-primary">
                                        {{ $consumption->consumption_number }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap">{{ $consumption->consumption_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $consumption->barn?->name ?? __('No barn') }}</td>
                                <td class="num">{{ $consumption->items_count }}</td>
                                <td class="num">{{ \App\Support\Number::format((float) $consumption->total_quantity, 2) }}</td>
                                <td class="num"><x-money :amount="$consumption->total_amount" /></td>
                                <td class="text-base-content/65">{{ $consumption->creator?->name ?? '—' }}</td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for consumption :number', ['number' => $consumption->id])">
                                            <x-menu-link :href="route('feed-consumptions.show', $consumption)" icon="info">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $consumptions->links() }}</div>
        @endif
    </x-panel>
@endsection