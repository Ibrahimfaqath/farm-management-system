@php $editing = $farm->exists; @endphp
@extends('layouts.app', ['title' => $editing ? __('Edit farm') : __('Add farm')])

@section('content')
    <x-page-header :title="$editing ? __('Edit :name', ['name' => $farm->name]) : __('Add farm')"
                   :back="route('farms.index')" />

    <form method="POST" action="{{ $editing ? route('farms.update', $farm) : route('farms.store') }}" class="max-w-4xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel :title="__('Identity')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-form.input name="code" :label="__('Code')" :value="$farm->code" required maxlength="20" placeholder="FRM-001" />
                <x-form.input name="name" :label="__('Farm name')" :value="$farm->name" required maxlength="150" />
                <x-form.input name="manager_name" :label="__('Manager name')" :value="$farm->manager_name" />
                <x-form.input name="phone" :label="__('Phone')" :value="$farm->phone" />
                <x-form.input name="area_hectares" :label="__('Land area (ha)')" type="number" step="0.01" min="0" :value="$farm->area_hectares" />
                <x-form.input name="established_date" :label="__('Established on')" type="date" :value="$farm->established_date?->toDateString()" />
            </div>
        </x-panel>

        <x-panel :title="__('Address')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-form.input name="address" :label="__('Street address')" :value="$farm->address" />
                <x-form.input name="village" :label="__('Village')" :value="$farm->village" />
                <x-form.input name="district" :label="__('District')" :value="$farm->district" />
                <x-form.input name="regency" :label="__('City / Regency')" :value="$farm->regency" />
                <x-form.input name="province" :label="__('Province')" :value="$farm->province" />
                <x-form.input name="postal_code" :label="__('Postal code')" :value="$farm->postal_code" maxlength="10" />
            </div>
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('General notes')" :value="$farm->notes" rows="3" />
        </x-panel>

        <x-form.actions boxed>
            <x-form.submit>{{ $editing ? __('Save changes') : __('Save farm') }}</x-form.submit>
            <a href="{{ route('farms.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
        </x-form.actions>
    </form>
@endsection