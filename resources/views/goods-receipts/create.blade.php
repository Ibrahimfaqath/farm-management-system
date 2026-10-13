@php
    $orderedLines = $purchaseOrder
        ? $purchaseOrder->items->map(function ($item) use ($purchaseOrder) {
            $received = (float) ($purchaseOrder->receivedQuantities()[$item->getKey()] ?? 0);

            return [
                'id' => $item->getKey(),
                'feed_item_id' => $item->feed_item_id,
                'name' => $item->feedItem->name,
                'unit' => $item->unit->shortLabel(),
                'ordered' => (float) $item->quantity,
                'received' => $received,
                'outstanding' => max(0, (float) $item->quantity - $received),
                'unit_price' => (float) $item->unit_price,
            ];
        })
        : collect();

    /*
     * Built here rather than passed inline on the select: a closure holding
     * parentheses inside a string literal confuses Blade's component-tag
     * parser, which then emits the whole component tag as literal text and
     * silently drops the field from the page.
     */
    $purchaseOrderOptions = $openOrders->mapWithKeys(fn ($order) => [
        $order->id => $order->po_number.' · '.$order->supplier->name
            .' ('.__('outstanding').' '.number_format($order->totalOutstanding(), 2).')',
    ]);
@endphp
@extends('layouts.app', ['title' => __('Record goods receipt')])

