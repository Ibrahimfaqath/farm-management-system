@extends('layouts.app', ['title' => __('Customers')])

@section('content')
    <x-page-header :title="__('Customers')" :subtitle="__('Customer records with their current balances.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('sales.manage'))
                <a href="{{ route('sales-orders.create') }}" class="btn btn-sm">{{ __('New sales order') }}</a>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add customer') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search by name or code…')" :reset="route('customers.index')"
                      columns="sm:grid-cols-2 lg:grid-cols-3">
            <x-filter-select name="type"
                :options="$types"
                :selected="$filters['type'] ?? ''"
                :placeholder="__('All types') "
                :label="__('Filter by type') " />
        </x-filter-bar>

        @if ($customers->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No customers yet')"
                              :description="__('Add a customer before creating a sales order.')"
                              :url="route('customers.create')" :submit-label="__('Add customer')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Contact') }}</th>
                            <th class="num">{{ __('Terms') }}</th>
                            <th class="num">{{ __('Invoices') }}</th>
                            <th class="num">{{ __('Receivable') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($customers as $customer)
                            <tr>
                                <td class="font-mono text-xs">{{ $customer->code }}</td>
                                <td>
                                    <a href="{{ route('customers.show', $customer) }}" class="link link-hover font-medium text-primary">
                                        {{ $customer->name }}
                                    </a>
                                </td>
                                <td class="text-base-content/70">{{ $types[$customer->type] ?? $customer->type }}</td>
                                <td class="text-base-content/65">
                                    <span class="block truncate">{{ $customer->contact_person ?? '—' }}</span>
                                    <span class="text-meta block">{{ $customer->phone ?? '—' }}</span>
                                </td>
                                <td class="num">{{ $customer->payment_terms_days }} {{ __('days') }}</td>
                                <td class="num">{{ $customer->invoices_count }}</td>
                                <td class="num {{ (float) $customer->balance > 0 ? 'font-medium text-tone-warning' : 'text-base-content/45' }}">
                                    <x-money :amount="$customer->balance" />
                                </td>
                                <td class="row-actions">
                                    @if (auth()->user()->hasAbility('sales.manage'))
                                        <x-actions-menu :label="__('Actions for :name', ['name' => $customer->name])">
                                            <x-menu-link :href="route('customers.edit', $customer)" icon="edit">
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
            <div class="border-t border-base-300 p-3">{{ $customers->links() }}</div>
        @endif
    </x-panel>
@endsection