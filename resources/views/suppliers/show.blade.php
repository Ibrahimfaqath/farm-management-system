@extends('layouts.app', ['title' => $supplier->name])

@section('content')
    <x-page-header :title="$supplier->name" :subtitle="$supplier->code.' · '.$supplier->city" :back="route('suppliers.index')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.purchase'))
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm">+ Pesanan Pembelian</a>
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm">Ubah Data</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Pesanan Pembelian" :value="$supplier->purchase_orders_count" />
        <x-stat label="Penerimaan Barang" :value="$supplier->goods_receipts_count" />
        <x-stat label="Pembayaran" :value="$supplier->payments_count" />
        <x-stat label="Utang Berjalan" :value="'Rp'.number_format($outstanding, 0, ',', '.')" :tone="$outstanding > 0 ? 'warning' : 'success'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Pesanan Pembelian" padding="p-0">
                @if ($purchaseOrders->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Belum ada pesanan pembelian untuk pemasok ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Nomor</th><th>Tanggal</th><th>Status</th><th class="text-right">Item</th><th class="text-right">Nilai</th></tr></thead>
                            <tbody>
                                @foreach ($purchaseOrders as $order)
                                    <tr>
                                        <td><a href="{{ route('purchase-orders.show', $order) }}" class="text-primary hover:underline">{{ $order->po_number }}</a></td>
                                        <td class="whitespace-nowrap">{{ $order->order_date->translatedFormat('d M Y') }}</td>
                                        <td><x-badge :label="$order->status->label()" :class="$order->status->badgeClass()" /></td>
                                        <td class="text-right tabular-nums">{{ $order->items_count }}</td>
                                        <td class="text-right"><x-money :amount="$order->total" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>

            <x-panel title="Penerimaan Barang" padding="p-0">
                @if ($goodsReceipts->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Belum ada penerimaan barang.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Nomor</th><th>Tanggal</th><th>Status</th><th class="text-right">Nilai</th></tr></thead>
                            <tbody>
                                @foreach ($goodsReceipts as $receipt)
                                    <tr>
                                        <td><a href="{{ route('goods-receipts.show', $receipt) }}" class="text-primary hover:underline">{{ $receipt->receipt_number }}</a></td>
                                        <td class="whitespace-nowrap">{{ $receipt->received_date->translatedFormat('d M Y') }}</td>
                                        <td><x-badge :label="$receipt->status->label()" :class="$receipt->status->badgeClass()" /></td>
                                        <td class="text-right"><x-money :amount="$receipt->total_amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel title="Informasi Pemasok">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Kontak</dt><dd>{{ $supplier->contact_person ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Telepon</dt><dd>{{ $supplier->phone ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Email</dt><dd class="break-all">{{ $supplier->email ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">NPWP</dt><dd>{{ $supplier->tax_number ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Termin</dt><dd>{{ $supplier->payment_terms_days }} hari</dd></div>
            </dl>
            @if ($supplier->address)
                <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $supplier->address }}</p>
            @endif
            @if ($supplier->notes)
                <p class="mt-2 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $supplier->notes }}</p>
            @endif
        </x-panel>
    </div>
@endsection
