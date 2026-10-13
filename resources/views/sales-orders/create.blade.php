@extends('layouts.app', ['title' => __('New sales order')])

@section('content')
    <x-page-header :title="__('New sales order')"
                   :subtitle="__('An order is not revenue until an invoice is created from it.')"
                   :back="route('sales-orders.index')" />

    <form method="POST" action="{{ route('sales-orders.store') }}" class="max-w-5xl space-y-4">
        @csrf

        <x-panel :title="__('Order information')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="customer_id" :label="__('Customer')" :options="$customers"
                               :selected="old('customer_id', $salesOrder->customer_id ?? $customer?->id)" required class="sm:col-span-2" />
                <x-form.input name="order_date" :label="__('Date')" type="date" required
                              :value="$salesOrder->order_date?->toDateString() ?? now()->toDateString()" />
                <x-form.money name="discount_amount" :label="__('Discount')" :value="$salesOrder->discount_amount ?? 0" />
            </div>
        </x-panel>

        <x-panel :title="__('Items')" :description="__('Add at least one line of goods being sold.')" padding="p-0">
            <div class="table-scroll" data-line-editor>
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th class="w-1/5">{{ __('Product') }}</th>
                            <th class="w-2/5">{{ __('Description') }}</th>
                            <th class="w-24">{{ __('Quantity') }}</th>
                            <th class="w-24">{{ __('Unit') }}</th>
                            <th class="w-36">{{ __('Unit price') }}</th>
                            <th class="w-10 text-center"><span class="sr-only">{{ __('Remove') }}</span></th>
                        </tr>
                    </thead>
                    <tbody id="so-items">
                        <tr data-line-row>
                            <td>
                                <select name="items[0][product_id]" data-line-item data-line-fill-description
                                        class="select select-sm w-full bg-base-100" aria-label="{{ __('Product') }}">
                                    <option value="">{{ __('— No product —') }}</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-name="{{ $product->name }}"
                                                data-price="{{ (float) $product->default_price }}"
                                                data-unit="{{ $product->unit->shortLabel() }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input name="items[0][description]" data-line-description type="text" required
                                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Description') }}">
                            </td>
                            <td>
                                <input name="items[0][quantity]" type="number" step="0.01" min="0.01" required
                                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Quantity') }}">
                            </td>
                            <td>
                                <input name="items[0][unit]" type="text" readonly data-line-unit tabindex="-1"
                                       class="input input-sm w-full bg-base-200 text-base-content/60" aria-label="{{ __('Unit') }}">
                            </td>
                            <td>
                                <input name="items[0][unit_price]" type="number" step="1000" min="0" required data-line-price
                                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Unit price') }}">
                            </td>
                            <td class="text-center"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-base-300 p-3">
                <button type="button" class="btn btn-ghost btn-sm" data-line-add="so-items" data-line-template="so-line-template">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add line') }}
                </button>
            </div>

            @error('items')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('items.*.description')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('items.*.quantity')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('Notes')" :value="$salesOrder->notes" rows="2" />
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save sales order') }}</x-form.submit>
                <a href="{{ route('sales-orders.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>

    <template id="so-line-template">
        <tr data-line-row>
            <td>
                <select name="items[__INDEX__][product_id]" data-line-item data-line-fill-description
                        class="select select-sm w-full bg-base-100" aria-label="{{ __('Product') }}">
                    <option value="">{{ __('— No product —') }}</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-name="{{ $product->name }}"
                                data-price="{{ (float) $product->default_price }}"
                                data-unit="{{ $product->unit->shortLabel() }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input name="items[__INDEX__][description]" data-line-description type="text" required
                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Description') }}">
            </td>
            <td>
                <input name="items[__INDEX__][quantity]" type="number" step="0.01" min="0.01" required
                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Quantity') }}">
            </td>
            <td>
                <input name="items[__INDEX__][unit]" type="text" readonly data-line-unit tabindex="-1"
                       class="input input-sm w-full bg-base-200 text-base-content/60" aria-label="{{ __('Unit') }}">
            </td>
            <td>
                <input name="items[__INDEX__][unit_price]" type="number" step="1000" min="0" required data-line-price
                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Unit price') }}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-ghost btn-xs text-tone-error" data-line-remove
                        aria-label="{{ __('Remove line') }}">&times;</button>
            </td>
        </tr>
    </template>
@endsection