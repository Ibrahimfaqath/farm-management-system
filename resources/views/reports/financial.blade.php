@extends('layouts.app', ['title' => 'Laporan Keuangan'])

@section('content')
    <x-page-header title="Laporan Keuangan"
                   subtitle="Pendapatan, biaya, piutang, utang, dan arus kas — laba bukan sama dengan kas" />

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-4">
        <input name="date_from" type="date" value="{{ $from }}" class="input input-sm input bg-base-100">
        <input name="date_to" type="date" value="{{ $to }}" class="input input-sm input bg-base-100">
        <div class="flex gap-2">
            <button class="btn btn-neutral btn-sm">Terapkan</button>
            <a href="{{ route('reports.financial.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    <section class="mb-5">
        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-base-content/50">Posisi Keuangan Saat Ini</h3>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat label="Kas" :value="'Rp'.number_format($cash, 0, ',', '.')" tone="info" />
            <x-stat label="Piutang" :value="'Rp'.number_format($receivable, 0, ',', '.')" tone="warning"
                   :hint="'terlambat Rp'.number_format($overdue, 0, ',', '.')" />
            <x-stat label="Utang" :value="'Rp'.number_format($payable, 0, ',', '.')" tone="warning" />
            <x-stat label="Arus Kas Bersih Periode"
                   :value="'Rp'.number_format($summary['net_cash'], 0, ',', '.')"
                   :tone="$summary['net_cash'] >= 0 ? 'success' : 'error'" />
        </div>
    </section>

    <section class="mb-5">
        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-base-content/50">Periode {{ \Carbon\Carbon::parse($from)->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($to)->translatedFormat('d M Y') }}</h3>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat label="Pendapatan" :value="'Rp'.number_format($summary['revenue'], 0, ',', '.')" tone="success"
                   hint="dari faktur" />
            <x-stat label="Pendapatan Lain" :value="'Rp'.number_format($summary['other_income'], 0, ',', '.')" tone="success"
                   hint="di luar faktur" />
            <x-stat label="Biaya" :value="'Rp'.number_format($summary['operating_expense'], 0, ',', '.')" tone="warning" />
            <x-stat label="Laba Estimasi" :value="'Rp'.number_format($summary['profit_estimate'], 0, ',', '.')"
                   :tone="$summary['profit_estimate'] >= 0 ? 'primary' : 'error'"
                   hint="pendapatan + lain − biaya" />
        </div>
    </section>

    <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <x-panel title="Arus Kas per Bulan" description="Uang benar-benar masuk dan keluar">
            @if ($cashByMonth->isEmpty())
                <p class="text-sm text-base-content/55">Belum ada transaksi kas pada periode ini.</p>
            @else
                @php $max = max(1, (float) $cashByMonth->flatMap(fn ($v) => [$v['in'], $v['out']])->max()); @endphp
                <div class="space-y-3">
                    @foreach ($cashByMonth as $month => $flow)
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="font-medium">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y') }}</span>
                                <span class="tabular-nums text-xs">
                                    <span class="text-success">+{{ number_format($flow['in'], 0) }}</span>
                                    <span class="text-warning">−{{ number_format($flow['out'], 0) }}</span>
                                </span>
                            </div>
                            <div class="mt-1 flex gap-0.5">
                                <div class="h-2 rounded-l bg-success/70" style="width: {{ $flow['in'] / $max * 100 }}%"></div>
                                <div class="h-2 rounded-r bg-warning/70" style="width: {{ $flow['out'] / $max * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-panel>

        <x-panel title="Biaya per Kategori" padding="p-0">
            @if ($expensesByCategory->isEmpty())
                <p class="p-4 text-sm text-base-content/55">Belum ada biaya pada periode ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead><tr><th>Kategori</th><th class="text-right">Jumlah Catatan</th><th class="text-right">Nilai</th><th class="text-right">Porsi</th></tr></thead>
                        <tbody>
                            @php $totalExpense = (float) $expensesByCategory->sum('total'); @endphp
                            @foreach ($expensesByCategory as $row)
                                <tr>
                                    <td>{{ $expenseCategories[$row['category']] ?? $row['category'] }}</td>
                                    <td class="text-right tabular-nums">{{ $row['records'] }}</td>
                                    <td class="text-right"><x-money :amount="$row['total']" /></td>
                                    <td class="text-right tabular-nums text-base-content/60">
                                        {{ $totalExpense > 0 ? round($row['total'] / $totalExpense * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>
    </div>

    <x-panel title="Pendapatan per Pelanggan" padding="p-0"
             description="Pendapatan diakui saat faktur terbit; kolom terbayar menunjukkan kas yang sudah masuk.">
        @if ($revenueByCustomer->isEmpty())
            <p class="p-4 text-sm text-base-content/55">Belum ada faktur pada periode ini.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th class="text-right">Total Faktur</th>
                            <th class="text-right">Sudah Terbayar</th>
                            <th class="text-right">Belum Dibayar</th>
                            <th class="text-right">Tingkat Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($revenueByCustomer as $row)
                            @php $unpaid = (float) $row['total'] - (float) $row['paid']; @endphp
                            <tr>
                                <td><a href="{{ route('customers.show', $row['customer']) }}" class="font-medium text-primary hover:underline">{{ $row['customer']->name }}</a></td>
                                <td class="text-right"><x-money :amount="$row['total']" /></td>
                                <td class="text-right text-success"><x-money :amount="$row['paid']" /></td>
                                <td class="text-right {{ $unpaid > 0 ? 'text-warning' : 'text-success' }}"><x-money :amount="$unpaid" /></td>
                                <td class="text-right tabular-nums">{{ (float) $row['total'] > 0 ? round($row['paid'] / $row['total'] * 100, 1) : 0 }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>
@endsection