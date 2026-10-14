@extends('layouts.app', ['title' => $customer->name])

@section('content')
    <x-page-header :title="$customer->name" :subtitle="$customer->code.' · '.($customer->city ?? '—')"
                   :back="route('customers.index')"
                   :breadcrumbs="[
                       ['label' => __('Customers'), 'url' => route('customers.index')],
                       ['label' => $customer->name],
                   ]">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create') }}" class="btn btn-sm">{{ __('Record payment') }}</a>
            @endif
            @if (auth()->user()->hasAbility('sales.manage'))
                <a href="{{ route('sales-orders.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('New sales order') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Outstanding receivable')" :value="'Rp'.number_format($balance, 0, ',', '.')"
               :tone="$balance > 0 ? 'warning' : 'success'" />
        <x-stat :label="__('Invoices')" :value="$customer->invoices_count" />
        <x-stat :label="__('Sales orders')" :value="$customer->sales_orders_count" />
        <x-stat :label="__('Terms')" :value="$customer->payment_terms_days.' '.__('days')" />
    </div>

    @if ($balance > (float) $customer->credit_limit && (float) $customer->credit_limit > 0)
        <div class="mb-4">
            <x-alert-row tone="error"
                         :title="__('Receivable exceeds the credit limit')"
                         :description="__('Credit limit :limit, current receivable :balance.', [
                             'limit' => 'Rp'.number_format((float) $customer->credit_limit, 0),
                             'balance' => 'Rp'.number_format($balance, 0),
                         ])" />
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Invoices')" padding="p-0">
                @if ($invoices->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No invoices for this customer yet.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Due date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="num">{{ __('Total') }}</th>
                                    <th class="num">{{ __('Balance') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td>
                                            <a href="{{ route('invoices.show', $invoice) }}" class="link link-hover font-mono text-xs text-primary">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap">{{ $invoice->invoice_date->translatedFormat('d M Y') }}</td>
                                        <td class="whitespace-nowrap">{{ $invoice->due_date->translatedFormat('d M Y') }}</td>
                                        <td>
                                            <div class="flex flex-wrap items-center gap-1">
                                                <x-badge :label="$invoice->status->label()" :class="$invoice->status->badgeClass()" />
                                                @if ($invoice->isOverdue())
                                                    <x-badge :label="__('Overdue')" class="badge-soft badge-error" />
                                                @endif
                                            </div>
                                        </td>
                                        <td class="num"><x-money :amount="$invoice->total" /></td>
                                        <td class="num"><x-money :amount="$invoice->balance()" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>

            <x-panel :title="__('Payments')" padding="p-0">
                @if ($payments->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No payments yet.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th class="num">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td class="text-meta font-mono">{{ $payment->payment_number }}</td>
                                        <td class="whitespace-nowrap">{{ $payment->payment_date->translatedFormat('d M Y') }}</td>
                                        <td class="text-meta font-mono">{{ $payment->invoice?->invoice_number ?? '—' }}</td>
                                        <td>{{ $payment->method->label() }}</td>
                                        <td class="num"><x-money :amount="$payment->amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel :title="__('Information')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Type') }}</dt>
                    <dd class="text-right">{{ $customer->type }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Contact person') }}</dt>
                    <dd class="text-right">{{ $customer->contact_person ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Phone') }}</dt>
                    <dd class="text-right">{{ $customer->phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Credit limit') }}</dt>
                    <dd class="text-right"><x-money :amount="$customer->credit_limit" /></dd>
                </div>
            </dl>
            @if ($customer->notes)
                <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $customer->notes }}</p>
            @endif
        </x-panel>
    </div>
@endsection