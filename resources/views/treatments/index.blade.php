@extends('layouts.app', ['title' => 'Pengobatan'])

@section('content')
    <x-page-header title="Pengobatan" subtitle="Obat, dosis, dan masa tunggu sebelum susu dapat dikonsumsi">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('treatments.create') }}" class="btn btn-primary btn-sm">+ Catat Pengobatan</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Biaya Bulan Ini" :value="'Rp'.number_format($monthCost, 0, ',', '.')" />
        <x-stat label="Masa Tunggu Aktif" :value="$withdrawalActive" :tone="$withdrawalActive > 0 ? 'warning' : 'success'"
               hint="susu belum boleh dikonsumsi" />
        <x-stat label="Total Catatan" :value="$treatments->total()" />
        <x-stat label="Jenis Obat" :value="$treatments->pluck('medication_name')->unique()->count()" />
    </div>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
            <select name="animal_id" class="select select-sm select bg-base-100">
                <option value="">Semua ternak</option>
                @foreach ($animals as $id => $code)
                    <option value="{{ $id }}" @selected((int) ($filters['animal_id'] ?? 0) === $id)>{{ $code }}</option>
                @endforeach
            </select>
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama obat…" class="input input-sm input bg-base-100">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="active_withdrawal" value="1" @checked($filters['active_withdrawal'] ?? false) class="checkbox checkbox-sm checkbox-primary">
                Memiliki masa tunggu
            </label>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('treatments.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($treatments->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada catatan pengobatan"
                              description="Catat obat yang diberikan beserta dosis dan masa tunggu agar riwayat dapat ditelusuri."
                              action-label="Catat Pengobatan" action-url="{{ route('treatments.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Ternak</th>
                            <th>Obat</th>
                            <th>Dosis</th>
                            <th>Periode</th>
                            <th>Masa Tunggu</th>
                            <th>Dokter</th>
                            <th class="text-right">Biaya</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($treatments as $treatment)
                            <tr>
                                <td><a href="{{ route('animals.show', $treatment->animal) }}" class="font-medium text-primary hover:underline">{{ $treatment->animal->animal_code }}</a></td>
                                <td class="font-medium">{{ $treatment->medication_name }}</td>
                                <td class="text-base-content/70">{{ $treatment->dosage ?? '—' }}</td>
                                <td class="whitespace-nowrap">
                                    {{ $treatment->treatment_date->translatedFormat('d M Y') }}
                                    @if ($treatment->end_date)
                                        <span class="text-base-content/50">– {{ $treatment->end_date->translatedFormat('d M Y') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($treatment->withdrawal_period_days)
                                        {{ $treatment->withdrawal_period_days }} hari
                                        @if ($treatment->isWithdrawalActive())
                                            <x-badge label="Aktif s/d {{ $treatment->withdrawalEndsAt()->translatedFormat('d M') }}" class="badge-soft badge-warning" />
                                        @endif
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td class="text-base-content/65">{{ $treatment->veterinarian?->name ?? '—' }}</td>
                                <td class="text-right"><x-money :amount="$treatment->cost ?? 0" /></td>
                                <td class="text-right">
                                    @if ($treatment->healthRecord)
                                        <a href="{{ route('health-records.show', $treatment->healthRecord) }}" class="btn btn-ghost btn-xs">Rekam</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $treatments->links() }}</div>
        @endif
    </x-panel>
@endsection
