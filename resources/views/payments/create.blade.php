@extends('layouts.app', ['title' => __('Record payment')])

@section('content')
    <x-page-header :title="__('Record payment')"
                   :subtitle="__('A payment reduces the receivable and increases cash at the same moment.')"
                   :back="route('payments.index')" />

    <form method="POST" action="{{ route('payments.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Invoice and customer')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="invoice_id" :label="__('Invoice')" :options="$openInvoices"
                               :selected="old('invoice_id', $invoice?->id)" :placeholder="__('— Payment without an invoice —')" />
                <x-form.select name="customer_id" :label="__('Customer')" :options="$customers"
                               :selected="old('customer_id', $invoice?->customer_id)" required />
            </div>
        </x-panel>

        <x-panel :title="__('Payment details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="payment_date" :label="__('Date')" type="date" required
                              :value="$payment->payment_date?->toDateString() ?? now()->toDateString()" />
                <x-form.money name="amount" :label="__('Amount')" :value="$invoice ? $invoice->balance() : 0" required />
                <x-form.select name="method" :label="__('Method')" :options="$methods"
                               :selected="$payment->method?->value ?? 'transfer'" required />
                <x-form.input name="reference_number" :label="__('Reference number')" :value="$payment->reference_number"
                              :placeholder="__('Bank transaction or receipt number')" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="notes" :label="__('Notes')" :value="$payment->notes" rows="2" />
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save payment') }}</x-form.submit>
                <a href="{{ route('payments.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection