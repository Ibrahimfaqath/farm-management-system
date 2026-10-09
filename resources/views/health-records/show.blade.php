@extends('layouts.app', ['title' => 'Rekam Kesehatan '.$record->animal->animal_code])

@section('content')
    <x-page-header :title="'Rekam Kesehatan · '.$record->animal->animal_code"
                   :subtitle="$record->record_date->translatedFormat('d M Y').' · '.$record->record_type->label()"
                   :back="route('health-records.index')">
        <x-slot:actions>
            @if ($record->status->isOpen() && auth()->user()->hasAbility('health.manage'))
                <button class="btn btn-outline btn-sm btn-success" onclick="document.getElementById('resolve-modal').showModal()">Tandai Selesai</button>
            @endif
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('health-records.edit', $record) }}" class="btn btn-primary btn-sm">Ubah</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Status" :value="$record->status->label()" :tone="$record->status->isOpen() ? 'warning' : 'success'" />
        <x-stat label="Tingkat" :value="$record->severity?->label() ?? '—'" />
        <x-stat label="Biaya" :value="'Rp'.number_format((float) $record->cost, 0, ',', '.')" />
        <x-stat label="Pengobatan" :value="$record->treatments->count()" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Detail Rekam">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-base-content/45">Gejala / Observasi</dt>
                        <dd class="mt-0.5 whitespace-pre-line">{{ $record->symptoms ?? '—' }}</dd>
                    </div>
                    <div class="rounded-md border border-base-300 p-3">
                        <dt class="text-xs uppercase tracking-wide text-base-content/45">Diagnosis</dt>
                        <dd class="mt-0.5 whitespace-pre-line">{{ $record->diagnosis ?? 'Belum ada diagnosis. Observasi saja belum cukup untuk menyimpulkan penyakit.' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-base-content/45">Catatan</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-base-content/75">{{ $record->notes ?? '—' }}</dd>
                    </div>
                </dl>
            </x-panel>

            <x-panel title="Pengobatannya" padding="p-0">
                @if ($record->treatments->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Belum ada pengobatan yang terkait dengan rekam ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Obat</th><th>Dosis</th><th>Periode</th><th>Masa Tunggu</th><th class="text-right">Biaya</th></tr></thead>
                            <tbody>
                                @foreach ($record->treatments as $treatment)
                                    <tr>
                                        <td class="font-medium">{{ $treatment->medication_name }}</td>
                                        <td class="text-base-content/70">{{ $treatment->dosage ?? '—' }}</td>
                                        <td class="whitespace-nowrap">{{ $treatment->treatment_date->translatedFormat('d M') }} – {{ $treatment->end_date?->translatedFormat('d M Y') ?? 'berjalan' }}</td>
                                        <td>
                                            {{ $treatment->withdrawal_period_days ? $treatment->withdrawal_period_days.' hari' : '—' }}
                                            @if ($treatment->isWithdrawalActive())
                                                <x-badge label="Aktif" class="badge-soft badge-warning" />
                                            @endif
                                        </td>
                                        <td class="text-right"><x-money :amount="$treatment->cost ?? 0" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel title="Informasi">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Ternak</dt>
                        <dd><a href="{{ route('animals.show', $record->animal) }}" class="text-primary hover:underline">{{ $record->animal->animal_code }}</a></dd>
                    </div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Ras</dt><dd>{{ $record->animal->breed?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Jenis</dt><dd>{{ $record->record_type->label() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Dokter</dt><dd>{{ $record->veterinarian?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Tindak Lanjut</dt><dd>{{ $record->follow_up_date?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Selesai</dt><dd>{{ $record->resolved_date?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                </dl>
            </x-panel>

            <x-panel title="Riwayat Kesehatan Ternak Ini" padding="p-0">
                @php $others = $record->animal->healthRecords()->whereKeyNot($record->id)->orderByDesc('record_date')->limit(6)->get(); @endphp
                @if ($others->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Tidak ada rekam lain.</p>
                @else
                    <ul class="divide-y divide-base-300">
                        @foreach ($others as $other)
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <a href="{{ route('health-records.show', $other) }}" class="text-xs hover:underline">
                                    {{ $other->record_date->translatedFormat('d M Y') }} · {{ $other->record_type->label() }}
                                </a>
                                <x-badge :label="$other->status->label()" :class="$other->status->badgeClass()" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>
        </div>
    </div>

    @if ($record->status->isOpen() && auth()->user()->hasAbility('health.manage'))
        <dialog id="resolve-modal" class="modal">
            <div class="modal-box max-w-lg">
                <h3 class="text-base font-semibold">Tandai Rekam Selesai</h3>
                <p class="mt-1 text-xs text-base-content/60">Riwayat tetap tersimpan sebagai bukti penanganan.</p>
                <form method="POST" action="{{ route('health-records.resolve', $record) }}" class="mt-4 space-y-3">
                    @csrf
                    <x-form.input name="resolution" label="Hasil Penanganan" required minlength="3"
                                  placeholder="Contoh: gejala mereda, nafsu makan kembali normal" />
                    <x-form.money name="cost" label="Biaya Akhir" :value="$record->cost ?? 0" />
                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('resolve-modal').close()">Batal</button>
                        <button class="btn btn-success btn-sm">Tandai Selesai</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>Tutup</button></form>
        </dialog>
    @endif
@endsection
