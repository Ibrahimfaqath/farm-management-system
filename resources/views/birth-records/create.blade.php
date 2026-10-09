@extends('layouts.app', ['title' => 'Catat Kelahiran'])

@section('content')
    <x-page-header title="Catat Kelahiran"
                   subtitle="Anak yang lahir dapat langsung didaftarkan sebagai hewan dengan silsilah terhubung."
                   :back="route('birth-records.index')" />

    <form method="POST" action="{{ route('birth-records.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Data Kelahiran">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="dam_id" label="Induk Betina" :options="$dams"
                               :selected="old('dam_id', $birth->dam_id ?? $dam?->id)" required class="sm:col-span-2" />
                <x-form.select name="sire_id" label="Induk Jantan" :options="$sires" :selected="$birth->sire_id"
                               placeholder="— Tidak diketahui —" />
                <x-form.select name="pregnancy_check_id" label="Pemeriksaan Kehamilan" :options="$pregnancyChecks"
                               :selected="old('pregnancy_check_id', $pregnancyCheck?->id)" placeholder="— Tanpa pemeriksaan —" />
                <x-form.input name="birth_date" label="Tanggal Lahir" type="date"
                              :value="$birth->birth_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.input name="birth_time" label="Waktu Lahir" type="time" />
                <x-form.select name="birth_type" label="Jenis Persalinan" :options="$birthTypes" :selected="$birth->birth_type?->value" required />
                <x-form.input name="birth_weight_kg" label="Berat Lahir (kg)" type="number" step="0.1" min="1" :value="$birth->birth_weight_kg" />
                <x-form.select name="calf_sex" label="Jenis Kelamin Anak" :options="$sexOptions"
                               :selected="old('calf_sex', $birth->calf_sex === 0 ? 'female' : 'male')" />
                <x-form.input name="colostrum_intake" label="Pemberian Kolostrum" :value="$birth->colostrum_intake"
                              placeholder="Contoh: 2 jam pertama, 10% berat badan" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="assistance_notes" label="Catatan Pertolongan" :value="$birth->assistance_notes" rows="2" />
            </div>
        </x-panel>

        <x-panel title="Pendaftaran Anak" description="Bila dicentang, sistem membuat data hewan anak dan menghubungkan silsilah ke induk.">
            <x-form.checkbox name="register_calf" value="1" label="Daftarkan anak sebagai hewan baru" />
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <x-form.input name="calf_code" label="Kode Hewan Anak" placeholder="Contoh: T-00031" />
                <x-form.input name="calf_name" label="Nama Anak (opsional)" />
            </div>
            @error('calf_code')<p class="text-xs text-error">{{ $message }}</p>@enderror
            @error('calf_sex')<p class="text-xs text-error">{{ $message }}</p>@enderror
            @error('register_calf')<p class="text-xs text-error">{{ $message }}</p>@enderror
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Kelahiran</button>
                <a href="{{ route('birth-records.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
