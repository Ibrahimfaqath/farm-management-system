@extends('layouts.app', ['title' => $receipt->receipt_number])

@section('content')
    <x-page-header :title="$receipt->receipt_number"
                   :subtitle="$receipt->supplier->name.' · '.$receipt->received_date->translatedFormat('d M Y')"
                   :back="route('goods-receipts.index')">
        <x-slot:actions>
            @if ($receipt->status->value === 'draft' && auth()->user()->hasAbility('feed.receive'))
                <form method="POST" action="{{ route('goods-receipts.post', $receipt) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm">Konfirmasi &amp; Tambah Stok</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($receipt->status->value === 'draft')
        <div class="mb-4">
            <x-alert-row tone="warning" title="Penerimaan masih draf"
                         description="Stok belum bertambah. Konfirmasi penerimaan agar batch dibuat dan stok naik." />
        </div>
    @elseif ($receipt->status->affectsInventory())
        <div class="mb-4">
            <x-alert-row tone="info" title="Diterima: {{ number_format((float) $receipt->items->sum('quantity_received'), 2) }} unit dari pesanan"
                         :description="'Stok bertambah: '.number_format((float) $receipt->items->sum('quantity_received'), 2).' unit senilai '.number_format((float) $receipt->total_amount, 0).' rupiah. Batch sudah dibuat otomatis.'" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Status" :value="$receipt->status->label()" :tone="$receipt->status->affectsInventory() ? 'success' : 'warning'" />
        <x-stat label="Nilai Penerimaan" :value="'Rp'.number_format($totalAmount, 0, ',', '.')" />
        <x-stat label="Sudah Dibayar" :value="'Rp'.number_format($paidAmount, 0, ',', '.')" tone="info" />
        <x-stat label="Sisa Utang" :value="'Rp'.number_format($outstanding, 0, ',', '.')" :tone="$outstanding > 0 ? 'warning' : 'success'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Rincian Barang" padding="p-0">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item Pakan</th>
                                <th class="text-right">Diterima</th>
                                <th class="text-right">Harga</th>
                                <th class="text-right">Nilai</th>
                                <th>Batch</th>
                                <th>Kedaluwarsa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($receipt->items as $item)
                                <tr>
                                    <td>
                                        <p class="font-medium">{{ $item->feedItem->name }}</p>
                                        <p class="text-[11px] text-base-content/45">{{ $item->feedItem->code }}</p>
                                    </td>
                                    <td class="text-right tabular-nums">{{ number_format((float) $item->quantity_received, 2) }} <span class="text-[11px] text-base-content/50">{{ $item->unit->shortLabel() }}</span></td>
                                    <td class="text-right tabular-nums"><x-money :amount="$item->unit_price" /></td>
                                    <td class="text-right tabular-nums"><x-money :amount="$item->line_total" /></td>
                                    <td>
                                        @if ($item->batch)
                                            <span class="font-mono text-[11px]">{{ $item->batch->batch_code }}</span>
                                            <span class="block text-[11px] text-base-content/50">sisa {{ number_format((float) $item->batch->quantity_remaining, 2) }}</span>
                                        @else
                                            <span class="text-base-content/45">{{ $item->batch_number ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">{{ $item->expiry_date?->translatedFormat('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-base-300 p-3 text-right text-sm font-medium">
                    Total <x-money :amount="$totalAmount" />
                </div>
            </x-panel>

            <x-panel title="Pergerakan Stok" padding="p-0" description="Jejak audit stok yang dihasilkan penerimaan ini.">
                @if ($movements->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Belum ada pergerakan stok karena penerimaan belum dikonfirmasi.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Item</th><th>Jenis</th><th class="text-right">Jumlah</th><th>Batch</th><th>Tanggal</th></tr></thead>
                            <tbody>
                                @foreach ($movements as $movement)
                                    <tr>
                                        <td>{{ $movement->feedItem?->name }}</td>
                                        <td><x-badge :label="$movement->type->label()" :class="$movement->type->badgeClass()" /></td>
                                        <td class="text-right tabular-nums text-success">+{{ number_format((float) $movement->quantity, 2) }}</td>
                                        <td class="font-mono text-[11px]">{{ $movement->batch?->batch_code ?? '—' }}</td>
                                        <td class="whitespace-nowrap">{{ $movement->movement_date->translatedFormat('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel title="Informasi">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Pemasok</dt><dd class="text-right">{{ $receipt->supplier->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Pesanan</dt>
                        <dd class="text-right">
                            @if ($receipt->purchaseOrder)
                                <a href="{{ route('purchase-orders.show', $receipt->purchaseOrder) }}" class="text-primary hover:underline">{{ $receipt->purchaseOrder->po_number }}</a>
                            @else
                                <span class="text-base-content/45">Tanpa pesanan</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Surat Jalan</dt><dd>{{ $receipt->delivery_note_reference ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Diterima Oleh</dt><dd>{{ $receipt->receiver?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Konfirmasi</dt><dd>{{ $receipt->posted_at?->translatedFormat('d M Y H:i') ?? 'Belum' }}</dd></div>
                </dl>
            </x-panel>

            @if ($receipt->quality_notes)
                <x-panel title="Catatan Mutu">
                    <p class="text-xs leading-relaxed text-base-content/70">{{ $receipt->quality_notes }}</p>
                </x-panel>
            @endif

            @if ($receipt->payments->isNotEmpty())
                <x-panel title="Pembayaran ke Pemasok" padding="p-0">
                    <ul class="divide-y divide-base-300">
                        @foreach ($receipt->payments as $payment)
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <span class="text-xs">{{ $payment->payment_date->translatedFormat('d M Y') }} · {{ $payment->method->label() }}</span>
                                <x-money :amount="$payment->amount" class="text-sm" />
                            </li>
                        @endforeach
                    </ul>
                </x-panel>
            @endif
        </div>
    </div>
@endsection
