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
@endphp
@extends('layouts.app', ['title' => 'Catat Penerimaan Barang'])

@section('content')
    <x-page-header title="Catat Penerimaan Barang"
                   subtitle="Penerimaan tanpa pesanan pembelian tetap bisa dicatat dengan memilih pemasok."
                   :back="route('goods-receipts.index')" />

    <form method="POST" action="{{ route('goods-receipts.store') }}" class="max-w-5xl space-y-4">
        @csrf

        <x-panel title="Sumber Penerimaan">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="purchase_order_id" label="Pesanan Pembelian" class="lg:col-span-2"
                               placeholder="— Tanpa pesanan (penerimaan bebas) —"
                               :options="$openOrders->mapWithKeys(fn ($o) => [$o->id => $o->po_number.' · '.$o->supplier->name.' (sisa '.$o->totalOutstanding().')'])
                               :selected="$purchaseOrder?->id" />
                <x-form.input name="received_date" label="Tanggal Penerimaan" type="date" :value="now()->toDateString()" required />
                <x-form.input name="delivery_note_reference" label="Nomor Surat Jalan" placeholder="DO-0001" />
                <x-form.input name="supplier_id" label="Pemasok (bila tanpa PO)" type="number"
                              :value="$purchaseOrder?->supplier_id" class="hidden" />
            </div>

            @if ($purchaseOrder)
                <div class="mt-4 alert alert-soft alert-info px-3 py-2.5 text-xs">
                    <p class="font-medium">Sisa pesanan {{ $purchaseOrder->po_number }}</p>
                    <ul class="mt-1.5 space-y-1">
                        @foreach ($orderedLines as $line)
                            <li class="flex justify-between gap-3">
                                <span>{{ $line['name'] }}</span>
                                <span class="tabular-nums">
                                    pesan {{ number_format($line['ordered'], 2) }} · diterima {{ number_format($line['received'], 2) }} ·
                                    <span class="{{ $line['outstanding'] > 0 ? 'text-warning' : 'text-success' }} font-medium">
                                        sisa {{ number_format($line['outstanding'], 2) }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-panel>

        <x-panel title="Barang Diterima" description="Isi hanya sebagian bila pemasok mengirim dalam jumlah tidak penuh.">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-2/5">Item Pakan</th>
                            <th class="w-28">Diterima</th>
                            <th class="w-24">Satuan</th>
                            <th class="w-36">Harga</th>
                            <th class="w-36">No. Batch</th>
                            <th class="w-32">Kedaluwarsa</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="gr-items">
                        @foreach ($orderedLines as $line)
                            @if ($line['outstanding'] > 0)
                                <tr class="gr-line">
                                    <td>
                                        <input type="hidden" name="items[{{ $loop->index }}][purchase_order_item_id]" value="{{ $line['id'] }}">
                                        <select name="items[{{ $loop->index }}][feed_item_id]" class="select select-sm select w-full bg-base-100" required>
                                            <option value="">— Pilih item —</option>
                                            @foreach ($feedItems as $item)
                                                <option value="{{ $item->id }}" data-price="{{ (float) $item->price_per_unit }}" data-unit="{{ $item->unit->shortLabel() }}"
                                                        @selected($item->id === $line['feed_item_id'])>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-[11px] text-base-content/50">Sisa pesanan {{ number_format($line['outstanding'], 2) }} {{ $line['unit'] }}</p>
                                    </td>
                                    <td>
                                        <input name="items[{{ $loop->index }}][quantity_received]" type="number" step="0.01" min="0.01" required
                                               value="{{ $line['outstanding'] > 0 ? $line['outstanding'] : '' }}"
                                               max="{{ $line['outstanding'] }}"
                                               class="input input-sm input w-full bg-base-100">
                                    </td>
                                    <td><input name="items[{{ $loop->index }}][unit_display]" type="text" readonly value="{{ $line['unit'] }}" class="input input-sm input w-full bg-base-200 text-base-content/60"></td>
                                    <td><input name="items[{{ $loop->index }}][unit_price]" type="number" step="1" min="0" required value="{{ $line['unit_price'] }}" class="input input-sm input w-full bg-base-100"></td>
                                    <td><input name="items[{{ $loop->index }}][batch_number]" type="text" class="input input-sm input w-full bg-base-100" placeholder="opsional"></td>
                                    <td><input name="items[{{ $loop->index }}][expiry_date]" type="date" class="input input-sm input w-full bg-base-100"></td>
                                    <td class="text-center"><button type="button" class="btn btn-ghost btn-xs text-error" onclick="this.closest('tr').remove()">&times;</button></td>
                                </tr>
                            @endif
                        @endforeach

                        @if ($orderedLines->isEmpty() || $orderedLines->every(fn ($l) => $l['outstanding'] <= 0))
                            <tr class="gr-line">
                                <td>
                                    <select name="items[0][feed_item_id]" class="select select-sm select w-full bg-base-100" required>
                                        <option value="">— Pilih item —</option>
                                        @foreach ($feedItems as $item)
                                            <option value="{{ $item->id }}" data-price="{{ (float) $item->price_per_unit }}" data-unit="{{ $item->unit->shortLabel() }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input name="items[0][quantity_received]" type="number" step="0.01" min="0.01" required class="input input-sm input w-full bg-base-100"></td>
                                <td><input name="items[0][unit_display]" type="text" readonly class="input input-sm input w-full bg-base-200 text-base-content/60"></td>
                                <td><input name="items[0][unit_price]" type="number" step="1" min="0" required class="input input-sm input w-full bg-base-100"></td>
                                <td><input name="items[0][batch_number]" type="text" class="input input-sm input w-full bg-base-100" placeholder="opsional"></td>
                                <td><input name="items[0][expiry_date]" type="date" class="input input-sm input w-full bg-base-100"></td>
                                <td class="text-center"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-ghost btn-sm mt-3" onclick="addGrLine()">+ Tambah Baris</button>

            @error('items')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.quantity_received')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.purchase_order_item_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.inventory_batch_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel title="Catatan">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.textarea name="quality_notes" label="Catatan Mutu" rows="2"
                                 placeholder="Contoh: moisture 38%, tidak berjamur" />
                <x-form.textarea name="notes" label="Catatan Umum" rows="2" />
            </div>
            <div class="mt-4">
                <x-form.checkbox name="post_now" label="Konfirmasi sekarang dan langsung menambah stok"
                                 hint="Bila tidak dicentang, penerimaan disimpan sebagai draf dan stok belum berubah." :checked="true" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Penerimaan</button>
                <a href="{{ route('goods-receipts.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>

    <template id="gr-line-template">
        <tr class="gr-line">
            <td>
                <select name="items[__INDEX__][feed_item_id]" class="select select-sm select w-full bg-base-100" required>
                    <option value="">— Pilih item —</option>
                    @foreach ($feedItems as $item)
                        <option value="{{ $item->id }}" data-price="{{ (float) $item->price_per_unit }}" data-unit="{{ $item->unit->shortLabel() }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input name="items[__INDEX__][quantity_received]" type="number" step="0.01" min="0.01" required class="input input-sm input w-full bg-base-100"></td>
            <td><input name="items[__INDEX__][unit_display]" type="text" readonly class="input input-sm input w-full bg-base-200 text-base-content/60"></td>
            <td><input name="items[__INDEX__][unit_price]" type="number" step="1" min="0" required class="input input-sm input w-full bg-base-100"></td>
            <td><input name="items[__INDEX__][batch_number]" type="text" class="input input-sm input w-full bg-base-100"></td>
            <td><input name="items[__INDEX__][expiry_date]" type="date" class="input input-sm input w-full bg-base-100"></td>
            <td class="text-center"><button type="button" class="btn btn-ghost btn-xs text-error" onclick="this.closest('tr').remove()">&times;</button></td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('change', function (event) {
            const select = event.target.closest('.gr-line select[name*="feed_item_id"]');
            if (!select) return;
            const row = select.closest('tr');
            const option = select.options[select.selectedIndex];
            const price = row.querySelector('input[name*="unit_price"]');
            if (price && option?.dataset.price) price.value = option.dataset.price;
            const unit = row.querySelector('input[name*="unit_display"]');
            if (unit) unit.value = option?.dataset.unit ?? '';
        });

        function addGrLine() {
            const body = document.getElementById('gr-items');
            const index = body.querySelectorAll('tr').length;
            body.insertAdjacentHTML('beforeend', document.getElementById('gr-line-template').innerHTML.replaceAll('__INDEX__', index));
        }
    </script>
@endpush
