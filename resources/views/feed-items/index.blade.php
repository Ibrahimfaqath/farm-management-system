@extends('layouts.app', ['title' => __('Feed items')])

@section('content')
    <x-page-header :title="__('Feed items')" :subtitle="__('Feed master data and reorder thresholds.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.purchase'))
                <a href="{{ route('inventory.index') }}" class="btn btn-sm">{{ __('View stock') }}</a>
                <a href="{{ route('feed-items.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add feed item') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($lowStockCount > 0)
        <div class="mb-4">
            <x-alert-row tone="warning"
                         :title="trans_choice(':count feed item is below its reorder point|:count feed items are below their reorder points', $lowStockCount, ['count' => $lowStockCount])"
                         :description="__('Open the inventory page for the full list and remaining-days estimate.')"
                         :url="route('inventory.index')" :url-label="__('Open inventory')" />
        </div>
    @endif

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search by name or code…')" :reset="route('feed-items.index')"
                      columns="sm:grid-cols-2 lg:grid-cols-3">
            <x-filter-select name="category"
                :options="$categories"
                :selected="$filters['category'] ?? ''"
                :placeholder="__('All categories') "
                :label="__('Filter by category') " />

            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="checkbox" name="low_stock" value="1" @checked($filters['low_stock'] ?? false)
                       class="checkbox checkbox-sm checkbox-primary">
                {{ __('Low stock only') }}
            </label>
        </x-filter-bar>

        @if ($feedItems->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No feed items yet')"
                              :description="__('Add feed master data so it can be ordered and tracked.')"
                              :url="route('feed-items.create')" :submit-label="__('Add feed item')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Unit') }}</th>
                            <th class="num">{{ __('Price') }}</th>
                            <th class="num">{{ __('Stock') }}</th>
                            <th class="num">{{ __('Reorder point') }}</th>
                            <th>{{ __('Location') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($feedItems as $item)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $item->name }}</p>
                                    <p class="text-meta font-mono text-base-content/45">{{ $item->code }}</p>
                                </td>
                                <td class="text-base-content/70">{{ $item->category->label() }}</td>
                                <td>{{ $item->unit->shortLabel() }}</td>
                                <td class="num"><x-money :amount="$item->price_per_unit" /></td>
                                <td class="num">{{ number_format((float) ($item->current_stock ?? 0), 2) }}</td>
                                <td class="num text-base-content/65">{{ number_format((float) $item->reorder_point, 2) }}</td>
                                <td class="text-base-content/65">{{ $item->storage_location ?? '—' }}</td>
                                <td class="row-actions">
                                    @if (auth()->user()->hasAbility('feed.purchase'))
                                        <x-actions-menu :label="__('Actions for :name', ['name' => $item->name])">
                                            <x-menu-link :href="route('feed-items.edit', $item)" icon="edit">
                                                {{ __('Edit') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $feedItems->links() }}</div>
        @endif
    </x-panel>
@endsection