@extends('layouts.app', ['title' => 'Inventori'])

@section('content')
    <x-page-header title="Inventori Pakan" subtitle="Stok saat ini diturunkan dari riwayat pergerakan, bukan angka yang diedit manual">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.adjust'))
                <a href="{{ route('inventory.batches') }}" class="btn btn-sm">Batch</a>
                <a href="{{ route('inventory.movements') }}" class="btn btn-sm">Pergerakan Stok</a>
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('adjust-modal').showModal()">Penyesuaian</button>
            @endif
            @if (auth()->user()->hasAbility('feed.consume'))
                <a href="{{ route('feed-consumptions.create') }}" class="btn btn-primary btn-sm">Catat Pemakaian</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Nilai Stok" :value="'Rp'.number_format($totalValue, 0, ',', '.')" tone="primary" />
        <x-stat label="Stok Menipis" :value="$lowStock->count()" :tone="$lowStock->count() > 0 ? 'warning' : 'success'"
               hint="di bawah batas pemesanan ulang" />
        <x-stat label="Stok Habis" :value="$outOfStock->count()" :tone="$outOfStock->count() > 0 ? 'error' : 'success'" />
        <x-stat label="Jenis Pakan Aktif" :value="$items->count()" />
    </div>

    @if ($lowStock->isNotEmpty())
        <div class="mb-4 space-y-2">
            @foreach ($lowStock->take(4) as $item)
                <x-alert-row tone="warning"
                             :title="$item->name.' tersisa '.number_format((float) $item->current_stock, 1).' '.$item->unit->shortLabel().', batas pemesanan ulang '.number_format((float) $item->reorder_point, 1)"
                             :description="'Rata-rata pemakaian 30 hari: '.number_format((float) $item->daily_consumption, 1).' '.$item->unit->shortLabel().($item->days_remaining ? ' · estimasi '.$item->days_remaining.' hari lagi' : '')"
                             :url="route('purchase-orders.create')" url-label="Buat PO" />
            @endforeach
        </div>
    @endif

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-3">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari item pakan…" class="input input-sm input bg-base-100">
            <select name="category" class="select select-sm select bg-base-100">
                <option value="">Semua kategori</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('inventory.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($items->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada item pakan aktif"
                              description="Tambahkan item pakan lalu buat pesanan pembelian dan penerimaan barang untuk mulai melacak stok."
                              action-label="Tambah Item Pakan" action-url="{{ route('feed-items.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Kategori</th>
                            <th class="text-right">Stok</th>
                            <th class="text-right">Batas Ulang</th>
                            <th class="text-right">Rata-rata/Hari</th>
                            <th class="text-right">Estimasi</th>
                            <th class="text-right">Nilai</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php
                                $isLow = (float) $item->reorder_point > 0 && (float) $item->current_stock <= (float) $item->reorder_point;
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $item->name }}</p>
                                    <p class="text-[11px] text-base-content/45">{{ $item->code }}</p>
                                </td>
                                <td class="text-base-content/70">{{ $item->category->label() }}</td>
                                <td class="text-right tabular-nums font-medium {{ (float) $item->current_stock <= 0 ? 'text-error' : '' }}">
                                    {{ number_format((float) $item->current_stock, 2) }} <span class="text-[11px] text-base-content/50">{{ $item->unit->shortLabel() }}</span>
                                </td>
                                <td class="text-right tabular-nums text-base-content/65">{{ number_format((float) $item->reorder_point, 2) }}</td>
                                <td class="text-right tabular-nums text-base-content/65">{{ number_format((float) $item->daily_consumption, 2) }}</td>
                                <td class="text-right tabular-nums">
                                    @if ($item->days_remaining === null)
                                        <span class="text-base-content/45">—</span>
                                    @elseif ($item->days_remaining <= 7)
                                        <span class="text-warning">{{ $item->days_remaining }} hr</span>
                                    @else
                                        {{ $item->days_remaining }} hr
                                    @endif
                                </td>
                                <td class="text-right tabular-nums"><x-money :amount="$item->stock_value" /></td>
                                <td>
                                    @if ((float) $item->current_stock <= 0)
                                        <x-badge label="Habis" class="badge-soft badge-error" />
                                    @elseif ($isLow)
                                        <x-badge label="Menipis" class="badge-soft badge-warning" />
                                    @else
                                        <x-badge label="Aman" class="badge-soft badge-success" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>

    @if (auth()->user()->hasAbility('feed.adjust'))
        <dialog id="adjust-modal" class="modal">
            <div class="modal-box max-w-lg">
                <h3 class="text-base font-semibold">Penyesuaian Stok</h3>
                <p class="mt-1 text-xs text-base-content/60">
                    Setiap penyesuaian wajib menyertakan alasan dan tercatat sebagai pergerakan stok terpisah.
                </p>
                <form method="POST" action="{{ route('inventory.adjust') }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-form.select name="feed_item_id" label="Item Pakan" required class="sm:col-span-2"
                                       :options="$items->pluck('name', 'id')" />
                        <x-form.select name="direction" label="Arah" required
                                       :options="['in' => 'Tambah (+)', 'out' => 'Kurangi (-)']" />
                        <x-form.input name="quantity" label="Jumlah" type="number" step="0.01" min="0.01" required />
                        <x-form.input name="movement_date" label="Tanggal" type="date" :value="now()->toDateString()" />
                        <x-form.input name="reason" label="Alasan" required minlength="5"
                                      placeholder="Contoh: Koreksi timbangan gudang" />
                    </div>
                    <x-form.textarea name="notes" label="Catatan" rows="2" />
                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('adjust-modal').close()">Batal</button>
                        <button class="btn btn-primary btn-sm">Simpan Penyesuaian</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>Tutup</button></form>
        </dialog>
    @endif
@endsection
