@php $editing = $feedItem->exists; @endphp
@extends('layouts.app', ['title' => $editing ? __('Edit feed item') : __('Add feed item')])

@section('content')
    <x-page-header :title="$editing ? __('Edit :name', ['name' => $feedItem->name]) : __('Add feed item')"
                   :subtitle="__('Unit price is used to value consumption and stock adjustments.')"
                   :back="route('feed-items.index')" />

    <form method="POST" action="{{ $editing ? route('feed-items.update', $feedItem) : route('feed-items.store') }}"
          class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel :title="__('Identity')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="code" :label="__('Item code')" :value="$feedItem->code" required maxlength="30" placeholder="FD-001" />
                <x-form.input name="name" :label="__('Feed name')" :value="$feedItem->name" required maxlength="150" />
                <x-form.select name="category" :label="__('Category')" :options="$categories" :selected="$feedItem->category?->value" required />
                <x-form.select name="unit" :label="__('Unit')" :options="$units" :selected="$feedItem->unit?->value" required />
                <x-form.money name="price_per_unit" :label="__('Price per unit')" :value="$feedItem->price_per_unit ?? 0" required />
                <x-form.input name="storage_location" :label="__('Storage location')" :value="$feedItem->storage_location" maxlength="100" />
            </div>
        </x-panel>

        <x-panel :title="__('Reorder threshold')"
                 :description="__('This only raises a warning. It never places an order on its own.')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="reorder_point" :label="__('Minimum stock level')" type="number" step="0.01" min="0"
                              :value="$feedItem->reorder_point ?? 0"
                              :hint="__('A warning appears when stock reaches this level.')" />
                <x-form.input name="reorder_quantity" :label="__('Suggested order quantity')" type="number" step="0.01" min="0"
                              :value="$feedItem->reorder_quantity ?? 0" />
                <x-form.input name="shelf_life_days" :label="__('Shelf life (days)')" type="number" min="1"
                              :value="$feedItem->shelf_life_days" :hint="__('Leave empty if it does not expire.')" />
            </div>
            <div class="mt-4">
                <x-form.checkbox name="is_active" :label="__('Item is in use')" :checked="$feedItem->is_active ?? true" />
            </div>
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('Notes')" :value="$feedItem->notes" rows="3" />
        </x-panel>

        <x-form.actions boxed>
            <x-form.submit>{{ $editing ? __('Save changes') : __('Save feed item') }}</x-form.submit>
            <a href="{{ route('feed-items.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
        </x-form.actions>
    </form>
@endsection