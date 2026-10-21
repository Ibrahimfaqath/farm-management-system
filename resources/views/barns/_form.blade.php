@php $editing = $barn->exists; @endphp
@extends('layouts.app', ['title' => $editing ? __('Edit barn') : __('Add barn')])

@section('content')
    <x-page-header :title="$editing ? __('Edit :name', ['name' => $barn->name]) : __('Add barn')"
                   :subtitle="__('Housing details used for placement, capacity and stock locations.')"
                   :back="route('barns.index')" />

    <form method="POST" action="{{ $editing ? route('barns.update', $barn) : route('barns.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel :title="__('Barn details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="farm_id" :label="__('Farm')" :options="$farms" :selected="$barn->farm_id" required />
                <x-form.input name="code" :label="__('Barn code')" :value="$nextCode ?? $barn->code" readonly maxlength="20" :hint="__('Assigned automatically when saved.')" />
                <x-form.input name="name" :label="__('Barn name')" :value="$barn->name" required maxlength="150" />
                <x-form.select name="type" :label="__('Barn type')" :options="$types" :selected="$barn->type?->value" required />
                <x-form.input name="capacity" :label="__('Capacity (head)')" type="number" min="1" :value="$barn->capacity" />
                <x-form.input name="area_m2" :label="__('Area (m²)')" type="number" step="0.01" min="0" :value="$barn->area_m2" />
            </div>
            <div class="mt-4">
                <x-form.checkbox name="is_active" :label="__('Barn is in use')" :checked="$barn->is_active ?? true" />
            </div>
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('Notes')" :value="$barn->notes" rows="3" />
        </x-panel>

        <x-form.actions boxed>
            <x-form.submit>{{ $editing ? __('Save changes') : __('Save barn') }}</x-form.submit>
            <a href="{{ route('barns.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
        </x-form.actions>
    </form>
@endsection