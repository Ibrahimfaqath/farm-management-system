@php
    $tabs = [
        'riwayat' => 'Riwayat',
        'kesehatan' => 'Kesehatan',
        'breeding' => 'Pembiakan',
        'produksi' => 'Produksi',
    ];
    $current = request()->query('tab', 'riwayat');
@endphp
@extends('layouts.app', ['title' => $animal->animal_code])

@section('content')
    <x-page-header :title="$animal->animal_code.($animal->name ? ' · '.$animal->name : '')"
                   :subtitle="$animal->breed?->name.' · '.$animal->sex->label().' · '.$animal->age_label"
                   :back="route('animals.index')">
        <x-slot:actions>
            @if ($animal->status->isOpen() && auth()->user()->hasAbility('animal.exit'))
                <button class="btn btn-outline btn-sm btn-error" onclick="document.getElementById('exit-modal').showModal()">
                    Keluarkan Ternak
                </button>
            @endif
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('animals.edit', $animal) }}" class="btn btn-primary btn-sm">Ubah Data</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @unless ($animal->status->isOpen())
        <x-alert-row tone="warning" class="mb-4"
                     title="Ternak ini berstatus {{ $animal->status->label() }}"
                     description="Data historis tetap tersedia sebagai bukti riwayat. Keluar pada {{ $animal->exit_date?->translatedFormat('d M Y') }}." />
    @endunless

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat label="Status" :value="$animal->status->label()" :tone="$animal->status->isOpen() ? 'success' : 'warning'" />
        <x-stat label="Produksi" :value="$animal->production_status->label()" />
        <x-stat label="Breeding" :value="$animal->breeding_status->label()" />
        <x-stat label="Kandang" :value="$animal->barn?->name ?? '—'" />
        <x-stat label="Susu 30 hari" :value="($milkSummary->total ?? 0) > 0 ? number_format((float) $milkSummary->total, 1).' L' : '—'"
               :hint="($milkSummary->records ?? 0) > 0 ? 'Rata-rata '.number_format((float) $milkSummary->average, 1).' L/record' : 'Belum ada catatan'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div role="tablist" class="tabs tabs-box tabs-sm w-fit bg-base-100">
                @foreach ($tabs as $key => $label)
                    <a role="tab" href="?tab={{ $key }}"
                       class="tab @selected($current === $key)">{{ $label }}</a>
                @endforeach
            </div>

            @if ($current === 'riwayat')
                @include('animals.tabs.history')
            @elseif ($current === 'kesehatan')
                @include('animals.tabs.health')
            @elseif ($current === 'breeding')
                @include('animals.tabs.breeding')
            @else
                @include('animals.tabs.production')
            @endif
        </div>

        <div class="space-y-4">
            <x-panel title="Identitas">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Ras</dt><dd class="text-right">{{ $animal->breed?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Peternakan</dt><dd class="text-right">{{ $animal->farm?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Tanggal lahir</dt><dd class="text-right">{{ $animal->date_of_birth?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Perolehan</dt><dd class="text-right">{{ $animal->acquisition_type?->label() ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Nilai perolehan</dt><dd class="text-right"><x-money :amount="$animal->acquisition_cost ?? 0" /></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Kesehatan</dt><dd class="text-right">{{ $animal->healthStatusLabel() }}</dd></div>
                    @if ($animal->exit_date)
                        <div class="flex justify-between gap-3"><dt class="text-base-content/55">Tanggal keluar</dt><dd class="text-right">{{ $animal->exit_date->translatedFormat('d M Y') }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-base-content/55">Nilai keluar</dt><dd class="text-right"><x-money :amount="$animal->exit_value ?? 0" /></dd></div>
                    @endif
                </dl>
                @if ($animal->notes)
                    <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $animal->notes }}</p>
                @endif
                @if ($animal->exit_notes)
                    <p class="mt-2 alert alert-soft alert-warning px-3 py-2.5 text-xs leading-relaxed">{{ $animal->exit_notes }}</p>
                @endif
            </x-panel>

            <x-panel title="Silsilah">
                <div class="grid gap-3">
                    <div class="rounded-md border border-base-300 p-2.5">
                        <p class="text-[11px] uppercase tracking-wide text-base-content/45">Induk betina</p>
                        @if ($animal->dam)
                            <a href="{{ route('animals.show', $animal->dam) }}" class="text-sm font-medium text-primary hover:underline">{{ $animal->dam->animal_code }}</a>
                            <p class="text-xs text-base-content/55">{{ $animal->dam->name ?? 'Tanpa nama' }}</p>
                        @else
                            <p class="text-sm text-base-content/45">Tidak diketahui</p>
                        @endif
                    </div>
                    <div class="rounded-md border border-base-300 p-2.5">
                        <p class="text-[11px] uppercase tracking-wide text-base-content/45">Induk jantan</p>
                        @if ($animal->sire)
                            <a href="{{ route('animals.show', $animal->sire) }}" class="text-sm font-medium text-primary hover:underline">{{ $animal->sire->animal_code }}</a>
                            <p class="text-xs text-base-content/55">{{ $animal->sire->name ?? 'Tanpa nama' }}</p>
                        @else
                            <p class="text-sm text-base-content/45">Tidak diketahui</p>
                        @endif
                    </div>
                    @if ($animal->calves->isNotEmpty())
                        <div class="rounded-md border border-base-300 p-2.5">
                            <p class="text-[11px] uppercase tracking-wide text-base-content/45">Anak ({{ $animal->calves->count() }})</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @foreach ($animal->calves as $calf)
                                    <a href="{{ route('animals.show', $calf) }}"
                                       class="badge badge-sm border border-base-300 bg-base-100 hover:border-primary">{{ $calf->animal_code }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-panel>
        </div>
    </div>

    @if ($animal->status->isOpen() && auth()->user()->hasAbility('animal.exit'))
        <dialog id="exit-modal" class="modal">
            <div class="modal-box max-w-lg">
                <h3 class="text-base font-semibold">Keluarkan {{ $animal->animal_code }}</h3>
                <p class="mt-1 text-xs text-base-content/60">
                    Hewan tidak akan dihapus. Riwayat kesehatan, pembiakan, produksi, dan keuangan tetap dapat diakses.
                </p>
                <form method="POST" action="{{ route('animals.exit', $animal) }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-form.select name="exit_type" label="Jenis Keluar" required
                                       :options="['sold' => 'Terjual', 'deceased' => 'Mati', 'transferred' => 'Dipindahkan']" />
                        <x-form.input name="exit_date" label="Tanggal" type="date" required :value="now()->toDateString()" />
                        <x-form.money name="exit_value" label="Nilai Transaksi" :value="0" />
                        <x-form.input name="exit_reference" label="Nomor Referensi" placeholder="No. kuitansi / nota" />
                    </div>
                    <x-form.input name="reason" label="Alasan" required
                                  placeholder="Contoh: dijual ke CV Sumber Susu" />
                    <x-form.textarea name="exit_notes" label="Catatan Tambahan" rows="2" />
                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('exit-modal').close()">Batal</button>
                        <button class="btn btn-error btn-sm">Konfirmasi Keluar</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>Tutup</button></form>
        </dialog>
    @endif
@endsection
