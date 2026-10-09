@extends('layouts.app', ['title' => 'Laporan Produksi'])

@section('content')
    <x-page-header title="Laporan Produksi" subtitle="Volume susu harian, mingguan, bulanan, dan per ternak" />

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
        <select name="farm_id" class="select select-sm select bg-base-100">
            <option value="">Semua peternakan</option>
            @foreach ($farms as $id => $name)
                <option value="{{ $id }}" @selected((int) ($farmId ?? 0) === $id)>{{ $name }}</option>
            @endforeach
        </select>
        <input name="date_from" type="date" value="{{ $from }}" class="input input-sm input bg-base-100">
        <input name="date_to" type="date" value="{{ $to }}" class="input input-sm input bg-base-100">
        <select name="group_by" class="select select-sm select bg-base-100">
            @foreach (['day' => 'Per Hari', 'week' => 'Per Minggu', 'month' => 'Per Bulan', 'raw' => 'Mentah'] as $value => $label)
                <option value="{{ $value }}" @selected($grouping === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn btn-neutral btn-sm">Terapkan</button>
            <a href="{{ route('reports.production.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat label="Total Liter" :value="number_format($summary['total'], 1).' L'" tone="primary" />
        <x-stat label="Rata-rata Harian" :value="number_format($summary['per_day'], 1).' L'" />
        <x-stat label="Per Sapi Laktasi" :value="number_format($summary['per_lactating_head'], 1).' L'" />
        <x-stat label="Jumlah Catatan" :value="$summary['records']" />
        <x-stat label="vs Periode Lalu"
               :value="$trend['change_pct'] === null ? '—' : ($trend['change_pct'] > 0 ? '+' : '').$trend['change_pct'].'%'"
               :tone="match ($trend['direction']) { 'up' => 'success', 'down' => 'warning', default => 'neutral' }" />
    </div>

    @if ($trend['direction'] === 'down')
        <div class="mb-4">
            <x-alert-row tone="warning"
                         :title="'Produksi turun '.abs($trend['change_pct']).'% dibanding periode sebelumnya'"
                         description="Ini fakta angka, bukan diagnosis. Periksa catatan pakan, kesehatan, dan kondisi hewan." />
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Grafik Produksi"
                     description="{{ match ($grouping) { 'day' => 'Agregasi harian', 'week' => 'Agregasi mingguan', 'month' => 'Agregasi bulanan', default => 'Data mentah' } }}">
                @if ($series === [])
                    <p class="text-sm text-base-content/55">Belum ada produksi pada periode ini.</p>
                @else
                    @php $max = max(array_map(fn ($s) => $s['total'], $series)) ?: 1; @endphp
                    <div class="flex h-56 items-end gap-1 overflow-x-auto">
                        @foreach ($series as $point)
                            <div class="group flex min-w-8 flex-1 flex-col items-center justify-end gap-1"
                                 title="{{ $point['label'] }}: {{ number_format($point['total'], 2) }} L">
                                <div class="w-full rounded-t bg-primary/70 transition-colors group-hover:bg-primary"
                                     style="height: {{ max(2, $point['total'] / $max * 180) }}px"></div>
                                <span class="text-[9px] text-base-content/45">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-panel>

            <x-panel title="Produksi per Ternak" padding="p-0">
                @if ($topAnimals === [])
                    <p class="p-4 text-sm text-base-content/55">Belum ada produksi pada periode ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Kode</th><th>Kandang</th><th class="text-right">Total</th><th class="text-right">Rata-rata</th></tr></thead>
                            <tbody>
                                @foreach ($topAnimals as $row)
                                    <tr>
                                        <td><a href="{{ route('animals.show', $row['animal']) }}" class="font-medium text-primary hover:underline">{{ $row['animal']->animal_code }}</a></td>
                                        <td class="text-base-content/70">{{ $row['animal']->barn?->name ?? '—' }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($row['total'], 2) }} L</td>
                                        <td class="text-right tabular-nums">{{ number_format($row['average'], 2) }} L</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel title="Produksi per Kandang" padding="p-0">
                @if ($byBarn === [])
                    <p class="p-4 text-sm text-base-content/55">Belum ada data.</p>
                @else
                    <ul class="divide-y divide-base-300">
                        @foreach ($byBarn as $row)
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <span class="text-sm">{{ $row['barn'] }}</span>
                                <span class="text-sm tabular-nums font-medium">{{ number_format($row['total'], 1) }} L</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>

            <x-panel title="Penurunan Perlu Dicek"
                     description="Perubahan nyata, penyebabnya perlu diperiksa manusia." padding="p-0">
                @if ($declining === [])
                    <p class="p-4 text-sm text-base-content/55">Tidak ada penurunan signifikan pada periode pembanding.</p>
                @else
                    <ul class="divide-y divide-base-300">
                        @foreach ($declining as $row)
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <a href="{{ route('animals.show', $row['animal'], ['tab' => 'produksi']) }}" class="text-sm text-primary hover:underline">
                                    {{ $row['animal']->animal_code }}
                                </a>
                                <span class="text-sm tabular-nums font-medium text-warning">{{ $row['change_pct'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>
        </div>
    </div>
@endsection