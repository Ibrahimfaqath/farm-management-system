@extends('layouts.app', ['title' => __('Record expense')])

@section('content')
    <x-page-header :title="__('Record expense')"
                   :subtitle="__('An expense is recorded together with its cash-out effect so the figures never drift apart.')"
                   :back="route('expenses.index')" />

    <form method="POST" action="{{ route('expenses.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Expense details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="expense_date" :label="__('Date')" type="date" required
                              :value="$expense->expense_date?->toDateString() ?? now()->toDateString()" />
                <x-form.select name="category" :label="__('Category')" :options="$categories" :selected="$expense->category?->value" required />
                <x-form.money name="amount" :label="__('Amount')" :value="0" required />
                <x-form.select name="payment_method" :label="__('Payment method')" :options="$methods"
                               :selected="$expense->payment_method?->value" required />
                <x-form.input name="description" :label="__('Description')" :value="$expense->description" required class="sm:col-span-2"
                              :placeholder="__('For example: warehouse roof repair')" />
                <x-form.select name="supplier_id" :label="__('Registered supplier')" :options="$suppliers" :selected="$expense->supplier_id"
                               :placeholder="__('— Not a registered supplier —')" />
                <x-form.input name="vendor_name" :label="__('Vendor name')" :value="$expense->vendor_name"
                              :placeholder="__('Fill in when it is not a registered supplier')" />
                <x-form.input name="reference" :label="__('Reference / receipt number')" :value="$expense->reference" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="notes" :label="__('Notes')" :value="$expense->notes" rows="2" />
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save expense') }}</x-form.submit>
                <a href="{{ route('expenses.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection