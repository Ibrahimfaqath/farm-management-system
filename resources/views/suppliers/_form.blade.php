@php $editing = $supplier->exists; @endphp
@extends('layouts.app', ['title' => $editing ? __('Edit supplier') : __('Add supplier')])

@section('content')
    <x-page-header :title="$editing ? __('Edit :name', ['name' => $supplier->name]) : __('Add supplier')"
                   :back="route('suppliers.index')" />

    <form method="POST" action="{{ $editing ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
          class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel :title="__('Identity')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="code" :label="__('Supplier code')" :value="$nextCode ?? $supplier->code" readonly maxlength="30" :hint="__('Assigned automatically when saved.')" />
                <x-form.input name="name" :label="__('Supplier name')" :value="$supplier->name" required maxlength="150" />
                <x-form.select name="category" :label="__('Supplier category')" :options="$categories" :selected="$supplier->category" required />
                <x-form.input name="tax_number" :label="__('Tax number')" :value="$supplier->tax_number" maxlength="40" />
            </div>
        </x-panel>

        <x-panel :title="__('Contact')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="contact_person" :label="__('Contact person')" :value="$supplier->contact_person" />
                <x-form.input name="phone" :label="__('Phone')" :value="$supplier->phone" />
                <x-form.input name="email" :label="__('Email')" type="email" :value="$supplier->email" />
                <x-form.input name="city" :label="__('City')" :value="$supplier->city" />
                <x-form.input name="address" :label="__('Address')" :value="$supplier->address" />
                <x-form.input name="payment_terms_days" :label="__('Payment terms (days)')" type="number" min="0" max="180"
                              :value="$supplier->payment_terms_days ?? 0" />
            </div>
            <div class="mt-4">
                <x-form.checkbox name="is_active" :label="__('Supplier is active')" :checked="$supplier->is_active ?? true" />
            </div>
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('Notes')" :value="$supplier->notes" rows="3" />
        </x-panel>

        <x-form.actions boxed>
            <x-form.submit>{{ $editing ? __('Save changes') : __('Save supplier') }}</x-form.submit>
            <a href="{{ route('suppliers.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
        </x-form.actions>
    </form>
@endsection