@extends('layouts.app', ['title' => __('Goods receipts')])

@section('content')
    <x-page-header :title="__('Goods receipts')"
                   :subtitle="__('Only a confirmed receipt increases stock.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.receive'))
                <a href="{{ route('goods-receipts.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record receipt') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <x-stat :label="__('Draft receipts')" :value="$draftCount"
               :tone="$draftCount > 0 ? 'warning' : 'success'"
               :hint="__('Stock has not moved yet')" />
        <x-stat :label="__('Supplier payable')" :value="'Rp'.number_format($payableTotal, 0, ',', '.')"
               :tone="$payableTotal > 0 ? 'warning' : 'success'"
               :hint="__('From confirmed receipts')" />
        <x-stat :label="__('Confirmed receipts')" :value="$postedCount" tone="success" />
    </div>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search receipt number…')" :reset="route('goods-receipts.index')">
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

        @if ($receipts->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No goods receipts yet')"
                              :description="__('Record a receipt to add stock. Creating one from a purchase order keeps the outstanding quantity visible.')"
                              :url="route('goods-receipts.create')" :submit-label="__('Record receipt')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Supplier') }}</th>
                            <th>{{ __('Purchase order') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="num">{{ __('Items') }}</th>
                            <th class="num">{{ __('Value') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($receipts as $receipt)
                            <tr>
                                <td>
                                    <a href="{{ route('goods-receipts.show', $receipt) }}" class="link link-hover font-mono text-xs text-primary">
                                        {{ $receipt->receipt_number }}
                                    </a>
                                </td>
                                <td>{{ $receipt->supplier->name }}</td>
                                <td class="text-meta font-mono text-base-content/65">{{ $receipt->purchaseOrder?->po_number ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $receipt->received_date->translatedFormat('d M Y') }}</td>
                                <td><x-badge :label="$receipt->status->label()" :class="$receipt->status->badgeClass()" /></td>
                                <td class="num">{{ $receipt->items_count }}</td>
                                <td class="num"><x-money :amount="$receipt->total_amount" /></td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for receipt :number', ['number' => $receipt->receipt_number])">
                                            <x-menu-link :href="route('goods-receipts.show', $receipt)" icon="eye">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $receipts->links() }}</div>
        @endif
    </x-panel>
@endsection