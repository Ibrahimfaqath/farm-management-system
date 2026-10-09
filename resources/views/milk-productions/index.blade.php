@extends('layouts.app', ['title' => 'Produksi Susu'])

@section('content')
    <x-page-header title="Produksi Susu" subtitle="Catatan harian per ekor atau per kelompok kandang">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('production.record'))
                <a href="{{ route('milk-productions.batch') }}" class="btn btn-sm">Input per Kandang</a>
                <a href="{{ route('milk-productions.create') }}" class="btn btn-primary btn-sm">+ Catat Produksi</a>
            @endif
            <a href="{{ route('reports.production.index') }}" class="btn btn-sm">Laporan</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat label="Total Periode" :value="number_format($summary['total'], 1).' L'" tone="primary" />
        <x-stat label="Rata-rata Harian" :value="number_format($summary['per_day'], 1).' L'" />
        <x-stat label="Per Sapi Laktasi" :value="number_format($summary['per_lactating_head'], 1).' L'" />
        <x-stat label="Jumlah Catatan" :value="$summary['records']" />
        <x-stat label="Perbandingan" :value="$trend['change_pct'] === null ? '—' : ($trend['change_pct'] > 0 ? '+' : '').$trend['change_pct'].'%'"
               :tone="match ($trend['direction']) { 'up' => 'success', 'down' => 'warning', default => 'neutral' }"
               hint="rata-rata harian vs periode sebelumnya" />
    </div>

    @if ($trend['direction'] === 'down' && $trend['change_pct'] !== null)
        <div class="mb-4">
            <x-alert-row tone="warning"
                         :title="'Produksi turun '.$trend['change_pct'].'% dibanding periode sebelumnya'"
                         description="Perubahan ini adalah fakta angka, bukan diagnosis. Sistem tidak menyimpulkan penyebabnya." />
        </div>
    @endif

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <select name="farm_id" class="select select-sm select bg-base-100">
                <option value="">Semua peternakan</option>
                @foreach ($farms as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['farm_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="barn_id" class="select select-sm select bg-base-100">
                <option value="">Semua kandang</option>
                @foreach ($barns as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['barn_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
            <input name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('milk-productions.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($productions->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada catatan produksi"
                              description="Catat produksi susu harian per ekor atau per kelompok kandang untuk memantau kinerja produksi."
                              action-label="Catat Produksi" action-url="{{ route('milk-productions.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Sumber</th>
                            <th>Sesi</th>
                            <th class="text-right">Liter</th>
                            <th class="text-right">Lemak %</th>
                            <th class="text-right">Protein %</th>
                            <th>Dicatat Oleh</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productions as $production)
                            <tr>
                                <td class="whitespace-nowrap">{{ $production->production_date->translatedFormat('d M Y') }}</td>
                                <td>
                                    @if ($production->animal)
                                        <a href="{{ route('animals.show', $production->animal) }}" class="font-medium text-primary hover:underline">{{ $production->animal->animal_code }}</a>
                                    @else
                                        {{ $production->barn?->name ?? '—' }}
                                    @endif
                                </td>
                                <td>{{ $production->session->label() }}</td>
                                <td class="text-right tabular-nums font-medium">{{ number_format((float) $production->liters, 2) }}</td>
                                <td class="text-right tabular-nums text-base-content/65">{{ $production->fat_percent ?? '—' }}</td>
                                <td class="text-right tabular-nums text-base-content/65">{{ $production->protein_percent ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $production->recorder?->name ?? '—' }}</td>
                                <td class="max-w-xs truncate text-base-content/60">{{ $production->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $productions->links() }}</div>
        @endif
    </x-panel>
@endsection
