@php $editing = $breed->exists; @endphp
@extends('layouts.app', ['title' => $editing ? __('Edit breed') : __('Add breed')])

@section('content')
    <x-page-header :title="$editing ? __('Edit :name', ['name' => $breed->name]) : __('Add breed')"
                   :subtitle="__('Breed reference data and its productivity parameters.')"
                   :back="route('breeds.index')" />

    <form method="POST" action="{{ $editing ? route('breeds.update', $breed) : route('breeds.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel :title="__('Breed details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="code" :label="__('Breed code')" :value="$nextCode ?? $breed->code" readonly maxlength="20" :hint="__('Assigned automatically when saved.')" />
                <x-form.input name="name" :label="__('Breed name')" :value="$breed->name" required maxlength="150" />
                <x-form.input name="origin" :label="__('Origin')" :value="$breed->origin" />
                <x-form.input name="average_milk_liters" :label="__('Average milk (L/day)')" type="number" step="0.1" min="0"
                              :value="$breed->average_milk_liters" />
                <x-form.input name="average_lactation_days" :label="__('Average lactation (days)')" type="number" min="1"
                              :value="$breed->average_lactation_days" />
                <x-form.input name="adult_weight_kg" :label="__('Adult weight (kg)')" type="number" step="0.1" min="0"
                              :value="$breed->adult_weight_kg" />
            </div>
            <p class="mt-4 text-meta text-base-content/55">{{ __('All breeds are dairy cattle.') }}</p>
            <div class="mt-4">
                <x-form.checkbox name="is_active" :label="__('Breed is in use')" :checked="$breed->is_active ?? true" />
            </div>
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('Notes')" :value="$breed->notes" rows="3" />
        </x-panel>

        <x-form.actions boxed>
            <x-form.submit>{{ $editing ? __('Save changes') : __('Save breed') }}</x-form.submit>
            <a href="{{ route('breeds.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
        </x-form.actions>
    </form>
@endsection