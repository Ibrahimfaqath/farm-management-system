<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Models\Animal;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class VaccinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('health.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'animal_id' => ['required', 'exists:animals,id'],
            'vaccine_name' => ['required', 'string', 'max:150'],
            'batch_number' => ['nullable', 'string', 'max:60'],
            'administered_date' => ['required', 'date', 'before_or_equal:today'],
            'next_due_date' => ['nullable', 'date', 'after:administered_date'],
            'dose' => ['nullable', 'string', 'max:100'],
            'route' => ['nullable', 'string', 'max:50'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'administered_by' => ['nullable', 'string', 'max:150'],
            'veterinarian_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'animal_id' => 'ternak',
            'vaccine_name' => 'nama vaksin',
            'administered_date' => 'tanggal pemberian',
            'next_due_date' => 'tanggal jatuh tempo',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $animal = Animal::query()->find($this->input('animal_id'));

            if ($animal?->sex === Sex::Female && $animal->date_of_birth
                && $this->input('administered_date') && $animal->date_of_birth->gt(Carbon::parse($this->input('administered_date')))) {
                $validator->errors()->add('administered_date', 'Tanggal pemberian tidak boleh sebelum tanggal lahir hewan.');
            }
        });
    }
}
