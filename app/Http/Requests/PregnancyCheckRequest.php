<?php

namespace App\Http\Requests;

use App\Enums\PregnancyCheckMethod;
use App\Enums\PregnancyResult;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\BreedingRecord;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PregnancyCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('breeding.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'animal_id' => ['required', 'exists:animals,id'],
            'breeding_record_id' => ['nullable', 'exists:breeding_records,id'],
            'check_date' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', Rule::enum(PregnancyCheckMethod::class)],
            'result' => ['required', Rule::enum(PregnancyResult::class)],
            'estimated_gestation_days' => ['nullable', 'integer', 'min:1', 'max:320'],
            'expected_due_date' => ['nullable', 'date', 'after_or_equal:check_date'],
            'fetus_count' => ['nullable', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'veterinarian_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'animal_id' => 'ternak betina',
            'breeding_record_id' => 'catatan perkawinan',
            'check_date' => 'tanggal pemeriksaan',
            'expected_due_date' => 'perkiraan tanggal lahir',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $animal = Animal::query()->find($this->input('animal_id'));

            if ($animal?->sex !== Sex::Female) {
                $validator->errors()->add('animal_id', 'Pemeriksaan kehamilan hanya untuk ternak betina.');
            }

            $recordId = $this->input('breeding_record_id');

            if ($recordId) {
                $record = BreedingRecord::query()->find($recordId);

                if ($record && (int) $record->animal_id !== (int) $this->input('animal_id')) {
                    $validator->errors()->add('breeding_record_id', 'Catatan perkawinan bukan milik ternak tersebut.');
                }
            }

            $result = $this->input('result');

            if (in_array($result, [PregnancyResult::Pregnant->value, PregnancyResult::ConfirmedPregnant->value], true)
                && ! $this->filled('expected_due_date') && ! $recordId) {
                $validator->errors()->add(
                    'expected_due_date',
                    'Isi perkiraan tanggal lahir atau pilih catatan perkawinan agar tanggal dapat dihitung.',
                );
            }
        });
    }
}
