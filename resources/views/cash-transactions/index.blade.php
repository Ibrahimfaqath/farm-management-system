@extends('layouts.app', ['title' => 'Arus Kas'])

@section('content')
    <x-page-header title="Arus Kas" subtitle="Pergerakan uang nyata yang masuk dan keluar">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('finance.manage'))
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('cash-modal').showModal()">Catat Transaksi Kas</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat label="Saldo Kas" :value="'Rp'.number_format($balance, 0, ',', '.')" tone="primary" />
        <x-stat label="Kas Masuk Periode" :value="'Rp'.number_format($summary['cash_in'], 0, ',', '.')" tone="success" />
        <x-stat label="Kas Keluar Periode" :value="'Rp'.number_format($summary['cash_out'], 0, ',', '.')" tone="warning" />
        <x-stat label="Arus Kas Bersih" :value="'Rp'.number_format($summary['net_cash'], 0, ',', '.')"
               :tone="$summary['net_cash'] >= 0 ? 'success' : 'error'" />
        <x-stat label="Pendapatan vs Kas"
               :value="$summary['revenue'] > 0 ? 'Rp'.number_format($summary['cash_in'] / $summary['revenue'] * 100, 0).'%' : '—'"
               hint="kas masuk ÷ pendapatan" />
    </div>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari keterangan…" class="input input-sm input bg-base-100">
            <select name="direction" class="select select-sm select bg-base-100">
                <option value="">Semua arah</option>
                @foreach ($directions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['direction'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="category" class="select select-sm select bg-base-100">
                <option value="">Semua jenis</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('cash-transactions.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($transactions->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada transaksi kas"
                              description="Transaksi kas terbentuk otomatis dari pembayaran pelanggan, pembayaran pemasok, biaya, dan pendapatan lain."
                              action-label="Catat Transaksi Kas" action-url="{{ route('cash-transactions.index') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Keterangan</th>
                            <th>Metode</th>
                            <th>Dicatat Oleh</th>
                            <th class="text-right">Masuk</th>
                            <th class="text-right">Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td class="font-mono text-[11px]">{{ $transaction->transaction_number }}</td>
                                <td class="whitespace-nowrap">{{ $transaction->transaction_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $transaction->category->label() }}</td>
                                <td class="max-w-xs truncate">{{ $transaction->description }}</td>
                                <td class="text-base-content/65">{{ $transaction->method->label() }}</td>
                                <td class="text-base-content/65">{{ $transaction->creator?->name ?? '—' }}</td>
                                <td class="text-right tabular-nums text-success">
                                    {{ $transaction->direction->value === 'in' ? number_format((float) $transaction->amount, 0) : '—' }}
                                </td>
                                <td class="text-right tabular-nums text-warning">
                                    {{ $transaction->direction->value === 'out' ? number_format((float) $transaction->amount, 0) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $transactions->links() }}</div>
        @endif
    </x-panel>

    @if (auth()->user()->hasAbility('finance.manage'))
        <dialog id="cash-modal" class="modal">
            <div class="modal-box max-w-lg">
                <h3 class="text-base font-semibold">Catat Transaksi Kas</h3>
                <p class="mt-1 text-xs text-base-content/60">
                    Gunakan modul Biaya, Pendapatan, Pembayaran Pelanggan, atau Pembayaran Pemasok untuk transaksi otomatis.
                    Formulir ini untuk prive dan transaksi lain.
                </p>
                <form method="POST" action="{{ route('cash-transactions.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-form.input name="transaction_date" label="Tanggal" type="date" :value="now()->toDateString()" required />
                        <x-form.select name="category" label="Jenis" required
                                       :options="['owner_draw' => 'Prive Owners', 'other_outflow' => 'Pengeluaran Lain', 'loan' => 'Penerimaan Pinjaman']" />
                        <x-form.money name="amount" label="Jumlah" :value="0" required />
                        <x-form.select name="method" label="Metode" required
                                       :options="['cash' => 'Tunai', 'transfer' => 'Transfer Bank', 'debit_card' => 'Kartu Debit', 'ewallet' => 'Dompet Digital']" />
                        <x-form.input name="reference_number" label="Nomor Referensi" />
                    </div>
                    <x-form.input name="description" label="Keterangan" required placeholder="Contoh: prive pemilik bulan ini" />
                    <x-form.textarea name="notes" label="Catatan" rows="2" />
                    @error('category')<p class="text-xs text-error">{{ $message }}</p>@enderror
                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('cash-modal').close()">Batal</button>
                        <button class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>Tutup</button></form>
        </dialog>
    @endif
@endsection
