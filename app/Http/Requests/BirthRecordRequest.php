<?php

namespace App\Http\Requests;

use App\Enums\BirthType;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\PregnancyCheck;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BirthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('breeding.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'dam_id' => ['required', 'exists:animals,id'],
            'sire_id' => ['nullable', 'exists:animals,id', 'different:dam_id'],
            'pregnancy_check_id' => ['nullable', 'exists:pregnancy_checks,id'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'birth_time' => ['nullable', 'date_format:H:i'],
            'birth_type' => ['required', Rule::enum(BirthType::class)],
            'birth_weight_kg' => ['nullable', 'numeric', 'min:1', 'max:120'],
            'colostrum_intake' => ['nullable', 'string', 'max:100'],
            'calf_sex' => ['nullable', Rule::enum(Sex::class)],
            'assistance_notes' => ['nullable', 'string', 'max:2000'],

            'register_calf' => ['boolean'],
            'calf_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'dam_id' => 'induk betina',
            'sire_id' => 'induk jantan',
            'birth_date' => 'tanggal lahir',
            'birth_weight_kg' => 'berat lahir',
            'calf_sex' => 'jenis kelamin anak',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $dam = Animal::query()->find($this->input('dam_id'));

            if ($dam?->sex !== Sex::Female) {
                $validator->errors()->add('dam_id', 'Induk yang dipilih harus berjenis kelamin betina.');
            }

            $checkId = $this->input('pregnancy_check_id');

            if ($checkId) {
                $check = PregnancyCheck::query()->find($checkId);

                if ($check && (int) $check->animal_id !== (int) $this->input('dam_id')) {
                    $validator->errors()->add('pregnancy_check_id', 'Pemeriksaan kehamilan bukan milik induk betina ini.');
                }
            }

            if ($this->input('birth_type') === BirthType::Stillborn->value && $this->boolean('register_calf')) {
                $validator->errors()->add('register_calf', 'Anak lahir mati tidak dapat didaftarkan sebagai hewan.');
            }

            if ($this->boolean('register_calf') && ! $this->filled('calf_sex')) {
                $validator->errors()->add('calf_sex', 'Jenis kelamin anak wajib diisi saat mendaftarkan anak.');
            }
        });
    }
}
