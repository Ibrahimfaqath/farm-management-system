@extends('layouts.app', ['title' => __('Purchase orders')])

@section('content')
    <x-page-header :title="__('Purchase orders')"
                   :subtitle="__('An order does not increase stock; stock grows when a goods receipt is confirmed.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.purchase'))
                <a href="{{ route('goods-receipts.index') }}" class="btn btn-sm">{{ __('Goods receipts') }}</a>
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('New purchase order') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($openOrders > 0)
        <div class="mb-4">
            <x-alert-row tone="info"
                         :title="trans_choice(':count purchase order is not fully received|:count purchase orders are not fully received', $openOrders, ['count' => $openOrders])"
                         :description="__('The remaining quantities are waiting for a goods receipt from the supplier.')"
                         :url="route('purchase-orders.index', ['status' => 'partially_received'])" :url-label="__('Show them')" />
        </div>
    @endif

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search PO number…')" :reset="route('purchase-orders.index')">
            <x-filter-select name="status"
                :options="$statuses"
                :selected="$filters['status'] ?? ''"
                :placeholder="__('All statuses') "
                :label="__('Filter by status') " />

            <x-filter-select name="supplier_id"
                :options="$suppliers"
                :selected="$filters['supplier_id'] ?? ''"
                :placeholder="__('All suppliers') "
                :label="__('Filter by supplier') " />
        </x-filter-bar>

        @if ($purchaseOrders->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No purchase orders yet')"
                              :description="__('A purchase order records a buying commitment. Stock only increases once a goods receipt is confirmed.')"
                              :url="route('purchase-orders.create')" :submit-label="__('Create purchase order')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Supplier') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Expected') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="num">{{ __('Items') }}</th>
                            <th class="num">{{ __('Value') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($purchaseOrders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('purchase-orders.show', $order) }}" class="link link-hover font-mono text-xs text-primary">
                                        {{ $order->po_number }}
                                    </a>
                                </td>
                                <td>{{ $order->supplier->name }}</td>
                                <td class="whitespace-nowrap">{{ $order->order_date->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap text-base-content/65">{{ $order->expected_date?->translatedFormat('d M Y') ?? '—' }}</td>
                                <td><x-badge :label="$order->status->label()" :class="$order->status->badgeClass()" /></td>
                                <td class="num">{{ $order->items_count }}</td>
                                <td class="num"><x-money :amount="$order->total" /></td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for order :number', ['number' => $order->po_number])">
                                            <x-menu-link :href="route('purchase-orders.show', $order)" icon="eye">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$purchaseOrders" />
        @endif
    </x-panel>
@endsection