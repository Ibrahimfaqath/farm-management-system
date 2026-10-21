<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BreedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('animal.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
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
            'average_milk_liters' => 'rata-rata produksi susu',
            'adult_weight_kg' => 'berat dewasa',
        ];
    }
}
