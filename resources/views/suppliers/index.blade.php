@extends('layouts.app', ['title' => __('Suppliers')])

@section('content')
    <x-page-header :title="__('Suppliers')" :subtitle="__('Feed, equipment and other suppliers.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.purchase'))
                <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add supplier') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search by name or code…')" :reset="route('suppliers.index')"
                      columns="sm:grid-cols-2 lg:grid-cols-3">
            <x-filter-select name="category"
                :options="$categories"
                :selected="$filters['category'] ?? ''"
                :placeholder="__('All categories') "
                :label="__('Filter by category') " />
        </x-filter-bar>

        @if ($suppliers->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No suppliers yet')"
                              :description="__('Add a supplier before creating a purchase order.')"
                              :url="route('suppliers.create')" :submit-label="__('Add supplier')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Contact') }}</th>
                            <th class="num">{{ __('Terms') }}</th>
                            <th class="num">{{ __('PO') }}</th>
                            <th class="num">{{ __('Receipts') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td class="font-mono text-xs">{{ $supplier->code }}</td>
                                <td>
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="link link-hover font-medium text-primary">
                                        {{ $supplier->name }}
                                    </a>
                                </td>
                                <td class="text-base-content/70">{{ $categories[$supplier->category] ?? $supplier->category }}</td>
                                <td class="text-base-content/65">
                                    <span class="block truncate">{{ $supplier->contact_person ?? '—' }}</span>
                                    <span class="text-meta block">{{ $supplier->phone ?? '—' }}</span>
                                </td>
                                <td class="num">{{ $supplier->payment_terms_days }} {{ __('days') }}</td>
                                <td class="num">{{ $supplier->purchase_orders_count }}</td>
                                <td class="num">{{ $supplier->goods_receipts_count }}</td>
                                <td>
                                    <x-badge :label="$supplier->is_active ? __('Active') : __('Inactive')"
                                             :class="$supplier->is_active ? 'badge-soft badge-success' : 'badge-ghost'" />
                                </td>
                                <td class="row-actions">
                                    @if (auth()->user()->hasAbility('feed.purchase'))
                                        <x-actions-menu :label="__('Actions for :name', ['name' => $supplier->name])">
                                            <x-menu-link :href="route('suppliers.edit', $supplier)" icon="edit">
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
            <x-pagination :paginator="$suppliers" />
        @endif
    </x-panel>
@endsection