@extends('layouts.app', ['title' => 'Item Pakan'])

@section('content')
    <x-page-header title="Item Pakan" subtitle="Master data pakan dan ambang pemesanan ulang">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.purchase'))
                <a href="{{ route('inventory.index') }}" class="btn btn-sm">Lihat Stok</a>
                <a href="{{ route('feed-items.create') }}" class="btn btn-primary btn-sm">+ Tambah Item</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($lowStockCount > 0)
        <div class="mb-4">
            <x-alert-row tone="warning" title="{{ $lowStockCount }} item pakan berada di bawah batas pemesanan ulang"
                         description="Periksa halaman Inventori untuk daftar dan estimasi kebutuhan."
                         :url="route('inventory.index')" url-label="Buka Inventori" />
        </div>
    @endif

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-3">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama atau kode…" class="input input-sm input bg-base-100">
            <select name="category" class="select select-sm select bg-base-100">
                <option value="">Semua kategori</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="low_stock" value="1" @checked($filters['low_stock'] ?? false) class="checkbox checkbox-sm checkbox-primary">
                    Stok menipis
                </label>
                <button class="btn btn-neutral btn-sm">Cari</button>
                <a href="{{ route('feed-items.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($feedItems->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada item pakan" description="Tambahkan master pakan agar bisa dipesan dan dilacak stoknya."
                              action-label="Tambah Item Pakan" action-url="{{ route('feed-items.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Stok</th>
                            <th class="text-right">Batas Ulang</th>
                            <th>Lokasi</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($feedItems as $item)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $item->name }}</p>
                                    <p class="text-[11px] text-base-content/45">{{ $item->code }}</p>
                                </td>
                                <td class="text-base-content/70">{{ $item->category->label() }}</td>
                                <td>{{ $item->unit->shortLabel() }}</td>
                                <td class="text-right tabular-nums"><x-money :amount="$item->price_per_unit" /></td>
                                <td class="text-right tabular-nums">{{ number_format((float) ($item->current_stock ?? 0), 2) }}</td>
                                <td class="text-right tabular-nums text-base-content/65">{{ number_format((float) $item->reorder_point, 2) }}</td>
                                <td class="text-base-content/65">{{ $item->storage_location ?? '—' }}</td>
                                <td class="text-right whitespace-nowrap">
                                    @if (auth()->user()->hasAbility('feed.purchase'))
                                        <a href="{{ route('feed-items.edit', $item) }}" class="btn btn-ghost btn-xs">Ubah</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $feedItems->links() }}</div>
        @endif
    </x-panel>
@endsection
