@php $editing = $purchaseOrder->exists; @endphp
@extends('layouts.app', ['title' => $editing ? __('Edit purchase order') : __('New purchase order')])

@section('content')
    <x-page-header :title="$editing ? __('Edit :number', ['number' => $purchaseOrder->po_number]) : __('New purchase order')"
                   :subtitle="__('An order records a buying commitment only. It never increases stock.')"
                   :back="route('purchase-orders.index')" />

    <form method="POST" action="{{ $editing ? route('purchase-orders.update', $purchaseOrder) : route('purchase-orders.store') }}"
          class="max-w-5xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel :title="__('Order information')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="supplier_id" :label="__('Supplier')" :options="$suppliers" :selected="$purchaseOrder->supplier_id" required />
                <x-form.input name="order_date" :label="__('Order date')" type="date" required
                              :value="($purchaseOrder->order_date ?? now())->toDateString()" />
                <x-form.input name="expected_date" :label="__('Expected arrival')" type="date"
                              :value="($purchaseOrder->expected_date ?? now()->addDays(7))->toDateString()" />
                <x-form.money name="discount_amount" :label="__('Discount')" :value="$purchaseOrder->discount_amount ?? 0" />
            </div>
        </x-panel>

        <x-panel :title="__('Order lines')" :description="__('Add at least one feed item.')" padding="p-0" data-line-editor data-line-currency="Rp">
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th class="w-2/5">{{ __('Feed item') }}</th>
                            <th class="w-24">{{ __('Quantity') }}</th>
                            <th class="w-24">{{ __('Unit') }}</th>
                            <th class="w-36">{{ __('Unit price') }}</th>
                            <th class="w-40">{{ __('Batch number') }}</th>
                            <th class="w-36 text-right tabular-nums">{{ __('Line total') }}</th>
                            <th class="w-10 text-center"><span class="sr-only">{{ __('Remove') }}</span></th>
                        </tr>
                    </thead>
                    <tbody id="po-items">
                        @php
                            $rows = $editing && $purchaseOrder->items->isNotEmpty()
                                ? $purchaseOrder->items
                                : collect([null]);
                        @endphp
                        @foreach ($rows as $row)
                            <tr data-line-row>
                                <td>
                                    <select name="items[{{ $loop->index }}][feed_item_id]" data-line-item required
                                            class="select select-sm w-full bg-base-100" aria-label="{{ __('Feed item') }}">
                                        <option value="">{{ __('— Select item —') }}</option>
                                        @foreach ($feedItems as $item)
                                            <option value="{{ $item->id }}" data-price="{{ (float) $item->price_per_unit }}"
                                                    data-unit="{{ $item->unit->shortLabel() }}"
                                                    @selected($row && (int) $row->feed_item_id === $item->id)>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input name="items[{{ $loop->index }}][quantity]" type="number" step="0.01" min="0.01" required data-line-amount
                                           value="{{ $row ? (float) $row->quantity : '' }}"
                                           class="input input-sm w-full bg-base-100" aria-label="{{ __('Quantity') }}">
                                </td>
                                <td>
                                    <input name="items[{{ $loop->index }}][unit]" type="text" readonly data-line-unit
                                           value="{{ $row ? $row->unit->shortLabel() : '' }}"
                                           class="input input-sm w-full bg-base-200 text-base-content/60" tabindex="-1" aria-label="{{ __('Unit') }}">
                                </td>
                                <td>
                                    <input name="items[{{ $loop->index }}][unit_price]" type="number" step="1" min="0" required data-line-price
                                           value="{{ $row ? (float) $row->unit_price : '' }}"
                                           class="input input-sm w-full bg-base-100" aria-label="{{ __('Unit price') }}">
                                </td>
                                <td>
                                    <input name="items[{{ $loop->index }}][batch_hint]" type="text"
                                           value="{{ $row?->batch_hint }}" class="input input-sm w-full bg-base-100"
                                           aria-label="{{ __('Batch number') }}">
                                </td>
                                <td class="text-right text-sm tabular-nums text-base-content/70" data-line-total>—</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-ghost btn-xs text-tone-error" data-line-remove
                                            aria-label="{{ __('Remove line') }}">&times;</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-base-300 p-3">
                <button type="button" class="btn btn-ghost btn-sm" data-line-add="po-items" data-line-template="po-line-template">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add line') }}
                </button>
                <p class="text-xs text-base-content/50">
                    {{ __('Order total') }}
                    <span class="ml-1 font-semibold text-base-content tabular-nums" data-line-grand-total>—</span>
                </p>
            </div>

            @error('items')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('items.*.feed_item_id')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('items.*.quantity')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('Notes')" :value="$purchaseOrder->notes" rows="2" />
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ $editing ? __('Save changes') : __('Save purchase order') }}</x-form.submit>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>

    <template id="po-line-template">
        <tr data-line-row>
            <td>
                <select name="items[__INDEX__][feed_item_id]" data-line-item required
                        class="select select-sm w-full bg-base-100" aria-label="{{ __('Feed item') }}">
                    <option value="">{{ __('— Select item —') }}</option>
                    @foreach ($feedItems as $item)
                        <option value="{{ $item->id }}" data-price="{{ (float) $item->price_per_unit }}"
                                data-unit="{{ $item->unit->shortLabel() }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input name="items[__INDEX__][quantity]" type="number" step="0.01" min="0.01" required data-line-amount
                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Quantity') }}">
            </td>
            <td>
                <input name="items[__INDEX__][unit]" type="text" readonly data-line-unit
                       class="input input-sm w-full bg-base-200 text-base-content/60" tabindex="-1" aria-label="{{ __('Unit') }}">
            </td>
            <td>
                <input name="items[__INDEX__][unit_price]" type="number" step="1" min="0" required data-line-price
                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Unit price') }}">
            </td>
            <td>
                <input name="items[__INDEX__][batch_hint]" type="text" class="input input-sm w-full bg-base-100"
                       aria-label="{{ __('Batch number') }}">
            </td>
            <td class="text-right text-sm tabular-nums text-base-content/70" data-line-total>—</td>
            <td class="text-center">
                <button type="button" class="btn btn-ghost btn-xs text-tone-error" data-line-remove
                        aria-label="{{ __('Remove line') }}">&times;</button>
            </td>
        </tr>
    </template>
@endsection