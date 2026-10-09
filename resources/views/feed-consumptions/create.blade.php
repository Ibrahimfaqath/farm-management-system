@extends('layouts.app', ['title' => 'Catat Pemakaian Pakan'])

@section('content')
    <x-page-header title="Catat Pemakaian Pakan"
                   subtitle="Stok akan berkurang sesuai jumlah yang dipakai. Batch dialokasikan otomatis dengan metode FIFO."
                   :back="route('feed-consumptions.index')" />

    <form method="POST" action="{{ route('feed-consumptions.store') }}" class="max-w-5xl space-y-4">
        @csrf

        <x-panel title="Konteks Pemakaian">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="farm_id" label="Peternakan" :options="$farms"
                               :selected="$barn?->farm_id ?? ($farms->keys()->first())" required />
                <x-form.select name="barn_id" label="Kandang" :options="$barns" :selected="$barn?->id" />
                <x-form.input name="consumption_date" label="Tanggal" type="date" :value="now()->toDateString()" required />
                <x-form.input name="animal_count" label="Jumlah Hewan" type="number" min="0"
                              value="{{ $barn?->animals()->where('status', 'active')->count() }}" />
            </div>
        </x-panel>

        <x-panel title="Pakan yang Dipakai" description="Isi jumlah yang benar-benar diberikan. Stok tersedia ditampilkan per item.">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-2/5">Item Pakan</th>
                            <th class="w-32">Stok Tersedia</th>
                            <th class="w-40">Batch</th>
                            <th class="w-32">Jumlah</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="fc-items">
                        <tr class="fc-line">
                            <td>
                                <select name="items[0][feed_item_id]" class="select select-sm select w-full bg-base-100" required>
                                    <option value="">— Pilih item —</option>
                                    @foreach ($feedItems as $item)
                                        <option value="{{ $item->id }}"
                                                data-stock="{{ (float) $item->usable_stock }}"
                                                data-unit="{{ $item->unit->shortLabel() }}">
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="fc-stock text-sm tabular-nums text-base-content/60">—</td>
                            <td>
                                <select name="items[0][inventory_batch_id]" class="select select-sm select w-full bg-base-100">
                                    <option value="">FIFO otomatis</option>
                                </select>
                            </td>
                            <td><input name="items[0][quantity]" type="number" step="0.01" min="0.01" required class="input input-sm input w-full bg-base-100"></td>
                            <td class="text-center"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-ghost btn-sm mt-3" onclick="addFcLine()">+ Tambah Baris</button>
            @error('items')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.quantity')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.feed_item_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('barn_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan" rows="2" placeholder="Contoh: pemberian pagi dan sore" />
        </x-panel>

        <x-panel title="Stok Negatif" description="Gunakan hanya bila benar-benar diperlukan, misalnya koreksi pembukuan.">
            <div class="space-y-3">
                <x-form.checkbox name="allow_negative_stock" value="1" label="Izinkan pemakaian melebihi stok"
                                 hint="Membutuhkan izin owner atau manager dan wajib menyertakan alasan." />
                <x-form.input name="override_reason" label="Alasan Override" minlength="10"
                              placeholder="Contoh: hasil timbangan lebih tinggi, akan dikoreksi minggu depan" />
                @error('override_reason')<p class="text-xs text-error">{{ $message }}</p>@enderror
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Pemakaian</button>
                <a href="{{ route('feed-consumptions.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>

    <template id="fc-line-template">
        <tr class="fc-line">
            <td>
                <select name="items[__INDEX__][feed_item_id]" class="select select-sm select w-full bg-base-100" required>
                    <option value="">— Pilih item —</option>
                    @foreach ($feedItems as $item)
                        <option value="{{ $item->id }}" data-stock="{{ (float) $item->usable_stock }}" data-unit="{{ $item->unit->shortLabel() }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="fc-stock text-sm tabular-nums text-base-content/60">—</td>
            <td>
                <select name="items[__INDEX__][inventory_batch_id]" class="select select-sm select w-full bg-base-100">
                    <option value="">FIFO otomatis</option>
                </select>
            </td>
            <td><input name="items[__INDEX__][quantity]" type="number" step="0.01" min="0.01" required class="input input-sm input w-full bg-base-100"></td>
            <td class="text-center"><button type="button" class="btn btn-ghost btn-xs text-error" onclick="this.closest('tr').remove()">&times;</button></td>
        </tr>
    </template>

    <script type="application/json" id="batches-data">{!! json_encode($batchesByItem) !!}</script>
@endsection

@push('scripts')
    <script>
        const BATCHES = JSON.parse(document.getElementById('batches-data').textContent);

        function refreshLine(select) {
            const row = select.closest('tr');
            const option = select.options[select.selectedIndex];
            const stockCell = row.querySelector('.fc-stock');
            const batchSelect = row.querySelector('select[name*="inventory_batch_id"]');

            const stock = option?.dataset.stock ? Number(option.dataset.stock) : null;
            stockCell.textContent = stock === null ? '—' : stock.toLocaleString('id-ID') + ' ' + (option.dataset.unit ?? '');

            batchSelect.innerHTML = '<option value="">FIFO otomatis</option>';

            if (select.value && BATCHES[select.value]) {
                BATCHES[select.value].forEach(function (batch) {
                    const opt = document.createElement('option');
                    opt.value = batch.id;
                    opt.textContent = batch.label;
                    batchSelect.appendChild(opt);
                });
            }
        }

        document.addEventListener('change', function (event) {
            const select = event.target.closest('.fc-line select[name*="feed_item_id"]');
            if (select) refreshLine(select);
        });

        function addFcLine() {
            const body = document.getElementById('fc-items');
            const index = body.querySelectorAll('tr').length;
            body.insertAdjacentHTML('beforeend', document.getElementById('fc-line-template').innerHTML.replaceAll('__INDEX__', index));
        }
    </script>
@endpush
