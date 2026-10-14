@extends('layouts.app', ['title' => $supplier->name])

@section('content')
    <x-page-header :title="$supplier->name" :subtitle="$supplier->code.' · '.$supplier->city"
                   :back="route('suppliers.index')"
                   :breadcrumbs="[
                       ['label' => __('Suppliers'), 'url' => route('suppliers.index')],
                       ['label' => $supplier->name],
                   ]">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.purchase'))
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm">{{ __('New purchase order') }}</a>
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm">{{ __('Edit supplier') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Purchase orders')" :value="$supplier->purchase_orders_count" />
        <x-stat :label="__('Goods receipts')" :value="$supplier->goods_receipts_count" />
        <x-stat :label="__('Payments')" :value="$supplier->payments_count" />
        <x-stat :label="__('Outstanding payable')" :value="'Rp'.number_format($outstanding, 0, ',', '.')"
               :tone="$outstanding > 0 ? 'warning' : 'success'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Purchase orders')" padding="p-0">
                @if ($purchaseOrders->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No purchase orders for this supplier yet.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="num">{{ __('Items') }}</th>
                                    <th class="num">{{ __('Value') }}</th>
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
                                        <td class="whitespace-nowrap">{{ $order->order_date->translatedFormat('d M Y') }}</td>
                                        <td><x-badge :label="$order->status->label()" :class="$order->status->badgeClass()" /></td>
                                        <td class="num">{{ $order->items_count }}</td>
                                        <td class="num"><x-money :amount="$order->total" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>

            <x-panel :title="__('Goods receipts')" padding="p-0">
                @if ($goodsReceipts->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No goods receipts yet.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="num">{{ __('Value') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($goodsReceipts as $receipt)
                                    <tr>
                                        <td>
                                            <a href="{{ route('goods-receipts.show', $receipt) }}" class="link link-hover font-mono text-xs text-primary">
                                                {{ $receipt->receipt_number }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap">{{ $receipt->received_date->translatedFormat('d M Y') }}</td>
                                        <td><x-badge :label="$receipt->status->label()" :class="$receipt->status->badgeClass()" /></td>
                                        <td class="num"><x-money :amount="$receipt->total_amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel :title="__('Supplier information')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Contact person') }}</dt>
                    <dd class="text-right">{{ $supplier->contact_person ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Phone') }}</dt>
                    <dd class="text-right">{{ $supplier->phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Email') }}</dt>
                    <dd class="text-right break-all">{{ $supplier->email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Tax number') }}</dt>
                    <dd class="text-right">{{ $supplier->tax_number ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Terms') }}</dt>
                    <dd class="text-right">{{ $supplier->payment_terms_days }} {{ __('days') }}</dd>
                </div>
            </dl>
            @if ($supplier->address)
                <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $supplier->address }}</p>
            @endif
            @if ($supplier->notes)
                <p class="mt-2 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $supplier->notes }}</p>
            @endif
        </x-panel>
    </div>
@endsection