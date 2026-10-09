@extends('layouts.app', ['title' => $farm->name])

@section('content')
    <x-page-header :title="$farm->name" :subtitle="$farm->code.' · '.(collect([$farm->address, $farm->village, $farm->district, $farm->regency, $farm->province])->filter()->implode(', ') ?: 'Alamat belum lengkap')"
                   back="{{ route('farms.index') }}">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('barns.create', ['farm_id' => $farm->id]) }}" class="btn btn-sm">+ Kandang</a>
                <a href="{{ route('animals.create') }}" class="btn btn-sm">+ Ternak</a>
            @endif
            @if (auth()->user()->hasAbility('settings.manage'))
                <a href="{{ route('farms.edit', $farm) }}" class="btn btn-primary btn-sm">Ubah Data</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Ternak Aktif" :value="$farm->active_animals_count" tone="primary" />
        <x-stat label="Total Ternak" :value="$farm->animals_count" hint="Termasuk yang sudah keluar" />
        <x-stat label="Kandang" :value="$farm->barns->count()" />
        <x-stat label="Luas Lahan" :value="$farm->area_hectares ? $farm->area_hectares.' ha' : '—'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-panel title="Kandang" padding="p-0">
                @if ($farm->barns->isEmpty())
                    <div class="p-4">
                        <x-empty-state title="Belum ada kandang"
                                      description="Tambahkan kandang untuk menempatkan hewan dan mencatat produksi."
                                      action-label="Tambah Kandang" action-url="{{ route('barns.create', ['farm_id' => $farm->id]) }}" />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm table-zebra">
                            <thead><tr><th>Kode</th><th>Nama</th><th>Jenis</th><th class="text-right">Isi</th><th class="text-right">Kapasitas</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($farm->barns as $barn)
                                    <tr>
                                        <td class="font-mono text-xs">{{ $barn->code }}</td>
                                        <td><a href="{{ route('barns.show', $barn) }}" class="font-medium text-primary hover:underline">{{ $barn->name }}</a></td>
                                        <td>{{ $barn->type->label() }}</td>
                                        <td class="text-right tabular-nums">{{ $barn->animals_count }}</td>
                                        <td class="text-right tabular-nums">{{ $barn->capacity ?? '—' }}</td>
                                        <td>
                                            <x-badge :label="$barn->is_active ? 'Aktif' : 'Nonaktif'"
                                                     :class="$barn->is_active ? 'badge-soft badge-success' : 'badge-ghost'" />
                                        </td>
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
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Pengelola</dt><dd>{{ $farm->manager_name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Telepon</dt><dd>{{ $farm->phone ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Berdiri sejak</dt><dd>{{ $farm->established_date?->translatedFormat('M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Kode pos</dt><dd>{{ $farm->postal_code ?? '—' }}</dd></div>
                </dl>
                @if ($farm->notes)
                    <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $farm->notes }}</p>
                @endif
            </x-panel>
        </div>
    </div>
@endsection
