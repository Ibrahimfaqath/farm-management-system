<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('settings.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $farmId = $this->route('farm')?->getKey();

        return [
            'code' => ['required', 'string', 'max:20', 'uppercase', Rule::unique('farms', 'code')->ignore($farmId)],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'regency' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_name' => ['nullable', 'string', 'max:150'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'established_date' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'kode peternakan',
            'name' => 'nama peternakan',
            'area_hectares' => 'luas lahan',
            'established_date' => 'tanggal berdiri',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Kode peternakan sudah digunakan.',
            'code.uppercase' => 'Kode peternakan harus huruf kapital.',
        ];
    }
}
