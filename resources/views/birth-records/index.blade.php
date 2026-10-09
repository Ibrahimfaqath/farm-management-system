@extends('layouts.app', ['title' => 'Kelahiran'])

@section('content')
    <x-page-header title="Kelahiran" subtitle="Menghubungkan induk betina, induk jantan, dan anak yang lahir">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('birth-records.create') }}" class="btn btn-primary btn-sm">+ Catat Kelahiran</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Kelahiran 12 Bulan" :value="$yearCount" />
        <x-stat label="Normal" :value="$births->where('birth_type.value', 'normal')->count()" tone="success" />
        <x-stat label="Butuh Pertolongan" :value="$difficultCount" :tone="$difficultCount > 0 ? 'warning' : 'success'" />
        <x-stat label="Anak Terdaftar" :value="$births->whereNotNull('animal_id')->count()" tone="primary" />
    </div>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <select name="dam_id" class="select select-sm select bg-base-100">
                <option value="">Semua induk betina</option>
                @foreach ($dams as $id => $code)
                    <option value="{{ $id }}" @selected((int) ($filters['dam_id'] ?? 0) === $id)>{{ $code }}</option>
                @endforeach
            </select>
            <select name="birth_type" class="select select-sm select bg-base-100">
                <option value="">Semua jenis persalinan</option>
                @foreach ($birthTypes as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['birth_type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
            <input name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('birth-records.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($births->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada catatan kelahiran"
                              description="Catat kelahiran untuk membangun silsilah, mengaktifkan kembali produksi induk, dan mendaftarkan anak."
                              action-label="Catat Kelahiran" action-url="{{ route('birth-records.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Induk Betina</th>
                            <th>Induk Jantan</th>
                            <th>Anak</th>
                            <th>Jenis</th>
                            <th class="text-right">Berat Lahir</th>
                            <th>Colostrum</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($births as $birth)
                            <tr>
                                <td class="whitespace-nowrap">{{ $birth->birth_date->translatedFormat('d M Y') }}</td>
                                <td><a href="{{ route('animals.show', $birth->dam) }}" class="font-medium text-primary hover:underline">{{ $birth->dam->animal_code }}</a></td>
                                <td class="text-base-content/70">{{ $birth->sire?->animal_code ?? '—' }}</td>
                                <td>
                                    @if ($birth->animal)
                                        <a href="{{ route('animals.show', $birth->animal) }}" class="font-medium text-primary hover:underline">{{ $birth->animal->animal_code }}</a>
                                    @else
                                        <span class="text-base-content/45">Tidak ada anak hidup</span>
                                    @endif
                                </td>
                                <td><x-badge :label="$birth->birth_type->label()" :class="$birth->birth_type->badgeClass()" /></td>
                                <td class="text-right tabular-nums">{{ $birth->birth_weight_kg ? $birth->birth_weight_kg.' kg' : '—' }}</td>
                                <td class="text-base-content/65">{{ $birth->colostrum_intake ?? '—' }}</td>
                                <td class="text-right"><a href="{{ route('birth-records.show', $birth) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $births->links() }}</div>
        @endif
    </x-panel>
@endsection
