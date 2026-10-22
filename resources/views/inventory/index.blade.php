@extends('layouts.app', ['title' => __('Inventory')])

@section('content')
    <x-page-header :title="__('Feed inventory')"
                   :subtitle="__('Stock is derived from movement history, never edited by hand.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.adjust'))
                <a href="{{ route('inventory.batches') }}" class="btn btn-sm">{{ __('Batches') }}</a>
                <a href="{{ route('inventory.movements') }}" class="btn btn-sm">{{ __('Stock movements') }}</a>
                <button type="button" class="btn btn-primary btn-sm" data-modal-open="adjust-modal">
                    <x-icon name="scale" :size="16" />
                    {{ __('Adjust stock') }}
                </button>
            @endif
            @if (auth()->user()->hasAbility('feed.consume'))
                <a href="{{ route('feed-consumptions.create') }}" class="btn btn-primary btn-sm">{{ __('Record usage') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Stock value')" :value="'Rp'.number_format($totalValue, 0, ',', '.')" tone="primary" />
        <x-stat :label="__('Low stock')" :value="$lowStock->count()"
               :tone="$lowStock->count() > 0 ? 'warning' : 'success'"
               :hint="__('Below reorder point')" />
        <x-stat :label="__('Out of stock')" :value="$outOfStock->count()"
               :tone="$outOfStock->count() > 0 ? 'error' : 'success'" />
        <x-stat :label="__('Active feed items')" :value="$items->count()" />
    </div>

    @if ($lowStock->isNotEmpty())
        <div class="mb-4 space-y-2">
            @foreach ($lowStock->take(4) as $item)
                <x-alert-row tone="warning"
                             :title="__('Feed stock low: :name', ['name' => $item->name])"
                             :description="__('Remaining :remaining :unit against a reorder point of :point. Average daily use: :daily :unit.', [
                                 'remaining' => \App\Support\Number::compact($item->current_stock),
                                 'unit' => $item->unit->shortLabel(),
                                 'point' => \App\Support\Number::compact($item->reorder_point),
                                 'daily' => \App\Support\Number::compact($item->daily_consumption, 1),
                             ])"
                             :url="route('purchase-orders.create')" :url-label="__('Create PO')" />
            @endforeach
        </div>
    @endif

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search feed items…')" :reset="route('inventory.index')"
                      columns="sm:grid-cols-3">
            <x-filter-select name="category"
                :options="$categories"
                :selected="$filters['category'] ?? ''"
                :placeholder="__('All categories') "
                :label="__('Filter by category') " />
        </x-filter-bar>

        @if ($items->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No active feed items')"
                              :description="__('Add a feed item, then a purchase order and a goods receipt, to start tracking stock.')"
                              :url="route('feed-items.create')" :submit-label="__('Add feed item')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th class="num">{{ __('Stock') }}</th>
                            <th class="num">{{ __('Reorder point') }}</th>
                            <th class="num">{{ __('Daily use') }}</th>
                            <th class="num">{{ __('Estimated') }}</th>
                            <th class="num">{{ __('Value') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($items as $item)
                            @php
                                $isLow = (float) $item->reorder_point > 0 && (float) $item->current_stock <= (float) $item->reorder_point;
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $item->name }}</p>
                                    <p class="text-meta font-mono text-base-content/45">{{ $item->code }}</p>
                                </td>
                                <td class="text-base-content/70">{{ $item->category->label() }}</td>
                                <td class="num font-medium {{ (float) $item->current_stock <= 0 ? 'text-tone-error' : '' }}">
                                    <x-qty :value="$item->current_stock" :unit="$item->unit->shortLabel()" />
                                </td>
                                <td class="num text-base-content/65">
                                    <x-qty :value="$item->reorder_point" :unit="$item->unit->shortLabel()" />
                                </td>
                                <td class="num text-base-content/65">
                                    <x-qty :value="$item->daily_consumption" :unit="$item->unit->shortLabel()" />
                                </td>
                                <td class="num">
                                    <x-days-remaining :days="$item->days_remaining" />
                                </td>
                                <td class="num"><x-money :amount="$item->stock_value" /></td>
                                <td>
                                    @if ((float) $item->current_stock <= 0)
                                        <x-badge :label="__('Out of stock')" class="badge-soft badge-error" />
                                    @elseif ($isLow)
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
        @endif
    </x-panel>

    @if (auth()->user()->hasAbility('feed.adjust'))
        <x-modal id="adjust-modal" :title="__('Adjust stock')"
                 :description="__('Every adjustment needs a reason and is recorded as its own stock movement.')">
            <form method="POST" action="{{ route('inventory.adjust') }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-form.select name="feed_item_id" :label="__('Feed item')" required class="sm:col-span-2"
                                   :options="$items->pluck('name', 'id')" />
                    <x-form.select name="direction" :label="__('Direction')" required
                                   :options="['in' => __('Increase (+)'), 'out' => __('Decrease (−)')]" />
                    <x-form.input name="quantity" :label="__('Quantity')" type="number" step="0.01" min="0.01" required />
                    <x-form.input name="movement_date" :label="__('Date')" type="date" :value="now()->toDateString()" />
                    <x-form.input name="reason" :label="__('Reason')" required minlength="5"
                                  :placeholder="__('For example: corrected warehouse scale reading')" />
                </div>
                <x-form.textarea name="notes" :label="__('Notes')" rows="2" />
                <x-form.actions>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Cancel') }}</button>
                    <x-form.submit>{{ __('Save adjustment') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif
@endsection