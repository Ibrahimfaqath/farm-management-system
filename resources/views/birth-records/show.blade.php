@extends('layouts.app', ['title' => 'Kelahiran '.$birth->dam->animal_code])

@section('content')
    <x-page-header :title="'Kelahiran · '.$birth->dam->animal_code"
                   :subtitle="$birth->birth_date->translatedFormat('d M Y').' · '.$birth->birth_type->label()"
                   :back="route('birth-records.index')" />

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Induk Betina" :value="$birth->dam->animal_code" />
        <x-stat label="Induk Jantan" :value="$birth->sire?->animal_code ?? '—'" />
        <x-stat label="Anak" :value="$birth->animal?->animal_code ?? 'Tidak ada'" :tone="$birth->animal ? 'success' : 'warning'" />
        <x-stat label="Berat Lahir" :value="$birth->birth_weight_kg ? $birth->birth_weight_kg.' kg' : '—'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Detail Kelahiran">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-base-content/55">Tanggal</dt><dd>{{ $birth->birth_date->translatedFormat('d M Y') }}</dd></div>
                    <div><dt class="text-base-content/55">Waktu</dt><dd>{{ $birth->birth_time ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/55">Jenis Persalinan</dt><dd>{{ $birth->birth_type->label() }}</dd></div>
                    <div><dt class="text-base-content/55">Kolostrum</dt><dd>{{ $birth->colostrum_intake ?? '—' }}</dd></div>
                </dl>
                @if ($birth->assistance_notes)
                    <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed">{{ $birth->assistance_notes }}</p>
                @endif
            </x-panel>

            <x-panel title="Silsilah" padding="p-0">
                <div class="grid gap-4 p-4 sm:grid-cols-3">
                    <div class="rounded-md border border-base-300 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-base-content/45">Induk Betina</p>
                        <a href="{{ route('animals.show', $birth->dam) }}" class="text-sm font-medium text-primary hover:underline">{{ $birth->dam->animal_code }}</a>
                    </div>
                    <div class="rounded-md border border-base-300 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-base-content/45">Induk Jantan</p>
                        @if ($birth->sire)
                            <a href="{{ route('animals.show', $birth->sire) }}" class="text-sm font-medium text-primary hover:underline">{{ $birth->sire->animal_code }}</a>
                        @else
                            <p class="text-sm text-base-content/45">Tidak diketahui</p>
                        @endif
                    </div>
                    <div class="rounded-md border border-base-300 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-base-content/45">Anak</p>
                        @if ($birth->animal)
                            <a href="{{ route('animals.show', $birth->animal) }}" class="text-sm font-medium text-primary hover:underline">{{ $birth->animal->animal_code }}</a>
                        @else
                            <p class="text-sm text-base-content/45">Tidak ada anak hidup</p>
                        @endif
                    </div>
                </div>
            </x-panel>
        </div>

        <x-panel title="Dampak terhadap Induk">
            <p class="text-xs leading-relaxed text-base-content/65">
                Setelah kelahiran, status breeding induk dikembalikan ke terbuka dan status produksi menjadi laktasi
                bila sebelumnya kering.
            </p>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">Breeding</dt>
                    <dd><x-badge :label="$birth->dam->breeding_status->label()" :class="$birth->dam->breeding_status->badgeClass()" /></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">Produksi</dt>
                    <dd><x-badge :label="$birth->dam->production_status->label()" :class="$birth->dam->production_status->badgeClass()" /></dd>
                </div>
            </dl>
        </x-panel>
    </div>
@endsection
