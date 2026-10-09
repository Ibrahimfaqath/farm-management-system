@php
    $lactating = $barn
        ? \App\Models\Animal::query()->active()->lactating()->where('barn_id', $barn->getKey())
            ->with('breed')->orderBy('animal_code')->get()
        : \App\Models\Animal::query()->active()->lactating()->with('breed')->orderBy('animal_code')->limit(60)->get();

    $existing = $barn
        ? \App\Models\MilkProduction::query()
            ->where('barn_id', $barn->getKey())
            ->whereDate('production_date', now()->toDateString())
            ->pluck('liters', 'animal_id')
        : collect();
@endphp
@extends('layouts.app', ['title' => 'Input Produksi per Kandang'])

@section('content')
    <x-page-header title="Input Produksi per Kandang"
                   subtitle="Cukup isi baris untuk hewan yang benar-benar diperah hari ini."
                   :back="route('milk-productions.index')" />

    <form method="POST" action="{{ route('milk-productions.batch.store') }}" class="space-y-4">
        @csrf

        <x-panel title="Konteks">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="farm_id" label="Peternakan" :options="$farms"
                               :selected="old('farm_id', $barn?->farm_id ?? $farms->keys()->first())" required />
                <x-form.select name="barn_id" label="Kandang" :options="$barns" :selected="old('barn_id', $barn?->id)"
                               placeholder="— Semua kandang —" />
                <x-form.input name="production_date" label="Tanggal" type="date" :value="now()->toDateString()" required />
                <x-form.select name="default_session" label="Sesi Baku" :options="$sessions" :selected="old('default_session', 'mixed')" required />
            </div>
            @error('lines')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel title="Hasil Pemerahan" padding="p-0">
            @if ($lactating->isEmpty())
                <div class="p-4">
                    <x-empty-state title="Belum ada hewan laktasi"
                                  description="Pastikan status produksi hewan diset Laktasi sebelum mencatat produksi." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Hewan</th>
                                <th>Ras</th>
                                <th class="w-32">Liter</th>
                                <th class="w-40">Sesi</th>
                                <th class="w-32">Lemak %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lactating as $index => $animal)
                                <tr>
                                    <td>
                                        <input type="hidden" name="lines[{{ $index }}][animal_id]" value="{{ $animal->id }}">
                                        <span class="font-medium">{{ $animal->animal_code }}</span>
                                        @if ($animal->name)
                                            <span class="text-[11px] text-base-content/45">{{ $animal->name }}</span>
                                        @endif
                                        @if ($existing->has($animal->id))
                                            <span class="block text-[11px] text-base-content/45">
                                                Tercatat {{ number_format((float) $existing[$animal->id], 1) }} L
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-base-content/70">{{ $animal->breed?->name ?? '—' }}</td>
                                    <td><input name="lines[{{ $index }}][liters]" type="number" step="0.1" min="0" max="200"
                                               class="input input-sm input w-full bg-base-100"></td>
                                    <td>
                                        <select name="lines[{{ $index }}][session]" class="select select-sm select w-full bg-base-100">
                                            <option value="">Ikuti sesi baku</option>
                                            @foreach ($sessions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input name="lines[{{ $index }}][fat_percent]" type="number" step="0.01" min="0" max="10"
                                               class="input input-sm input w-full bg-base-100"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Produksi</button>
                <a href="{{ route('milk-productions.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection