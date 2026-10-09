@extends('layouts.app', ['title' => 'Biaya'])

@section('content')
    <x-page-header title="Biaya" subtitle="Setiap biaya juga tercatat sebagai kas keluar">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('finance.manage'))
                <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">+ Catat Biaya</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Biaya Periode" :value="'Rp'.number_format($summary['operating_expense'], 0, ',', '.')" tone="warning" />
        <x-stat label="Pendapatan Periode" :value="'Rp'.number_format($summary['revenue'], 0, ',', '.')" tone="success" />
        <x-stat label="Kas Keluar Periode" :value="'Rp'.number_format($summary['cash_out'], 0, ',', '.')" />
        <x-stat label="Laba Estimasi" :value="'Rp'.number_format($summary['profit_estimate'], 0, ',', '.')"
               :tone="$summary['profit_estimate'] >= 0 ? 'primary' : 'error'"
               hint="pendapatan + pendapatan lain − biaya" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-panel padding="p-0">
                <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
                    <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari deskripsi…" class="input input-sm input bg-base-100">
                    <select name="category" class="select select-sm select bg-base-100">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
                    <div class="flex gap-2">
                        <button class="btn btn-neutral btn-sm">Terapkan</button>
                        <a href="{{ route('expenses.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    </div>
                </form>

                @if ($expenses->isEmpty())
                    <div class="p-4">
                        <x-empty-state title="Belum ada biaya tercatat" description="Catat biaya operasional seperti pakan, veteriner, dan tenaga kerja."
                                      action-label="Catat Biaya" action-url="{{ route('expenses.create') }}" />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm table-zebra">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Tanggal</th>
                                    <th>Kategori</th>
                                    <th>Deskripsi</th>
                                    <th>Vendor</th>
                                    <th>Metode</th>
                                    <th class="text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($expenses as $expense)
                                    <tr>
                                        <td class="font-mono text-[11px]">{{ $expense->expense_number }}</td>
                                        <td class="whitespace-nowrap">{{ $expense->expense_date->translatedFormat('d M Y') }}</td>
                                        <td>{{ $expense->category->label() }}</td>
                                        <td class="max-w-xs truncate">{{ $expense->description }}</td>
                                        <td class="text-base-content/65">{{ $expense->supplier?->name ?? $expense->vendor_name ?? '—' }}</td>
                                        <td class="text-base-content/65">{{ $expense->payment_method->label() }}</td>
                                        <td class="text-right font-medium"><x-money :amount="$expense->amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-base-300 p-3">{{ $expenses->links() }}</div>
                @endif
            </x-panel>
        </div>

        <x-panel title="Biaya per Kategori" description="Periode berjalan">
            @if ($byCategory->isEmpty())
                <p class="text-sm text-base-content/55">Belum ada biaya pada periode ini.</p>
            @else
                @php $maxTotal = (float) $byCategory->max('total'); @endphp
                <ul class="space-y-2.5">
                    @foreach ($byCategory as $row)
                        <li>
                            <div class="flex justify-between text-sm">
                                <span>{{ $categories[$row['category']] ?? $row['category'] }}</span>
                                <x-money :amount="$row['total']" class="tabular-nums" />
                            </div>
                            <progress class="progress progress-secondary mt-1 h-1.5 w-full" max="100" value="{{ $maxTotal > 0 ? ($row['total'] / $maxTotal) * 100 : 0 }}"></progress>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>
    </div>
@endsection
