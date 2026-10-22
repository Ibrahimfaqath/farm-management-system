@extends('layouts.app', ['title' => __('Record milk production')])

@section('content')
    <x-page-header :title="__('Record milk production')"
                   :subtitle="__('One record may cover a single animal or a barn group, never both, so nothing is counted twice.')"
                   :back="route('milk-productions.index')" />

    <form method="POST" action="{{ route('milk-productions.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Production source')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="farm_id" :label="__('Farm')" :options="$farms"
                               :selected="old('farm_id', $animal?->farm_id ?? $farms->keys()->first())" required />
                <x-form.animal-select name="animal_id" :label="__('Animal (individual)')"
                               :options="$animals" :selected="old('animal_id', $animal?->id)"
                               :placeholder="__('— Barn-level record —')" />
                <x-form.select name="barn_id" :label="__('Barn (group)')" :options="$barns" :selected="old('barn_id', $animal?->barn_id)"
                               :placeholder="__('— Per-head record —')" />
                <x-form.input name="production_date" :label="__('Date')" type="date" :value="now()->toDateString()" required />
                <x-form.select name="session" :label="__('Session')" :options="$sessions" :selected="old('session', 'mixed')" required />
            </div>
        </x-panel>

        <x-panel :title="__('Volume and quality')">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-form.input name="liters" :label="__('Volume (liters)')" type="number" step="0.1" min="0" max="200" required />
                <x-form.input name="fat_percent" :label="__('Fat content (%)')" type="number" step="0.01" min="0" max="10" />
                <x-form.input name="protein_percent" :label="__('Protein content (%)')" type="number" step="0.01" min="0" max="15" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="notes" :label="__('Notes')" rows="2" />
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save production') }}</x-form.submit>
                <a href="{{ route('milk-productions.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection