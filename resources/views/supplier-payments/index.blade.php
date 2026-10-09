@extends('layouts.app', ['title' => 'Pembayaran Pemasok'])

@section('content')
    <x-page-header title="Pembayaran Pemasok"
                   subtitle="Utang terbentuk dari penerimaan barang terkonfirmasi dan berkurang saat dibayar">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('finance.manage'))
                <a href="{{ route('supplier-payments.create') }}" class="btn btn-primary btn-sm">+ Bayar Pemasok</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <x-stat label="Total Utang" :value="'Rp'.number_format($totalOutstanding, 0, ',', '.')" tone="warning" />
        <x-stat label="Pemasok Berutang" :value="count($outstanding)" />
        <x-stat label="Total Pembayaran" :value="$payments->total()" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-panel title="Riwayat Pembayaran" padding="p-0">
                @if ($payments->isEmpty())
                    <div class="p-4">
                        <x-empty-state title="Belum ada pembayaran pemasok"
                                      description="Pembayaran mengurangi utang dari penerimaan barang yang sudah dikonfirmasi."
                                      action-label="Catat Pembayaran" action-url="{{ route('supplier-payments.create') }}" />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm table-zebra">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Tanggal</th>
                                    <th>Pemasok</th>
                                    <th>Penerimaan</th>
                                    <th>Metode</th>
                                    <th class="text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td class="font-mono text-[11px]">{{ $payment->payment_number }}</td>
                                        <td class="whitespace-nowrap">{{ $payment->payment_date->translatedFormat('d M Y') }}</td>
                                        <td>{{ $payment->supplier->name }}</td>
                                        <td class="text-[11px]">
                                            @if ($payment->goodsReceipt)
                                                <a href="{{ route('goods-receipts.show', $payment->goodsReceipt) }}" class="text-primary hover:underline">{{ $payment->goodsReceipt->receipt_number }}</a>
                                            @else
                                                <span class="text-base-content/45">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $payment->method->label() }}</td>
                                        <td class="text-right font-medium text-warning"><x-money :amount="$payment->amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-base-300 p-3">{{ $payments->links() }}</div>
                @endif
            </x-panel>
        </div>

        <x-panel title="Utang per Pemasok" description="Dari penerimaan barang terkonfirmasi">
            @if (count($outstanding) === 0)
                <p class="text-sm text-base-content/55">Tidak ada utang berjalan.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($outstanding as $row)
                        <li class="flex items-center justify-between gap-3 rounded-md border border-base-300 px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $row['supplier']->name }}</p>
                                <a href="{{ route('suppliers.show', $row['supplier']) }}" class="text-[11px] text-primary hover:underline">Lihat pemasok</a>
                            </div>
                            <x-money :amount="$row['outstanding']" class="shrink-0 text-sm font-medium text-warning" />
                        </li>
                    @endforeach
                </ul>
                @if (auth()->user()->hasAbility('finance.manage'))
                    <a href="{{ route('supplier-payments.create') }}" class="btn btn-primary btn-sm mt-3 w-full">Bayar Pemasok</a>
                @endif
            @endif
        </x-panel>
    </div>
@endsection
