<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BreedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('animal.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $breedId = $this->route('breed')?->getKey();

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('breeds', 'code')->ignore($breedId)],
            'name' => ['required', 'string', 'max:150'],
            'species' => ['required', 'in:cattle,buffalo,goat'],
            'origin' => ['nullable', 'string', 'max:100'],
            'average_milk_liters' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'average_lactation_days' => ['nullable', 'integer', 'min:60', 'max:400'],
            'adult_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'kode ras',
            'species' => 'jenis hewan',
            'average_milk_liters' => 'rata-rata produksi susu',
            'adult_weight_kg' => 'berat dewasa',
        ];
    }

    public function messages(): array
    {
        return ['code.unique' => 'Kode ras sudah digunakan.'];
    }
}
