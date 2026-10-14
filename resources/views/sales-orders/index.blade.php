@extends('layouts.app', ['title' => __('Sales orders')])

@section('content')
    <x-page-header :title="__('Sales orders')" :subtitle="__('An order is the basis on which an invoice is created.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('sales.manage'))
                <a href="{{ route('sales-orders.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('New sales order') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search SO number…')" :reset="route('sales-orders.index')">
            <x-filter-select name="status"
                :options="$statuses"
                :selected="$filters['status'] ?? ''"
                :placeholder="__('All statuses') "
                :label="__('Filter by status') " />

            <x-filter-select name="customer_id"
                :options="$customers"
                :selected="$filters['customer_id'] ?? ''"
                :placeholder="__('All customers') "
                :label="__('Filter by customer') " />
        </x-filter-bar>

        @if ($orders->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No sales orders yet')"
                              :description="__('A sales order records what is being sold. Invoices are created from an order to recognise revenue.')"
                              :url="route('sales-orders.create')" :submit-label="__('Create sales order')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="num">{{ __('Items') }}</th>
                            <th class="num">{{ __('Total') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('sales-orders.show', $order) }}" class="link link-hover font-mono text-xs text-primary">
                                        {{ $order->so_number }}
                                    </a>
                                </td>
                                <td>{{ $order->customer->name }}</td>
                                <td class="whitespace-nowrap">{{ $order->order_date->translatedFormat('d M Y') }}</td>
                                <td><x-badge :label="$order->status->label()" :class="$order->status->badgeClass()" /></td>
                                <td class="num">{{ $order->items_count }}</td>
                                <td class="num"><x-money :amount="$order->total" /></td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for order :number', ['number' => $order->so_number])">
                                            <x-menu-link :href="route('sales-orders.show', $order)" icon="eye">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $orders->links() }}</div>
        @endif
    </x-panel>
@endsection