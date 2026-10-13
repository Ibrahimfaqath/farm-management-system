@extends('layouts.app', ['title' => __('Record feed consumption')])

@section('content')
    <x-page-header :title="__('Record feed consumption')"
                   :subtitle="__('Stock decreases by the quantity used. Batches are allocated automatically, oldest first.')"
                   :back="route('feed-consumptions.index')" />

    <form method="POST" action="{{ route('feed-consumptions.store') }}" class="max-w-5xl space-y-4">
        @csrf

        <x-panel :title="__('Consumption context')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="farm_id" :label="__('Farm')" :options="$farms"
                               :selected="$barn?->farm_id ?? ($farms->keys()->first())" required />
                <x-form.select name="barn_id" :label="__('Barn')" :options="$barns" :selected="$barn?->id" />
                <x-form.input name="consumption_date" :label="__('Date')" type="date" :value="now()->toDateString()" required />
                <x-form.input name="animal_count" :label="__('Number of animals')" type="number" min="0"
                              :value="$barn?->animals()->where('status', 'active')->count()" />
            </div>
        </x-panel>

        <x-panel :title="__('Feed used')" padding="p-0"
                 :description="__('Enter the quantity actually given. Available stock is shown per item.')">
            <div class="table-scroll" data-line-editor>
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th class="w-2/5">{{ __('Feed item') }}</th>
                            <th class="w-32">{{ __('Available stock') }}</th>
                            <th class="w-40">{{ __('Batch') }}</th>
                            <th class="w-32">{{ __('Quantity') }}</th>
                            <th class="w-10 text-center"><span class="sr-only">{{ __('Remove') }}</span></th>
                        </tr>
                    </thead>
                    <tbody id="fc-items">
                        <tr data-line-row>
                            <td>
                                <select name="items[0][feed_item_id]" data-line-item required
                                        class="select select-sm w-full bg-base-100" aria-label="{{ __('Feed item') }}">
                                    <option value="">{{ __('— Select item —') }}</option>
                                    @foreach ($feedItems as $item)
                                        <option value="{{ $item->id }}"
                                                data-stock="{{ (float) $item->usable_stock }}"
                                                data-unit="{{ $item->unit->shortLabel() }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="fc-stock text-sm tabular-nums text-base-content/60">—</td>
                            <td>
                                <select name="items[0][inventory_batch_id]" data-line-batch
                                        class="select select-sm w-full bg-base-100" aria-label="{{ __('Batch') }}">
                                    <option value="">{{ __('Automatic FIFO') }}</option>
                                </select>
                            </td>
                            <td>
                                <input name="items[0][quantity]" type="number" step="0.01" min="0.01" required
                                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Quantity') }}">
                            </td>
                            <td class="text-center"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-base-300 p-3">
                <button type="button" class="btn btn-ghost btn-sm" data-line-add="fc-items" data-line-template="fc-line-template">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add line') }}
                </button>
            </div>

            @error('items')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('items.*.quantity')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('items.*.feed_item_id')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('barn_id')<p class="px-3 pb-2 text-xs text-tone-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('Notes')" rows="2"
                             :placeholder="__('For example: morning and evening feeding')" />
        </x-panel>

        <x-panel :title="__('Negative stock')"
                 :description="__('Use this only when it is genuinely needed, for example to correct the books.')">
            <div class="space-y-3">
                <x-form.checkbox name="allow_negative_stock" value="1" :label="__('Allow consumption beyond available stock')"
                                 :hint="__('Requires owner or manager permission and always needs a reason.')" />
                <x-form.input name="override_reason" :label="__('Override reason')" minlength="10"
                              :placeholder="__('For example: scale reading was too high, it will be corrected next week')" />
                @error('override_reason')<p class="mt-1 text-xs text-tone-error">{{ $message }}</p>@enderror
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save consumption') }}</x-form.submit>
                <a href="{{ route('feed-consumptions.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>

    <template id="fc-line-template">
        <tr data-line-row>
            <td>
                <select name="items[__INDEX__][feed_item_id]" data-line-item required
                        class="select select-sm w-full bg-base-100" aria-label="{{ __('Feed item') }}">
                    <option value="">{{ __('— Select item —') }}</option>
                    @foreach ($feedItems as $item)
                        <option value="{{ $item->id }}" data-stock="{{ (float) $item->usable_stock }}"
                                data-unit="{{ $item->unit->shortLabel() }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="fc-stock text-sm tabular-nums text-base-content/60">—</td>
            <td>
                <select name="items[__INDEX__][inventory_batch_id]" data-line-batch
                        class="select select-sm w-full bg-base-100" aria-label="{{ __('Batch') }}">
                    <option value="">{{ __('Automatic FIFO') }}</option>
                </select>
            </td>
            <td>
                <input name="items[__INDEX__][quantity]" type="number" step="0.01" min="0.01" required
                       class="input input-sm w-full bg-base-100" aria-label="{{ __('Quantity') }}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-ghost btn-xs text-tone-error" data-line-remove
                        aria-label="{{ __('Remove line') }}">&times;</button>
            </td>
        </tr>
    </template>

    <script type="application/json" id="batches-data">{!! json_encode($batchesByItem) !!}</script>
@endsection

@push('scripts')
    <script>
        (function () {
            var batches = JSON.parse(document.getElementById('batches-data').textContent || '{}');
            var automaticLabel = @json(__('Automatic FIFO'));

            function refreshLine(select) {
                var row = select.closest('[data-line-row]');
                var option = select.options[select.selectedIndex];
                var stockCell = row.querySelector('.fc-stock');
                var batchSelect = row.querySelector('[data-line-batch]');

                if (stockCell) {
                    var stock = option && option.dataset.stock ? Number(option.dataset.stock) : null;
                    stockCell.textContent = stock === null
                        ? '—'
                        : stock.toLocaleString('id-ID') + ' ' + (option.dataset.unit ?? '');
                }

                if (!batchSelect) {
                    return;
                }

                batchSelect.innerHTML = '<option value="">' + automaticLabel + '</option>';
                var available = select.value && batches[select.value] ? batches[select.value] : [];

                available.forEach(function (batch) {
                    var node = document.createElement('option');
                    node.value = batch.id;
                    node.textContent = batch.label;
                    batchSelect.appendChild(node);
                });
            }

            // Only touches this page's stock and batch columns; adding and
            // removing rows is handled once in app.js.
            document.addEventListener('change', function (event) {
                var select = event.target instanceof Element
                    ? event.target.closest('#fc-items [data-line-item]')
                    : null;

                if (select) {
                    refreshLine(select);
                }
            });
        })();
    </script>
@endpush