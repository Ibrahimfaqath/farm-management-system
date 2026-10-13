@extends('layouts.app', ['title' => __('Record income')])

@section('content')
    <x-page-header :title="__('Record income')"
                   :subtitle="__('For milk sales, use invoices and payments in the Sales module instead.')"
                   :back="route('incomes.index')" />

    <form method="POST" action="{{ route('incomes.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Income details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="income_date" :label="__('Date')" type="date" required
                              :value="$income->income_date?->toDateString() ?? now()->toDateString()" />
                <x-form.select name="category" :label="__('Category')" :options="$categories" :selected="$income->category?->value" required />
                <x-form.money name="amount" :label="__('Amount')" :value="0" required />
                <x-form.select name="payment_method" :label="__('Method')" :options="$methods" :selected="$income->payment_method?->value" required />
                <x-form.input name="description" :label="__('Description')" :value="$income->description" required class="sm:col-span-2"
                              :placeholder="__('For example: sale of 2 tonnes of manure')" />
                <x-form.input name="source_name" :label="__('Source / recipient')" :value="$income->source_name" />
                <x-form.input name="reference" :label="__('Reference number')" :value="$income->reference" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="notes" :label="__('Notes')" :value="$income->notes" rows="2" />
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save income') }}</x-form.submit>
                <a href="{{ route('incomes.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection