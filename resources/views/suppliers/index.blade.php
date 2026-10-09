@extends('layouts.app', ['title' => 'Pemasok'])

@section('content')
    <x-page-header title="Pemasok" subtitle="Mitra pemasok pakan, peralatan, dan kebutuhan kebun">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.purchase'))
                <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">+ Tambah Pemasok</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-3">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama atau kode…" class="input input-sm input bg-base-100">
            <select name="category" class="select select-sm select bg-base-100">
                <option value="">Semua jenis</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Cari</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($suppliers->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada pemasok" description="Tambahkan pemasok sebelum membuat pesanan pembelian."
                              action-label="Tambah Pemasok" action-url="{{ route('suppliers.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Jenis</th>
                            <th>Kontak</th>
                            <th class="text-right">Termin</th>
                            <th class="text-right">PO</th>
                            <th class="text-right">Penerimaan</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td class="font-mono text-xs">{{ $supplier->code }}</td>
                                <td>
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="font-medium text-primary hover:underline">{{ $supplier->name }}</a>
                                </td>
                                <td class="text-base-content/70">{{ $categories[$supplier->category] ?? $supplier->category }}</td>
                                <td class="text-base-content/65">{{ $supplier->contact_person ?? '—' }}<br><span class="text-[11px]">{{ $supplier->phone ?? '—' }}</span></td>
                                <td class="text-right tabular-nums">{{ $supplier->payment_terms_days }} hr</td>
                                <td class="text-right tabular-nums">{{ $supplier->purchase_orders_count }}</td>
                                <td class="text-right tabular-nums">{{ $supplier->goods_receipts_count }}</td>
                                <td>
                                    <x-badge :label="$supplier->is_active ? 'Aktif' : 'Nonaktif'"
                                             :class="$supplier->is_active ? 'badge-soft badge-success' : 'badge-ghost'" />
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    @if (auth()->user()->hasAbility('feed.purchase'))
                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-ghost btn-xs">Ubah</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $suppliers->links() }}</div>
        @endif
    </x-panel>
@endsection
