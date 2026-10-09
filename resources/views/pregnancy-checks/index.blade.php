@extends('layouts.app', ['title' => 'Pemeriksaan Kehamilan'])

@section('content')
    <x-page-header title="Pemeriksaan Kehamilan" subtitle="Hasil pemeriksaan yang menentukan status hamil dan perkiraan lahir">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('birth-records.index') }}" class="btn btn-sm">Kelahiran</a>
                <a href="{{ route('pregnancy-checks.create') }}" class="btn btn-primary btn-sm">+ Catat Pemeriksaan</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Ternak Hamil" :value="$pregnantCount" tone="primary" />
        <x-stat label="Perkiraan Lahir 21 Hari" :value="$dueSoonCount" :tone="$dueSoonCount > 0 ? 'warning' : 'success'" />
        <x-stat label="Total Pemeriksaan" :value="$checks->total()" />
        <x-stat label="Ternak diperiksa" :value="$checks->pluck('animal_id')->unique()->count()" />
    </div>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
            <select name="animal_id" class="select select-sm select bg-base-100">
                <option value="">Semua betina</option>
                @foreach ($animals as $id => $code)
                    <option value="{{ $id }}" @selected((int) ($filters['animal_id'] ?? 0) === $id)>{{ $code }}</option>
                @endforeach
            </select>
            <select name="result" class="select select-sm select bg-base-100">
                <option value="">Semua hasil</option>
                @foreach ($results as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['result'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="due_soon" value="1" @checked($filters['due_soon'] ?? false) class="checkbox checkbox-sm checkbox-primary">
                Perkiraan lahir 21 hari
            </label>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('pregnancy-checks.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($checks->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada pemeriksaan kehamilan"
                              description="Pemeriksaan kehamilan memastikan status hamil dan menghitung perkiraan tanggal lahir."
                              action-label="Catat Pemeriksaan" action-url="{{ route('pregnancy-checks.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Betina</th>
                            <th>Tanggal</th>
                            <th>Metode</th>
                            <th>Hasil</th>
                            <th>Usia Kehamilan</th>
                            <th>Perkiraan Lahir</th>
                            <th>Janin</th>
                            <th>Dokter</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($checks as $check)
                            <tr>
                                <td><a href="{{ route('animals.show', $check->animal) }}" class="font-medium text-primary hover:underline">{{ $check->animal->animal_code }}</a></td>
                                <td class="whitespace-nowrap">{{ $check->check_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $check->method->label() }}</td>
                                <td><x-badge :label="$check->result->label()" :class="$check->result->badgeClass()" /></td>
                                <td class="tabular-nums">{{ $check->estimated_gestation_days ? $check->estimated_gestation_days.' hari' : '—' }}</td>
                                <td class="whitespace-nowrap">{{ $check->expectedDueDate()?->translatedFormat('d M Y') ?? '—' }}</td>
                                <td class="tabular-nums">{{ $check->fetus_count ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $check->veterinarian?->name ?? '—' }}</td>
                                <td class="text-right">
                                    @if ($check->result->isPregnant() && auth()->user()->hasAbility('breeding.manage'))
                                        <a href="{{ route('birth-records.create', ['pregnancy_check_id' => $check->id]) }}" class="btn btn-ghost btn-xs">Catat Lahir</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $checks->links() }}</div>
        @endif
    </x-panel>
@endsection
