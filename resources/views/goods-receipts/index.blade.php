@extends('layouts.app', ['title' => 'Penerimaan Barang'])

@section('content')
    <x-page-header title="Penerimaan Barang" subtitle="Hanya penerimaan yang dikonfirmasi menambah stok">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.receive'))
                <a href="{{ route('goods-receipts.create') }}" class="btn btn-primary btn-sm">+ Catat Penerimaan</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <x-stat label="Draf Belum Dikonfirmasi" :value="$draftCount" :tone="$draftCount > 0 ? 'warning' : 'success'"
               hint="stok belum bertambah" />
        <x-stat label="Utang ke Pemasok" :value="'Rp'.number_format($payableTotal, 0, ',', '.')" :tone="$payableTotal > 0 ? 'warning' : 'success'"
               hint="dari penerimaan terkonfirmasi" />
        <x-stat label="Penerimaan Terkonfirmasi"
        <x-stat label="Penerimaan Terkonfirmasi" :value="$postedCount" tone="success" />
    </div>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nomor penerimaan…" class="input input-sm input bg-base-100">
            <select name="status" class="select select-sm select bg-base-100">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="supplier_id" class="select select-sm select bg-base-100">
                <option value="">Semua pemasok</option>
                @foreach ($suppliers as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['supplier_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('goods-receipts.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($receipts->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada penerimaan barang"
                              description="Catat penerimaan untuk menambah stok. Penerimaan dapat dibuat dari pesanan pembelian agar sisa pesanan terpantau."
                              action-label="Catat Penerimaan" action-url="{{ route('goods-receipts.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Pemasok</th>
                            <th>Pesanan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="text-right">Item</th>
                            <th class="text-right">Nilai</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receipts as $receipt)
                            <tr>
                                <td class="font-mono text-xs">
                                    <a href="{{ route('goods-receipts.show', $receipt) }}" class="text-primary hover:underline">{{ $receipt->receipt_number }}</a>
                                </td>
                                <td>{{ $receipt->supplier->name }}</td>
                                <td class="font-mono text-[11px] text-base-content/65">{{ $receipt->purchaseOrder?->po_number ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $receipt->received_date->translatedFormat('d M Y') }}</td>
                                <td><x-badge :label="$receipt->status->label()" :class="$receipt->status->badgeClass()" /></td>
                                <td class="text-right tabular-nums">{{ $receipt->items_count }}</td>
                                <td class="text-right"><x-money :amount="$receipt->total_amount" /></td>
                                <td class="text-right"><a href="{{ route('goods-receipts.show', $receipt) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $receipts->links() }}</div>
        @endif
    </x-panel>
@endsection
