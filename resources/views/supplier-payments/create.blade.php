@extends('layouts.app', ['title' => __('Pay supplier')])

@section('content')
    <x-page-header :title="__('Pay supplier')"
                   :subtitle="__('A payment reduces the payable and is recorded as cash out.')"
                   :back="route('supplier-payments.index')" />

    <form method="POST" action="{{ route('supplier-payments.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Payment source')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="goods_receipt_id" :label="__('Goods receipt')" :options="$openReceipts"
                               :selected="old('goods_receipt_id', $receipt?->id)"
                               :placeholder="__('— General supplier payment —')" />
                <x-form.select name="supplier_id" :label="__('Supplier')" :options="$suppliers"
                               :selected="old('supplier_id', $receipt?->supplier_id)" required />
            </div>
        </x-panel>

        <x-panel :title="__('Payment details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="payment_date" :label="__('Date')" type="date" :value="now()->toDateString()" required />
                <x-form.money name="amount" :label="__('Amount')" :value="$receipt ? $receipt->outstandingAmount() : 0" required />
                <x-form.select name="method" :label="__('Method')" :options="$methods" :selected="$payment->method?->value ?? 'transfer'" required />
                <x-form.input name="reference_number" :label="__('Reference number')" :value="$payment->reference_number" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="notes" :label="__('Notes')" :value="$payment->notes" rows="2" />
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save payment') }}</x-form.submit>
                <a href="{{ route('supplier-payments.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection
