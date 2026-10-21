@php $editing = $customer->exists; @endphp
@extends('layouts.app', ['title' => $editing ? __('Edit customer') : __('Add customer')])

@section('content')
    <x-page-header :title="$editing ? __('Edit :name', ['name' => $customer->name]) : __('Add customer')"
                   :back="route('customers.index')" />

    <form method="POST" action="{{ $editing ? route('customers.update', $customer) : route('customers.store') }}"
          class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel :title="__('Identity')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="code" :label="__('Customer code')" :value="$nextCode ?? $customer->code" readonly maxlength="30" :hint="__('Assigned automatically when saved.')" />
                <x-form.input name="name" :label="__('Customer name')" :value="$customer->name" required maxlength="150" />
                <x-form.select name="type" :label="__('Customer type')" :options="$types" :selected="$customer->type" required />
                <x-form.select name="payment_terms_days" :label="__('Payment terms')" :options="$termOptions"
                               :selected="$customer->payment_terms_days ?? 30" required />
                <x-form.money name="credit_limit" :label="__('Credit limit')" :value="$customer->credit_limit ?? 0" />
                <x-form.input name="contact_person" :label="__('Contact person')" :value="$customer->contact_person" />
                <x-form.input name="phone" :label="__('Phone')" :value="$customer->phone" />
                <x-form.input name="email" :label="__('Email')" type="email" :value="$customer->email" />
                <x-form.input name="city" :label="__('City')" :value="$customer->city" />
                <x-form.input name="address" :label="__('Address')" :value="$customer->address" />
            </div>
            <div class="mt-4">
                <x-form.checkbox name="is_active" :label="__('Customer is active')" :checked="$customer->is_active ?? true" />
            </div>
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('Notes')" :value="$customer->notes" rows="3" />
        </x-panel>

        <x-form.actions boxed>
            <x-form.submit>{{ $editing ? __('Save changes') : __('Save customer') }}</x-form.submit>
            <a href="{{ route('customers.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
        </x-form.actions>
    </form>
@endsection