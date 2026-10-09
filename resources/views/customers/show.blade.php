@extends('layouts.app', ['title' => $customer->name])

@section('content')
    <x-page-header :title="$customer->name" :subtitle="$customer->code.' · '.($customer->city ?? '—')" :back="route('customers.index')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create') }}" class="btn btn-sm">Catat Pembayaran</a>
            @endif
            @if (auth()->user()->hasAbility('sales.manage'))
                <a href="{{ route('sales-orders.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm">+ Pesanan</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Piutang Berjalan" :value="'Rp'.number_format($balance, 0, ',', '.')" :tone="$balance > 0 ? 'warning' : 'success'" />
        <x-stat label="Faktur" :value="$customer->invoices_count" />
        <x-stat label="Pesanan" :value="$customer->sales_orders_count" />
        <x-stat label="Termin" :value="$customer->payment_terms_days.' hari'" />
    </div>

    @if ($balance > (float) $customer->credit_limit && (float) $customer->credit_limit > 0)
        <div class="mb-4">
            <x-alert-row tone="error" title="Piutang melampaui limit kredit"
                         :description="'Limit Rp'.number_format((float) $customer->credit_limit, 0).', piutang saat ini Rp'.number_format($balance, 0).'.'" />
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Faktur" padding="p-0">
                @if ($invoices->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Belum ada faktur untuk pelanggan ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Nomor</th><th>Tanggal</th><th>Jatuh Tempo</th><th>Status</th><th class="text-right">Total</th><th class="text-right">Sisa</th></tr></thead>
                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td><a href="{{ route('invoices.show', $invoice) }}" class="text-primary hover:underline">{{ $invoice->invoice_number }}</a></td>
                                        <td class="whitespace-nowrap">{{ $invoice->invoice_date->translatedFormat('d M Y') }}</td>
                                        <td class="whitespace-nowrap">{{ $invoice->due_date->translatedFormat('d M Y') }}</td>
                                        <td>
                                            <x-badge :label="$invoice->status->label()" :class="$invoice->status->badgeClass()" />
                                            @if ($invoice->isOverdue())<x-badge label="Terlambat" class="badge-soft badge-error" />@endif
                                        </td>
                                        <td class="text-right"><x-money :amount="$invoice->total" /></td>
                                        <td class="text-right"><x-money :amount="$invoice->balance()" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>

            <x-panel title="Pembayaran" padding="p-0">
                @if ($payments->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Belum ada pembayaran.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Nomor</th><th>Tanggal</th><th>Faktur</th><th>Metode</th><th class="text-right">Jumlah</th></tr></thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td class="font-mono text-[11px]">{{ $payment->payment_number }}</td>
                                        <td class="whitespace-nowrap">{{ $payment->payment_date->translatedFormat('d M Y') }}</td>
                                        <td class="text-[11px]">{{ $payment->invoice?->invoice_number ?? '—' }}</td>
                                        <td>{{ $payment->method->label() }}</td>
                                        <td class="text-right"><x-money :amount="$payment->amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel title="Informasi">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Jenis</dt><dd>{{ $customer->type }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Kontak</dt><dd>{{ $customer->contact_person ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Telepon</dt><dd>{{ $customer->phone ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Limit Kredit</dt><dd><x-money :amount="$customer->credit_limit" /></dd></div>
            </dl>
            @if ($customer->notes)
                <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed">{{ $customer->notes }}</p>
            @endif
        </x-panel>
    </div>
@endsection