@section('content')
    <x-page-header :title="__('Record goods receipt')"
                   :subtitle="__('A receipt without a purchase order can still be recorded by choosing the supplier.')"
                   :back="route('goods-receipts.index')" />

    <form method="POST" action="{{ route('goods-receipts.store') }}" class="max-w-5xl space-y-4">
        @csrf

        <x-panel :title="__('Receipt source')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="purchase_order_id" :label="__('Purchase order')" class="lg:col-span-2"
                               :placeholder="__('— Without an order (free-form receipt) —')"
                               :options="$purchaseOrderOptions"
                               :selected="$purchaseOrder?->id" />
                <x-form.input name="received_date" :label="__('Receipt date')" type="date" :value="now()->toDateString()" required />
                <x-form.input name="delivery_note_reference" :label="__('Delivery note number')" placeholder="DO-0001" />
                <x-form.input name="supplier_id" :label="__('Supplier (when there is no purchase order)')" type="number"
                              :value="$purchaseOrder?->supplier_id" class="hidden" />
            </div>

            @if ($purchaseOrder)
                <div class="mt-4 alert alert-soft alert-info px-3 py-2.5 text-xs">
                    <p class="font-medium">{{ __('Outstanding on :number', ['number' => $purchaseOrder->po_number]) }}</p>
                    <ul class="mt-1.5 space-y-1">
                        @foreach ($orderedLines as $line)
                            <li class="flex justify-between gap-3">
                                <span>{{ $line['name'] }}</span>
                                <span class="tabular-nums">
                                    {{ __('ordered') }} {{ number_format($line['ordered'], 2) }} · {{ __('received') }} {{ number_format($line['received'], 2) }} ·
                                    <span @class(['font-medium', 'text-tone-warning' => $line['outstanding'] > 0, 'text-tone-success' => $line['outstanding'] <= 0])>
                                        {{ __('outstanding') }} {{ number_format($line['outstanding'], 2) }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-panel>

        <x-panel :title="__('Received items')" padding="p-0"
                 :description="__('Fill in a smaller quantity when the supplier delivers partly.')">
            <div class="table-scroll" data-line-editor>
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th class="w-2/5">{{ __('Feed item') }}</th>
                            <th class="w-28">{{ __('Received') }}</th>
                            <th class="w-24">{{ __('Unit') }}</th>
                            <th class="w-36">{{ __('Price') }}</th>
                            <th class="w-36">{{ __('Batch number') }}</th>
                            <th class="w-32">{{ __('Expiry') }}</th>
                            <th class="w-10 text-center"><span class="sr-only">{{ __('Remove') }}</span></th>
                        </tr>
                    </thead>
                    <tbody id="gr-items">
                        @foreach ($orderedLines as $line)
                            @if ($line['outstanding'] > 0)
                                <tr data-line-row>
                                    <td>
                                        <input type="hidden" name="items[{{ $loop->index }}][purchase_order_item_id]" value="{{ $line['id'] }}">
                                        <select name="items[{{ $loop->index }}][feed_item_id]" data-line-item required
                                            class="select select-sm w-full bg-base-100">
                                            <option value="">{{ __('— Select item —') }}</option>
                                            @foreach ($feedItems as $item)
                                                <option value="{{ $item->id }}" data-price="{{ (float) $item->price_per_unit }}" data-unit="{{ $item->unit->shortLabel() }}"
                                                        @selected($item->id === $line['feed_item_id'])>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-meta mt-1 text-base-content/50">
                                            {{ __('Outstanding: :qty :unit', ['qty' => number_format($line['outstanding'], 2), 'unit' => $line['unit']]) }}
                                        </p>
                                    </td>
                                    <td>
                                        <input name="items[{{ $loop->index }}][quantity_received]" type="number" step="0.01" min="0.01" required
                                               value="{{ $line['outstanding'] > 0 ? $line['outstanding'] : '' }}"
                                               max="{{ $line['outstanding'] }}"
                                               class="input input-sm w-full bg-base-100" aria-label="{{ __('Received quantity') }}">
                                    </td>
                                    <td><input name="items[{{ $loop->index }}][unit_display]" data-line-unit type="text" readonly value="{{ $line['unit'] }}" class="input input-sm input w-full bg-base-200 text-base-content/60"></td>
                                    <td><input name="items[{{ $loop->index }}][unit_price]" type="number" step="1" min="0" required value="{{ $line['unit_price'] }}" data-line-price class="input input-sm input w-full bg-base-100"></td>
                                    <td><input name="items[{{ $loop->index }}][batch_number]" type="text" class="input input-sm input w-full bg-base-100" placeholder="{{ __('optional') }}" aria-label="{{ __('Batch number') }}"></td>
                                    <td><input name="items[{{ $loop->index }}][expiry_date]" type="date" class="input input-sm input w-full bg-base-100"></td>
                                    <td class="text-center"><button type="button" class="btn btn-ghost btn-xs text-tone-error" data-line-remove>&times;</button></td>
                                </tr>
                            @endif
                        @endforeach

                        @if ($orderedLines->isEmpty() || $orderedLines->every(fn ($l) => $l['outstanding'] <= 0))
                            <tr data-line-row>
                                <td>
                                    <select name="items[0][feed_item_id]" data-line-item required
                                            class="select select-sm w-full bg-base-100">
                                        <option value="">{{ __('— Select item —') }}</option>
                                        @foreach ($feedItems as $item)
                                            <option value="{{ $item->id }}" data-price="{{ (float) $item->price_per_unit }}" data-unit="{{ $item->unit->shortLabel() }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input name="items[0][quantity_received]" type="number" step="0.01" min="0.01" required class="input input-sm w-full bg-base-100" aria-label="{{ __('Received quantity') }}"></td>
                                <td><input name="items[0][unit_display]" type="text" readonly data-line-unit tabindex="-1" class="input input-sm w-full bg-base-200 text-base-content/60" aria-label="{{ __('Unit') }}"></td>
                                <td><input name="items[0][unit_price]" type="number" step="1" min="0" required data-line-price class="input input-sm w-full bg-base-100" aria-label="{{ __('Unit price') }}"></td>
                                <td><input name="items[0][batch_number]" type="text" class="input input-sm input w-full bg-base-100" placeholder="{{ __('optional') }}" aria-label="{{ __('Batch number') }}"></td>
                                <td><input name="items[0][expiry_date]" type="date" class="input input-sm input w-full bg-base-100"></td>
                                <td class="text-center"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="border-t border-base-300 p-3">
                <button type="button" class="btn btn-ghost btn-sm" data-line-add="gr-items" data-line-template="gr-line-template">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add line') }}
                </button>
            </div>

            @error('items')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('items.*.quantity_received')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('items.*.purchase_order_item_id')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('items.*.inventory_batch_id')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel :title="__('Notes')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.textarea name="quality_notes" :label="__('Quality notes')" rows="2"
                                 :placeholder="__('For example: moisture 38%, no mould')" />
                <x-form.textarea name="notes" :label="__('General notes')" rows="2" />
            </div>
            <div class="mt-4">
                <x-form.checkbox name="post_now" :label="__('Confirm now and increase stock immediately')"
                                 :hint="__('When this is unticked the receipt is saved as a draft and stock does not change.')"
                                 :checked="true" />
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save goods receipt') }}</x-form.submit>
                <a href="{{ route('goods-receipts.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>

    <template id="gr-line-template">
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
                <input name="items[__INDEX__][quantity_received]" type="number" step="0.01" min="0.01" required
                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Received quantity') }}">
            </td>
            <td>
                <input name="items[__INDEX__][unit_display]" type="text" readonly data-line-unit tabindex="-1"
                       class="input input-sm w-full bg-base-200 text-base-content/60" aria-label="{{ __('Unit') }}">
            </td>
            <td>
                <input name="items[__INDEX__][unit_price]" type="number" step="1" min="0" required data-line-price
                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Unit price') }}">
            </td>
            <td>
                <input name="items[__INDEX__][batch_number]" type="text" class="input input-sm w-full bg-base-100"
                       :placeholder="__('optional')" aria-label="{{ __('Batch number') }}">
            </td>
            <td>
                <input name="items[__INDEX__][expiry_date]" type="date" class="input input-sm w-full bg-base-100"
                       aria-label="{{ __('Expiry date') }}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-ghost btn-xs text-tone-error" data-line-remove
                        aria-label="{{ __('Remove line') }}">&times;</button>
            </td>
        </tr>
    </template>
@endsection
