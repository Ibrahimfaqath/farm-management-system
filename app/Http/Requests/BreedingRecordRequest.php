<?php

namespace App\Http\Requests;

use App\Enums\BreedingRecordType;
use App\Enums\Sex;
use App\Models\Animal;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BreedingRecordRequest extends FormRequest
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
            'record_type' => ['required', Rule::enum(BreedingRecordType::class)],
            'breeding_date' => ['required', 'date', 'before_or_equal:today'],
            'bull_id' => ['nullable', 'exists:animals,id'],
            'semen_code' => ['nullable', 'string', 'max:60'],
            'semen_batch' => ['nullable', 'string', 'max:60'],
            'inseminator' => ['nullable', 'string', 'max:150'],
            'heat_signs' => ['nullable', 'string', 'max:1000'],
            'expected_next_heat_date' => ['nullable', 'date', 'after:breeding_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'animal_id' => 'ternak betina',
            'record_type' => 'jenis catatan',
            'breeding_date' => 'tanggal peminahan',
            'bull_id' => 'pejantan',
            'expected_next_heat_date' => 'perkiraan birahi berikutnya',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $animal = Animal::query()->find($this->input('animal_id'));

            if ($animal?->sex !== Sex::Female) {
                $validator->errors()->add('animal_id', 'Peminahan hanya dapat dicatat untuk ternak betina.');
            }

            $bullId = $this->input('bull_id');

            if ($bullId) {
                $bull = Animal::query()->find($bullId);

                if ($bull?->sex !== Sex::Male) {
                    $validator->errors()->add('bull_id', 'Pejantan yang dipilih harus berjenis kelamin jantan.');
                }

                if ($animal && (int) $bull->id === (int) $animal->id) {
                    $validator->errors()->add('bull_id', 'Pejantan tidak boleh sama dengan induk betina.');
                }
            }

            $recordType = $this->input('record_type');
            $isMating = in_array($recordType, [
                BreedingRecordType::Insemination->value,
                BreedingRecordType::NaturalMating->value,
            ], true);

            if ($isMating && ! $this->filled('bull_id') && ! $this->filled('semen_code')) {
                $validator->errors()->add('bull_id', 'Isi pejantan atau kode semen untuk peminahan.');
            }

            if ($recordType === BreedingRecordType::HeatObserved->value && ! $this->filled('heat_signs')) {
                $validator->errors()->add('heat_signs', 'Catat gejala birahi yang diamati.');
            }
        });
    }
}
