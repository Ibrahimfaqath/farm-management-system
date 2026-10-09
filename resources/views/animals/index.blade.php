@extends('layouts.app', ['title' => 'Ternak'])

@section('content')
    <x-page-header title="Ternak" subtitle="Daftar seluruh hewan beserta status dan lokasi">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('animals.create') }}" class="btn btn-primary btn-sm">+ Tambah Ternak</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
        <x-stat label="Total" :value="$summary['total']" />
        <x-stat label="Aktif" :value="$summary['active']" tone="primary" />
        <x-stat label="Laktasi" :value="$summary['lactating']" />
        <x-stat label="Hamil" :value="$summary['pregnant']" />
        <x-stat label="Anakan" :value="$summary['calves']" />
        <x-stat label="Keluar" :value="$summary['sold'] + $summary['deceased'] + $summary['transferred']" hint="Terjual / mati / pindah" />
    </div>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-2 lg:grid-cols-4">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari kode atau nama…" class="input input-sm input w-full bg-base-100">
            <select name="status" class="select select-sm select w-full bg-base-100">
                <option value="">Semua status</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="production_status" class="select select-sm select w-full bg-base-100">
                <option value="">Semua status produksi</option>
                @foreach ($productionOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['production_status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="barn_id" class="select select-sm select w-full bg-base-100">
                <option value="">Semua kandang</option>
                @foreach ($barns as $id => $label)
                    <option value="{{ $id }}" @selected(($filters['barn_id'] ?? '') == $id)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="health_issue" value="1" @checked($filters['health_issue'] ?? false) class="checkbox checkbox-sm checkbox-primary">
                Ada masalah kesehatan
            </label>
            <select name="breeding_status" class="select select-sm select w-full bg-base-100">
                <option value="">Semua status breeding</option>
                @foreach ($breedingOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['breeding_status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="sex" class="select select-sm select w-full bg-base-100">
                <option value="">Semua jenis kelamin</option>
                @foreach ($sexOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['sex'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('animals.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($animals->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada hewan terdaftar"
                              description="Daftarkan hewan pertama untuk mulai mencatat produksi, kesehatan, dan pembiakan."
                              action-label="Tambah Ternak" action-url="{{ route('animals.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Ras</th>
                            <th>Kelamin</th>
                            <th>Usia</th>
                            <th>Kandang</th>
                            <th>Produksi</th>
                            <th>Breeding</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($animals as $animal)
                            <tr>
                                <td>
                                    <a href="{{ route('animals.show', $animal) }}" class="font-medium text-primary hover:underline">{{ $animal->animal_code }}</a>
                                </td>
                                <td>{{ $animal->name ?: '—' }}</td>
                                <td class="text-base-content/75">{{ $animal->breed?->name ?? '—' }}</td>
                                <td>{{ $animal->sex->shortLabel() }}</td>
                                <td class="whitespace-nowrap text-base-content/70">{{ $animal->age_label }}</td>
                                <td class="text-base-content/70">{{ $animal->barn?->name ?? '—' }}</td>
                                <td><x-badge :label="$animal->production_status->label()" :class="$animal->production_status->badgeClass()" /></td>
                                <td><x-badge :label="$animal->breeding_status->label()" :class="$animal->breeding_status->badgeClass()" /></td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <x-badge :label="$animal->status->label()" :class="$animal->status->badgeClass()" />
                                        @if ($animal->open_health_records_count > 0)
                                            <x-badge label="Perawatan" class="badge-soft badge-error" />
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('animals.show', $animal) }}" class="btn btn-ghost btn-xs">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $animals->links() }}</div>
        @endif
    </x-panel>
@endsection
