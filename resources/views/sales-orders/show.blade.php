@extends('layouts.app', ['title' => __('Sales order').' '.$salesOrder->so_number])

@section('content')
    <x-page-header :title="$salesOrder->so_number"
                   :subtitle="$salesOrder->customer->name.' · '.$salesOrder->order_date->translatedFormat('d M Y')"
                   :back="route('sales-orders.index')"
                   :breadcrumbs="[
                       ['label' => __('Sales orders'), 'url' => route('sales-orders.index')],
                       ['label' => $salesOrder->so_number],
                   ]">
        <x-slot:actions>
            @if (! $salesOrder->invoice && $salesOrder->status->value !== 'cancelled' && auth()->user()->hasAbility('invoice.manage'))
                <form method="POST" action="{{ route('sales-orders.invoice', $salesOrder) }}">
                    @csrf
                    <x-form.submit>{{ __('Create invoice') }}</x-form.submit>
                </form>
            @endif
            @if ($salesOrder->invoice && $salesOrder->invoice->balance() > 0 && auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create', ['invoice_id' => $salesOrder->invoice->id]) }}" class="btn btn-primary btn-sm">
                    {{ __('Record payment') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($salesOrder->invoice)
        <div class="mb-4">
            <x-alert-row tone="info"
                         :title="__('Invoice :number has been issued', ['number' => $salesOrder->invoice->invoice_number])"
                         :description="__('Revenue of :total recognised and a receivable of :balance raised. Cash only moves once payment is received.', [
                             'total' => 'Rp'.number_format((float) $salesOrder->invoice->total, 0),
                             'balance' => 'Rp'.number_format($salesOrder->invoice->balance(), 0),
                         ])" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Status')" :value="$salesOrder->status->label()" />
        <x-stat :label="__('Items')" :value="$salesOrder->items->count()" />
        <x-stat :label="__('Order value')" :value="'Rp'.number_format((float) $salesOrder->total, 0, ',', '.')" />
        <x-stat :label="__('Invoice')" :value="$salesOrder->invoice?->invoice_number ?? __('Not created yet')"
               :tone="$salesOrder->invoice ? 'success' : 'warning'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Order lines')" padding="p-0">
                <div class="table-scroll">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>{{ __('Item') }}</th>
                                <th class="num">{{ __('Quantity') }}</th>
                                <th class="num">{{ __('Unit price') }}</th>
                                <th class="num">{{ __('Line total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($salesOrder->items as $item)
                                <tr>
                                    <td class="font-medium">{{ $item->description }}</td>
                                    <td class="num">{{ number_format((float) $item->quantity, 2) }} <span class="text-meta text-base-content/50">{{ $item->unit->shortLabel() }}</span></td>
                                    <td class="num"><x-money :amount="$item->unit_price" /></td>
                                    <td class="num"><x-money :amount="$item->line_total" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-base-300 p-3">
                    <dl class="ml-auto w-full max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-base-content/55">{{ __('Subtotal') }}</dt>
                            <dd><x-money :amount="$salesOrder->subtotal" /></dd>
                        </div>
                        @if ((float) $salesOrder->discount_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-base-content/55">{{ __('Discount') }}</dt>
                                <dd>&minus;<x-money :amount="$salesOrder->discount_amount" /></dd>
                            </div>
                        @endif
                        @if ((float) $salesOrder->tax_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-base-content/55">{{ __('Tax') }}</dt>
                                <dd><x-money :amount="$salesOrder->tax_amount" /></dd>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-base-300 pt-1 font-medium">
                            <dt>{{ __('Total') }}</dt>
                            <dd><x-money :amount="$salesOrder->total" /></dd>
                        </div>
                    </dl>
                </div>
            </x-panel>
        </div>

        <x-panel :title="__('Order information')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Customer') }}</dt>
                    <dd class="text-right">
                        <a href="{{ route('customers.show', $salesOrder->customer) }}" class="link link-hover text-primary">
                            {{ $salesOrder->customer->name }}
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Terms') }}</dt>
                    <dd class="text-right">{{ $salesOrder->customer->payment_terms_days }} {{ __('days') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Created by') }}</dt>
                    <dd class="text-right">{{ $salesOrder->creator?->name ?? '—' }}</dd>
                </div>
            </dl>
            @if ($salesOrder->notes)
                <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $salesOrder->notes }}</p>
            @endif
        </x-panel>
    </div>
@endsection