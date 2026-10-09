@extends('layouts.app', ['title' => $barn->name])

@section('content')
    <x-page-header :title="$barn->name" :subtitle="$barn->code.' · '.$barn->farm->name.' · '.$barn->type->label()"
                   :back="route('barns.index')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('animals.create') }}" class="btn btn-sm">+ Ternak</a>
                <a href="{{ route('feed-consumptions.create', ['barn_id' => $barn->id]) }}" class="btn btn-sm">+ Pemakaian Pakan</a>
                <a href="{{ route('barns.edit', $barn) }}" class="btn btn-primary btn-sm">Ubah Data</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Ternak Aktif" :value="$barn->animals_count" tone="primary" />
        <x-stat label="Kapasitas" :value="$barn->capacity ?? '—'" :hint="$barn->capacity ? 'Sisa '.max(0, $barn->capacity - $barn->animals_count).' ekor' : null" />
        <x-stat label="Susu Bulan Ini" :value="number_format((float) $milkThisMonth, 1).' L'" />
        <x-stat label="Pakan Bulan Ini" :value="number_format((float) $consumptionThisMonth, 1)" hint="Total seluruh item" />
    </div>

    <x-panel title="Hewan di Kandang Ini" padding="p-0">
        @if ($animals->isEmpty())
            <div class="p-4">
                <x-empty-state title="Kandang ini masih kosong"
                              description="Belum ada hewan aktif yang ditempatkan di kandang ini."
                              action-label="Daftarkan Ternak" action-url="{{ route('animals.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Ras</th><th>Kelamin</th><th>Usia</th><th>Produksi</th><th>Breeding</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($animals as $animal)
                            <tr>
                                <td><a href="{{ route('animals.show', $animal) }}" class="font-medium text-primary hover:underline">{{ $animal->animal_code }}</a></td>
                                <td>{{ $animal->name ?: '—' }}</td>
                                <td class="text-base-content/70">{{ $animal->breed?->name ?? '—' }}</td>
                                <td>{{ $animal->sex->shortLabel() }}</td>
                                <td class="whitespace-nowrap text-base-content/70">{{ $animal->age_label }}</td>
                                <td><x-badge :label="$animal->production_status->label()" :class="$animal->production_status->badgeClass()" /></td>
                                <td><x-badge :label="$animal->breeding_status->label()" :class="$animal->breeding_status->badgeClass()" /></td>
                                <td class="text-right"><a href="{{ route('animals.show', $animal) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $animals->links() }}</div>
        @endif
    </x-panel>
@endsection
