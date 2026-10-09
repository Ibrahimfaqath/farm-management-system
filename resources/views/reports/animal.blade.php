@extends('layouts.app', ['title' => 'Laporan Peternakan'])

@section('content')
    <x-page-header title="Laporan Peternakan" subtitle="Populasi, status, ras, kandang, dan produksi" />

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-4">
        <select name="farm_id" class="select select-sm select bg-base-100">
            <option value="">Semua peternakan</option>
            @foreach ($farms as $id => $name)
                <option value="{{ $id }}" @selected((int) ($farmId ?? 0) === $id)>{{ $name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 sm:col-span-2">
            <button class="btn btn-neutral btn-sm">Terapkan</button>
            <a href="{{ route('reports.animal.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach ($statusOptions as $value => $label)
            <x-stat :label="$label" :value="$byStatus[$value] ?? 0"
                   :tone="$value === 'active' ? 'primary' : ($value === 'deceased' ? 'error' : 'neutral')" />
        @endforeach
    </div>

    <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <x-panel title="Status Produksi" description="Hanya hewan aktif">
            @if ($byProduction->isEmpty())
                <p class="text-sm text-base-content/55">Belum ada hewan aktif.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($byProduction as $key => $total)
                        @php
                            $enum = App\Enums\ProductionStatus::from($key);
                            $max = (int) $byProduction->max();
                        @endphp
                        <li>
                            <div class="flex justify-between text-sm">
                                <span>{{ $enum->label() }}</span><span class="tabular-nums">{{ $total }}</span>
                            </div>
                            <progress class="progress progress-primary mt-1 h-1.5 w-full" max="100" value="{{ $max > 0 ? $total / $max * 100 : 0 }}"></progress>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel title="Status Breeding" description="Hanya hewan aktif">
            @if ($byBreeding->isEmpty())
                <p class="text-sm text-base-content/55">Belum ada hewan aktif.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($byBreeding as $key => $total)
                        @php $enum = App\Enums\BreedingStatus::from($key); $max = (int) $byBreeding->max(); @endphp
                        <li>
                            <div class="flex justify-between text-sm">
                                <span>{{ $enum->label() }}</span><span class="tabular-nums">{{ $total }}</span>
                            </div>
                            $max > 0 ? $total / $max * 100 : 0 } }"></progress>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel title="Sebaran Usia" description="Hewan aktif">
            @if ($ageBuckets === [])
                <p class="text-sm text-base-content/55">Belum ada data usia.</p>
            @else
                <ul class="space-y-1.5 text-sm">
                    @foreach ($ageBuckets as $bucket => $total)
                        <li class="flex justify-between"><span class="text-base-content/70">{{ $bucket }}</span>
                            <span class="tabular-nums font-medium">{{ $total }}</span></li>
                    @endforeach
                </ul>
            @endif
        </x-panel>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-panel title="Komposisi Ras" padding="p-0">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Ras</th><th class="text-right">Ternak</th><th class="text-right">Prod. Susu Rata-rata</th></tr></thead>
                    <tbody>
                        @foreach ($byBreed as $breed)
                            <tr>
                                <td class="font-medium">{{ $breed->name }}</td>
                                <td class="text-right tabular-nums">{{ $breed->animals_count }}</td>
                                <td class="text-right tabular-nums text-base-content/65">{{ $breed->average_milk_liters ? $breed->average_milk_liters.' L' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>

        <x-panel title="Sebaran Kandang" padding="p-0">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Kandang</th><th>Peternakan</th><th class="text-right">Aktif</th><th class="text-right">Kapasitas</th></tr></thead>
                    <tbody>
                        @foreach ($byBarn as $barn)
                            <tr>
                                <td><a href="{{ route('barns.show', $barn) }}" class="font-medium text-primary hover:underline">{{ $barn->name }}</a></td>
                                <td class="text-base-content/70">{{ $barn->farm->name }}</td>
                                <td class="text-right tabular-nums">{{ $barn->animals_count }}</td>
                                <td class="text-right tabular-nums text-base-content/65">{{ $barn->capacity ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>
    </div>

    <div class="mt-4">
        <x-panel title="Produksi per Ternak"
                 :description="'Periode '.$window['from']->translatedFormat('d M Y').' – '.$window['to']->translatedFormat('d M Y')"
                 padding="p-0">
            @if ($topProducers->isEmpty())
                <p class="p-4 text-sm text-base-content/55">Belum ada produksi pada periode ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead><tr><th>Kode</th><th>Nama</th><th>Kandang</th><th class="text-right">Total Liter</th><th class="text-right">Rata-rata Harian</th></tr></thead>
                        <tbody>
                            @foreach ($topProducers as $row)
                                <tr>
                                    <td><a href="{{ route('animals.show', $row['animal']) }}" class="font-medium text-primary hover:underline">{{ $row['animal']->animal_code }}</a></td>
                                    <td>{{ $row['animal']->name ?: '—' }}</td>
                                    <td class="text-base-content/70">{{ $row['animal']->barn?->name ?? '—' }}</td>
                                    <td class="text-right tabular-nums">{{ number_format($row['total'], 2) }}</td>
                                    <td class="text-right tabular-nums">{{ number_format($row['average'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>
    </div>
@endsection