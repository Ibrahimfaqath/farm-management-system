@php $editing = $purchaseOrder->exists; @endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Pesanan Pembelian' : 'Pesanan Pembelian Baru'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah '.$purchaseOrder->po_number : 'Pesanan Pembelian Baru'"
                   subtitle="Pesanan hanya mencatat komitmen belanja dan tidak menambah stok."
                   :back="route('purchase-orders.index')" />

    <form method="POST" action="{{ $editing ? route('purchase-orders.update', $purchaseOrder) : route('purchase-orders.store') }}"
          class="max-w-5xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel title="Informasi Pesanan">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="supplier_id" label="Pemasok" :options="$suppliers" :selected="$purchaseOrder->supplier_id" required />
                <x-form.input name="order_date" label="Tanggal Pesan" type="date" :value="($purchaseOrder->order_date ?? now())->toDateString()" required />
                <x-form.input name="expected_date" label="Perkiraan Tiba" type="date" :value="($purchaseOrder->expected_date ?? now()->addDays(7))->toDateString()" />
                <x-form.money name="discount_amount" label="Diskon" :value="$purchaseOrder->discount_amount ?? 0" />
            </div>
        </x-panel>

        <x-panel title="Item Pesanan" description="Tambahkan minimal satu item pakan.">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-2/5">Item Pakan</th>
                            <th class="w-24">Jumlah</th>
                            <th class="w-24">Satuan</th>
                            <th class="w-36">Harga Satuan</th>
                            <th class="w-40">Nomor Batch</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="po-items">
                        @php
                            $rows = $editing && $purchaseOrder->items->isNotEmpty()
                                ? $purchaseOrder->items
                                : collect([null]);
                        @endphp
                        @foreach ($rows as $row)
                            <tr class="po-line">
                                <td>
                                    <select name="items[{{ $loop->index }}][feed_item_id]" class="select select-sm select w-full bg-base-100" required>
                                        <option value="">— Pilih item —</option>
                                        @foreach ($feedItems as $item)
                                            <option value="{{ $item->id }}" data-price="{{ (float) $item->price_per_unit }}" data-unit="{{ $item->unit->shortLabel() }}"
                                                    @selected($row && (int) $row->feed_item_id === $item->id)>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input name="items[{{ $loop->index }}][quantity]" type="number" step="0.01" min="0.01" required
                                           value="{{ $row ? (float) $row->quantity : '' }}" class="input input-sm input w-full bg-base-100">
                                </td>
                                <td>
                                    <input name="items[{{ $loop->index }}][unit]" type="text" readonly
                                           value="{{ $row ? $row->unit->shortLabel() : '' }}" class="input input-sm input w-full bg-base-200 text-base-content/60">
                                </td>
                                <td>
                                    <input name="items[{{ $loop->index }}][unit_price]" type="number" step="1" min="0" required
                                           value="{{ $row ? (float) $row->unit_price : '' }}" class="input input-sm input w-full bg-base-100">
                                </td>
                                <td>
                                    <input name="items[{{ $loop->index }}][batch_hint]" type="text"
                                           value="{{ $row?->batch_hint }}" class="input input-sm input w-full bg-base-100">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-ghost btn-xs text-error" onclick="this.closest('tr').remove()">&times;</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <button type="button" class="btn btn-ghost btn-sm" onclick="addPoLine()">+ Tambah Baris</button>
                <p class="text-xs text-base-content/50">Subtotal dihitung otomatis dari jumlah dikali harga satuan.</p>
            </div>

            @error('items')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.feed_item_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.quantity')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan" :value="$purchaseOrder->notes" rows="2" />
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">{{ $editing ? 'Simpan Perubahan' : 'Simpan Pesanan' }}</button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>

    <template id="po-line-template">
        <tr class="po-line">
            <td>
                <select name="items[__INDEX__][feed_item_id]" class="select select-sm select w-full bg-base-100" required>
                    <option value="">— Pilih item —</option>
                    @foreach ($feedItems as $item)
                        <option value="{{ $item->id }}" data-price="{{ (float) $item->price_per_unit }}" data-unit="{{ $item->unit->shortLabel() }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input name="items[__INDEX__][quantity]" type="number" step="0.01" min="0.01" required class="input input-sm input w-full bg-base-100"></td>
            <td><input name="items[__INDEX__][unit]" type="text" readonly class="input input-sm input w-full bg-base-200 text-base-content/60"></td>
            <td><input name="items[__INDEX__][unit_price]" type="number" step="1" min="0" required class="input input-sm input w-full bg-base-100"></td>
            <td><input name="items[__INDEX__][batch_hint]" type="text" class="input input-sm input w-full bg-base-100"></td>
            <td class="text-center"><button type="button" class="btn btn-ghost btn-xs text-error" onclick="this.closest('tr').remove()">&times;</button></td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('change', function (event) {
            const select = event.target.closest('.po-line select[name*="feed_item_id"]');
            if (!select) return;
            const row = select.closest('tr');
            const option = select.options[select.selectedIndex];
            row.querySelector('input[name*="unit_price"]').value = option?.dataset.price ?? '';
            const unitInput = row.querySelector('input[name*="unit"]');
            if (unitInput) unitInput.value = option?.dataset.unit ?? '';
        });

        function addPoLine() {
            const body = document.getElementById('po-items');
            const index = body.querySelectorAll('tr').length;
            const html = document.getElementById('po-line-template').innerHTML.replaceAll('__INDEX__', index);
            body.insertAdjacentHTML('beforeend', html);
        }
    </script>
@endpush
