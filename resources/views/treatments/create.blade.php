@extends('layouts.app', ['title' => 'Catat Pengobatan'])

@section('content')
    <x-page-header title="Catat Pengobatan"
                   subtitle="Masa tunggu menentukan kapan susu boleh kembali dikonsumsi."
                   :back="route('treatments.index')" />

    <form method="POST" action="{{ route('treatments.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Data Pengobatan">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="animal_id" label="Ternak" :options="$animals"
                               :selected="old('animal_id', $treatment->animal_id ?? $animal?->id)" required class="sm:col-span-2" />
                <x-form.select name="health_record_id" label="Rekam Kesehatan (opsional)"
                               :options="$healthRecords->mapWithKeys(fn ($r) => [$r->id => $r->record_date->translatedFormat('d M Y').' · '.($r->diagnosis ?? $r->symptoms)])"
                               placeholder="— Tanpa rekam —" />
                <x-form.input name="medication_name" label="Nama Obat" :value="$treatment->medication_name" required
                              placeholder="Contoh: Oxytetracycline 20%" list="med-names" />
                <datalist id="med-names">
                    <option value="Oxytetracycline 20%"></option>
                    <option value="Penicillin Prokain"></option>
                    <option value="Ivermectin"></option>
                    <option value="Ketoprofen"></option>
                    <option value="Vitamin B Complex"></option>
                </datalist>
                <x-form.input name="dosage" label="Dosis" :value="$treatment->dosage" placeholder="Contoh: 20 ml IM" />
                <x-form.input name="route" label="Jalur" :value="$treatment->route" placeholder="IM / SC / Oral / Topikal" />
                <x-form.input name="frequency" label="Frekuensi" :value="$treatment->frequency" placeholder="Contoh: 2x sehari" />
            </div>
        </x-panel>

        <x-panel title="Periode dan Biaya">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.input name="treatment_date" label="Tanggal Mulai" type="date"
                              :value="$treatment->treatment_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.input name="end_date" label="Tanggal Selesai" type="date" :value="$treatment->end_date?->toDateString()" />
                <x-form.input name="withdrawal_period_days" label="Masa Tunggu (hari)" type="number" min="0"
                              :value="$treatment->withdrawal_period_days" hint="Susu ditahan selama periode ini." />
                <x-form.money name="cost" label="Biaya" :value="$treatment->cost ?? 0" />
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-form.select name="veterinarian_id" label="Dokter / Petugas" :options="$veterinarians" :selected="$treatment->veterinarian_id" />
                <x-form.textarea name="notes" label="Catatan" :value="$treatment->notes" rows="2" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Pengobatan</button>
                <a href="{{ route('treatments.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
