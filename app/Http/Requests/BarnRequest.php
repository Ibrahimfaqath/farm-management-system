<?php

namespace App\Http\Requests;

use App\Enums\BarnType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BarnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('animal.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'exists:farms,id'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(BarnType::class)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'area_m2' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'farm_id' => 'peternakan',
            'capacity' => 'kapasitas',
            'area_m2' => 'luas kandang',
        ];
    }
}
