@extends('layouts.app', ['title' => __('Payments')])

@section('content')
    <x-page-header :title="__('Customer payments')"
                   :subtitle="__('Each payment reduces receivables and adds to the cash flow record.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record payment') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <x-stat :label="__('Received this month')" :value="'Rp'.number_format($monthTotal, 0, ',', '.')" tone="success" />
        <x-stat :label="__('Total records')" :value="$payments->total()" />
        <x-stat :label="__('Average')"
               :value="$payments->count() > 0 ? 'Rp'.number_format($monthTotal / $payments->count(), 0, ',', '.') : '—'"
               :hint="__('Per payment this month')" />
    </div>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search payment number…')" :reset="route('payments.index')">
            <x-filter-select name="customer_id"
                :options="$customers"
                :selected="$filters['customer_id'] ?? ''"
                :placeholder="__('All customers') "
                :label="__('Filter by customer') " />

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
        </x-filter-bar>

        @if ($payments->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No payments yet')"
                              :description="__('Record a customer payment to reduce receivables and increase cash.')"
                              :url="route('payments.create')" :submit-label="__('Record payment')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Invoice') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Recorded by') }}</th>
                            <th class="num">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="font-mono text-xs">{{ $payment->payment_number }}</td>
                                <td class="whitespace-nowrap">{{ $payment->payment_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $payment->customer->name }}</td>
                                <td class="text-meta ">
                                    @if ($payment->invoice)
                                        <a href="{{ route('invoices.show', $payment->invoice) }}" class="link link-hover font-mono text-primary">
                                            {{ $payment->invoice->invoice_number }}
                                        </a>
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td>{{ $payment->method->label() }}</td>
                                <td class="text-meta truncate text-base-content/60">{{ $payment->reference_number ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $payment->recorder?->name ?? '—' }}</td>
                                <td class="num font-medium text-tone-success"><x-money :amount="$payment->amount" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$payments" />
        @endif
    </x-panel>
@endsection