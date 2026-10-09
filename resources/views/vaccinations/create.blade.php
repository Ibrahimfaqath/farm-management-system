@php $editing = $vaccination->exists; @endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Vaksinasi' : 'Catat Vaksinasi'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah Catatan Vaksinasi' : 'Catat Vaksinasi'"
                   :back="route('vaccinations.index')" />

    <form method="POST" action="{{ route('vaccinations.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Data Vaksinasi">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="animal_id" label="Ternak" :options="$animals"
                               :selected="old('animal_id', $vaccination->animal_id ?? $animal?->id)" required class="sm:col-span-2" />
                <x-form.input name="vaccine_name" label="Nama Vaksin" :value="$vaccination->vaccine_name" required
                              placeholder="Contoh: Brucellosis" list="vaccine-names" />
                <datalist id="vaccine-names">
                    <option value="Brucellosis"></option>
                    <option value="FMD"></option>
                    <option value="Haemorrhagic Septicaemia"></option>
                    <option value="Anthrax"></option>
                    <option value="BVD"></option>
                    <option value="Newcastle (AI)"></option>
                </datalist>
                <x-form.input name="batch_number" label="Nomor Batch" :value="$vaccination->batch_number" />
                <x-form.input name="administered_date" label="Tanggal Diberikan" type="date"
                              :value="$vaccination->administered_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.input name="next_due_date" label="Jatuh Tempo Berikutnya" type="date" :value="$vaccination->next_due_date?->toDateString()" />
                <x-form.input name="dose" label="Dosis" :value="$vaccination->dose" placeholder="Contoh: 2 ml IM" />
                <x-form.input name="route" label="Jalur" :value="$vaccination->route" placeholder="IM / SC / Oral" />
                <x-form.money name="cost" label="Biaya" :value="$vaccination->cost ?? 0" />
                <x-form.select name="veterinarian_id" label="Dokter / Petugas" :options="$veterinarians" :selected="$vaccination->veterinarian_id" />
                <x-form.input name="administered_by" label="Nama Pemberi" :value="$vaccination->administered_by" />
            </div>
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan" :value="$vaccination->notes" rows="2" />
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Vaksinasi</button>
                <a href="{{ route('vaccinations.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
