<?php

namespace App\Http\Requests;

use App\Models\HealthRecord;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TreatmentRequest extends FormRequest
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
            'health_record_id' => ['nullable', 'exists:health_records,id'],
            'medication_name' => ['required', 'string', 'max:150'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'route' => ['nullable', 'string', 'max:50'],
            'frequency' => ['nullable', 'string', 'max:100'],
            'treatment_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:treatment_date'],
            'withdrawal_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'veterinarian_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'animal_id' => 'ternak',
            'medication_name' => 'nama obat',
            'treatment_date' => 'tanggal pengobatan',
            'withdrawal_period_days' => 'masa tunggu',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $recordId = $this->input('health_record_id');

            if (! $recordId) {
                return;
            }

            $record = HealthRecord::query()->find($recordId);

            if ($record && (int) $record->animal_id !== (int) $this->input('animal_id')) {
                $validator->errors()->add('health_record_id', 'Rekam kesehatan yang dipilih bukan milik ternak tersebut.');
            }
        });
    }
}
