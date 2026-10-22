@extends('layouts.app', ['title' => $invoice->invoice_number])

@section('content')
    <x-page-header :title="$invoice->invoice_number"
                   :subtitle="$invoice->customer->name.' · '.$invoice->invoice_date->translatedFormat('d M Y')"
                   :back="route('invoices.index')"
                   :breadcrumbs="[
                       ['label' => __('Invoices'), 'url' => route('invoices.index')],
                       ['label' => $invoice->invoice_number],
                   ]">
        <x-slot:actions>
            <a href="{{ route('invoices.document', $invoice) }}" class="btn btn-ghost btn-sm"
               target="_blank" rel="noopener">
                <x-icon name="print" :size="16" />
                {{ __('Print document') }}
            </a>

            @if ($balance > 0 && auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-primary btn-sm">
                    {{ __('Record invoice payment') }}</a>
            @endif

            @if ($invoice->payments->isEmpty() && $invoice->status->value !== 'cancelled' && auth()->user()->hasAbility('sales.manage'))
                <button type="button" class="btn btn-ghost btn-sm text-tone-error"
                        data-modal-open="cancel-invoice-modal">
                    {{ __('Cancel invoice') }}
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($invoice->isOverdue())
        <div class="mb-4">
            <x-alert-row tone="error"
                         :title="__('This invoice is :days days overdue', ['days' => $invoice->daysOverdue()])"
                         :description="__('Outstanding: :balance. Due date :date.', [
                             'balance' => 'Rp'.\App\Support\Number::format($balance, 0),
                             'date' => $invoice->due_date->translatedFormat('d M Y'),
                         ])" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Invoice total')" :value="'Rp'.number_format((float) $invoice->total, 0, ',', '.')" />
        <x-stat :label="__('Paid')" :value="'Rp'.number_format($paidAmount, 0, ',', '.')" tone="info" />
        <x-stat :label="__('Outstanding balance')" :value="'Rp'.number_format($balance, 0, ',', '.')"
               :tone="$balance > 0 ? 'warning' : 'success'" />
        <x-stat :label="__('Status')" :value="$invoice->status->label()" :tone="$balance > 0 ? 'warning' : 'success'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Invoice lines')" padding="p-0">
                @if ($invoice->salesOrder)
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
                            <tbody class="motion-rows">
                                @foreach ($invoice->salesOrder->items as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="num">{{ \App\Support\Number::format((float) $item->quantity, 2) }} <span class="text-meta text-base-content/50">{{ $item->unit->shortLabel() }}</span></td>
                                        <td class="num"><x-money :amount="$item->unit_price" /></td>
                                        <td class="num"><x-money :amount="$item->line_total" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="p-4 text-sm text-base-content/55">{{ __('This invoice was raised directly, without a sales order.') }}</p>
                @endif

                <div class="border-t border-base-300 p-3">
                    <dl class="ml-auto w-full max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-base-content/55">{{ __('Subtotal') }}</dt>
                            <dd><x-money :amount="$invoice->subtotal" /></dd>
                        </div>
                        @if ((float) $invoice->discount_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-base-content/55">{{ __('Discount') }}</dt>
                                <dd>&minus;<x-money :amount="$invoice->discount_amount" /></dd>
                            </div>
                        @endif
                        @if ((float) $invoice->tax_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-base-content/55">{{ __('Tax') }}</dt>
                                <dd><x-money :amount="$invoice->tax_amount" /></dd>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-base-300 pt-1 font-medium">
                            <dt>{{ __('Total') }}</dt>
                            <dd><x-money :amount="$invoice->total" /></dd>
                        </div>
                    </dl>
                </div>
            </x-panel>

            <x-panel :title="__('Payment history')" padding="p-0"
                     :description="__('Each payment also records a cash-in transaction.')">
                @if ($invoice->payments->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No payments recorded for this invoice yet.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Recorded by') }}</th>
                                    <th class="num">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($invoice->payments as $payment)
                                    <tr>
                                        <td class="text-meta font-mono">{{ $payment->payment_number }}</td>
                                        <td class="whitespace-nowrap">{{ $payment->payment_date->translatedFormat('d M Y') }}</td>
                                        <td>{{ $payment->method->label() }}</td>
                                        <td class="text-meta text-base-content/60">{{ $payment->reference_number ?? '—' }}</td>
                                        <td class="text-base-content/65">{{ $payment->recorder?->name ?? '—' }}</td>
                                        <td class="num"><x-money :amount="$payment->amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel :title="__('Invoice information')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Customer') }}</dt>
                    <dd class="text-right">
                        <a href="{{ route('customers.show', $invoice->customer) }}" class="link link-hover text-primary">
                            {{ $invoice->customer->name }}
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Date') }}</dt>
                    <dd class="text-right">{{ $invoice->invoice_date->translatedFormat('d M Y') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Terms') }}</dt>
                    <dd class="text-right">{{ $invoice->payment_terms_days }} {{ __('days') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Due date') }}</dt>
                    <dd class="text-right">{{ $invoice->due_date->translatedFormat('d M Y') }}</dd>
                </div>
                @if ($invoice->salesOrder)
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Sales order') }}</dt>
                        <dd class="text-right">
                            <a href="{{ route('sales-orders.show', $invoice->salesOrder) }}" class="link link-hover font-mono text-primary">
                                {{ $invoice->salesOrder->so_number }}
                            </a>
                        </dd>
                    </div>
                @endif
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Created by') }}</dt>
                    <dd class="text-right">{{ $invoice->creator?->name ?? '—' }}</dd>
                </div>
            </dl>
            @if ($invoice->notes)
                <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $invoice->notes }}</p>
            @endif
        </x-panel>
    </div>

    @if ($invoice->payments->isEmpty() && $invoice->status->value !== 'cancelled' && auth()->user()->hasAbility('sales.manage'))
        <x-modal id="cancel-invoice-modal" :title="__('Cancel invoice :number?', ['number' => $invoice->invoice_number])"
                 :description="__('Nothing is deleted: the invoice is marked as cancelled and the cancellation is noted. An invoice that already has a payment cannot be cancelled.')">
            <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" class="mt-4">
                @csrf
                <x-form.actions bare>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Keep invoice') }}</button>
                    <x-form.submit variant="error">{{ __('Cancel invoice') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif
@endsection