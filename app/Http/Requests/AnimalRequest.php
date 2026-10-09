<?php

namespace App\Http\Requests;

use App\Enums\AcquisitionType;
use App\Enums\AnimalStatus;
use App\Enums\BreedingStatus;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\Barn;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('animal.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $animalId = $this->route('animal')?->getKey();

        return [
            'animal_code' => [
                'required', 'string', 'max:30',
                Rule::unique('animals', 'animal_code')->ignore($animalId),
            ],
            'name' => ['nullable', 'string', 'max:100'],
            'farm_id' => ['required', 'exists:farms,id'],
            'breed_id' => ['required', 'exists:breeds,id'],
            'barn_id' => ['nullable', 'exists:barns,id'],
            'sex' => ['required', Rule::enum(Sex::class)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::enum(AnimalStatus::class)],
            'production_status' => ['required', Rule::enum(ProductionStatus::class)],
            'breeding_status' => ['required', Rule::enum(BreedingStatus::class)],
            'acquisition_date' => ['nullable', 'date', 'before_or_equal:today'],
            'acquisition_type' => ['nullable', Rule::enum(AcquisitionType::class)],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'source_name' => ['nullable', 'string', 'max:150'],
            'dam_id' => ['nullable', 'exists:animals,id', Rule::notIn([$animalId])],
            'sire_id' => ['nullable', 'exists:animals,id', Rule::notIn([$animalId])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'animal_code' => 'kode hewan',
            'farm_id' => 'peternakan',
            'breed_id' => 'ras',
            'barn_id' => 'kandang',
            'date_of_birth' => 'tanggal lahir',
            'acquisition_date' => 'tanggal perolehan',
            'acquisition_type' => 'jenis perolehan',
            'acquisition_cost' => 'nilai perolehan',
            'source_name' => 'sumber',
            'dam_id' => 'induk betina',
            'sire_id' => 'induk jantan',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'animal_code.unique' => 'Kode hewan sudah digunakan.',
            'dam_id.not_in' => 'Seekor hewan tidak dapat menjadi induknya sendiri.',
            'sire_id.not_in' => 'Seekor hewan tidak dapat menjadi induknya sendiri.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStatusCoherence($validator);
            $this->validateGenealogy($validator);
            $this->validateDates($validator);
            $this->validateLocation($validator);
        });
    }

    private function validateLocation(Validator $validator): void
    {
        $barnId = $this->input('barn_id');
        $farmId = $this->input('farm_id');

        if ($barnId && $farmId && ! Barn::query()->whereKey($barnId)->where('farm_id', $farmId)->exists()) {
            $validator->errors()->add('barn_id', 'Kandang yang dipilih bukan milik peternakan tersebut.');
        }
    }

    private function validateStatusCoherence(Validator $validator): void
    {
        $sex = $this->input('sex');
        $production = $this->input('production_status');
        $breeding = $this->input('breeding_status');

        if ($sex === Sex::Male->value) {
            $allowed = [ProductionStatus::Calf->value, ProductionStatus::Heifer->value, ProductionStatus::Fattening->value, ProductionStatus::Bull->value];

            if ($production !== null && ! in_array($production, $allowed, true)) {
                $validator->errors()->add('production_status', 'Ternak jantan hanya dapat berstatus anakan, betina muda, gemukan, atau pejantan.');
            }

            if ($breeding !== BreedingStatus::NotApplicable->value) {
                $validator->errors()->add('breeding_status', 'Ternak jantan tidak memiliki status peminahan.');
            }

            return;
        }

        $disallowed = [ProductionStatus::Bull->value];

        if ($production !== null && in_array($production, $disallowed, true)) {
            $validator->errors()->add('production_status', 'Status pejantan hanya berlaku untuk ternak jantan.');
        }
    }

    private function validateGenealogy(Validator $validator): void
    {
        $damId = $this->input('dam_id');
        $sireId = $this->input('sire_id');

        if ($damId && $sireId && (int) $damId === (int) $sireId) {
            $validator->errors()->add('sire_id', 'Induk betina dan induk jantan tidak boleh sama.');
        }

        if ($damId && Animal::query()->whereKey($damId)->where('sex', Sex::Male->value)->exists()) {
            $validator->errors()->add('dam_id', 'Induk betina harus berjenis kelamin betina.');
        }

        if ($sireId && Animal::query()->whereKey($sireId)->where('sex', Sex::Female->value)->exists()) {
            $validator->errors()->add('sire_id', 'Induk jantan harus berjenis kelamin jantan.');
        }
    }

    private function validateDates(Validator $validator): void
    {
        $birthDate = $this->input('date_of_birth');
        $acquisitionDate = $this->input('acquisition_date');

        if ($birthDate && $acquisitionDate && $acquisitionDate < $birthDate) {
            $validator->errors()->add('acquisition_date', 'Tanggal perolehan tidak boleh sebelum tanggal lahir.');
        }
    }
}
