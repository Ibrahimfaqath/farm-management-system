@extends('layouts.app', ['title' => 'Pesanan Penjualan Baru'])

@section('content')
    <x-page-header title="Pesanan Penjualan Baru"
                   subtitle="Pesanan belum menjadi pendapatan sampai dibuatkan faktur."
                   :back="route('sales-orders.index')" />

    <form method="POST" action="{{ route('sales-orders.store') }}" class="max-w-5xl space-y-4">
        @csrf

        <x-panel title="Informasi Pesanan">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="customer_id" label="Pelanggan" :options="$customers"
                               :selected="old('customer_id', $salesOrder->customer_id ?? $customer?->id)" required class="sm:col-span-2" />
                <x-form.input name="order_date" label="Tanggal" type="date" :value="$salesOrder->order_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.money name="discount_amount" label="Diskon" :value="$salesOrder->discount_amount ?? 0" />
            </div>
        </x-panel>

        <x-panel title="Barang" description="Tambahkan minimal satu baris barang yang dijual.">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-1/5">Produk</th>
                            <th class="w-2/5">Keterangan</th>
                            <th class="w-24">Jumlah</th>
                            <th class="w-24">Satuan</th>
                            <th class="w-36">Harga</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="so-items">
                        <tr class="so-line">
                            <td>
                                <select name="items[0][product_id]" class="select select-sm select w-full bg-base-100">
                                    <option value="">— Tanpa produk —</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ (float) $product->default_price }}" data-unit="{{ $product->unit->shortLabel() }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input name="items[0][description]" type="text" required class="input input-sm input w-full bg-base-100"></td>
                            <td><input name="items[0][quantity]" type="number" step="0.01" min="0.01" required class="input input-sm input w-full bg-base-100"></td>
                            <td><input name="items[0][unit]" type="text" readonly class="input input-sm input w-full bg-base-200 text-base-content/60"></td>
                            <td><input name="items[0][unit_price]" type="number" step="1000" min="0" required class="input input-sm input w-full bg-base-100"></td>
                            <td class="text-center"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-ghost btn-sm mt-3" onclick="addSoLine()">+ Tambah Baris</button>
            @error('items')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.quantity')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan" :value="$salesOrder->notes" rows="2" />
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Pesanan</button>
                <a href="{{ route('sales-orders.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>

    <template id="so-line-template">
        <tr class="so-line">
            <td>
                <select name="items[__INDEX__][product_id]" class="select select-sm select w-full bg-base-100">
                    <option value="">— Tanpa produk —</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ (float) $product->default_price }}" data-unit="{{ $product->unit->shortLabel() }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input name="items[__INDEX__][description]" type="text" required class="input input-sm input w-full bg-base-100"></td>
            <td><input name="items[__INDEX__][quantity]" type="number" step="0.01" min="0.01" required class="input input-sm input w-full bg-base-100"></td>
            <td><input name="items[__INDEX__][unit]" type="text" readonly class="input input-sm input w-full bg-base-200 text-base-content/60"></td>
            <td><input name="items[__INDEX__][unit_price]" type="number" step="1000" min="0" required class="input input-sm input w-full bg-base-100"></td>
            <td class="text-center"><button type="button" class="btn btn-ghost btn-xs text-error" onclick="this.closest('tr').remove()">&times;</button></td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('change', function (event) {
            const select = event.target.closest('.so-line select[name*="product_id"]');
            if (!select) return;
            const row = select.closest('tr');
            const option = select.options[select.selectedIndex];
            if (!option?.value) return;
            row.querySelector('input[name*="description"]').value = option.dataset.name ?? '';
            row.querySelector('input[name*="unit_price"]').value = option.dataset.price ?? '';
            row.querySelector('input[name*="unit"]').value = option.dataset.unit ?? '';
        });

        function addSoLine() {
            const body = document.getElementById('so-items');
            const index = body.querySelectorAll('tr').length;
            body.insertAdjacentHTML('beforeend', document.getElementById('so-line-template').innerHTML.replaceAll('__INDEX__', index));
        }
    </script>
@endpush
