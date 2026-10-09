@extends('layouts.app', ['title' => 'Pesanan Pembelian'])

@section('content')
    <x-page-header title="Pesanan Pembelian"
                   subtitle="Pesanan tidak menambah stok; stok bertambah saat penerimaan barang dikonfirmasi">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.purchase'))
                <a href="{{ route('goods-receipts.index') }}" class="btn btn-sm">Penerimaan Barang</a>
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">+ Pesanan Baru</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($openOrders > 0)
        <div class="mb-4">
            <x-alert-row tone="info" title="{{ $openOrders }} pesanan pembelian belum diterima penuh"
                         description="Sisa pesanan menunggu penerimaan barang dari pemasok."
                         :url="route('purchase-orders.index', ['status' => 'partially_received'])" url-label="Lihat" />
        </div>
    @endif

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nomor PO…" class="input input-sm input bg-base-100">
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
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($purchaseOrders->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada pesanan pembelian"
                              description="Pesanan pembelian mencatat komitmen Belanja. Stok baru bertambah setelah penerimaan barang dikonfirmasi."
                              action-label="Buat Pesanan Pembelian" action-url="{{ route('purchase-orders.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Pemasok</th>
                            <th>Tanggal</th>
                            <th>Perkiraan</th>
                            <th>Status</th>
                            <th class="text-right">Item</th>
                            <th class="text-right">Nilai</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseOrders as $order)
                            <tr>
                                <td class="font-mono text-xs">
                                    <a href="{{ route('purchase-orders.show', $order) }}" class="text-primary hover:underline">{{ $order->po_number }}</a>
                                </td>
                                <td>{{ $order->supplier->name }}</td>
                                <td class="whitespace-nowrap">{{ $order->order_date->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap text-base-content/65">{{ $order->expected_date?->translatedFormat('d M Y') ?? '—' }}</td>
                                <td><x-badge :label="$order->status->label()" :class="$order->status->badgeClass()" /></td>
                                <td class="text-right tabular-nums">{{ $order->items_count }}</td>
                                <td class="text-right"><x-money :amount="$order->total" /></td>
                                <td class="text-right"><a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $purchaseOrders->links() }}</div>
        @endif
    </x-panel>
@endsection
