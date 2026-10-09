@extends('layouts.app', ['title' => 'Pesanan Penjualan'])

@section('content')
    <x-page-header title="Pesanan Penjualan" subtitle="Pesanan menjadi dasar pembuatan faktur">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('sales.manage'))
                <a href="{{ route('sales-orders.create') }}" class="btn btn-primary btn-sm">+ Pesanan Baru</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nomor SO…" class="input input-sm input bg-base-100">
            <select name="status" class="select select-sm select bg-base-100">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="customer_id" class="select select-sm select bg-base-100">
                <option value="">Semua pelanggan</option>
                @foreach ($customers as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['customer_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('sales-orders.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($orders->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada pesanan penjualan"
                              description="Pesanan penjualan mencatat barang yang dijual. Faktur dibuat dari pesanan untuk recognise pendapatan."
                              action-label="Buat Pesanan" action-url="{{ route('sales-orders.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="text-right">Item</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="font-mono text-xs">
                                    <a href="{{ route('sales-orders.show', $order) }}" class="text-primary hover:underline">{{ $order->so_number }}</a>
                                </td>
                                <td>{{ $order->customer->name }}</td>
                                <td class="whitespace-nowrap">{{ $order->order_date->translatedFormat('d M Y') }}</td>
                                <td><x-badge :label="$order->status->label()" :class="$order->status->badgeClass()" /></td>
                                <td class="text-right tabular-nums">{{ $order->items_count }}</td>
                                <td class="text-right"><x-money :amount="$order->total" /></td>
                                <td class="text-right"><a href="{{ route('sales-orders.show', $order) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $orders->links() }}</div>
        @endif
    </x-panel>
@endsection
