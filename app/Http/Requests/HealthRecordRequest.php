<?php

namespace App\Http\Requests;

use App\Enums\HealthRecordStatus;
use App\Enums\HealthRecordType;
use App\Enums\HealthSeverity;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HealthRecordRequest extends FormRequest
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
            'record_date' => ['required', 'date', 'before_or_equal:today'],
            'record_type' => ['required', Rule::enum(HealthRecordType::class)],
            'severity' => ['nullable', Rule::enum(HealthSeverity::class)],
            'symptoms' => ['nullable', 'string', 'max:2000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(HealthRecordStatus::class)],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:record_date'],
            'resolved_date' => ['nullable', 'date', 'after_or_equal:record_date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'veterinarian_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'animal_id' => 'ternak',
            'record_date' => 'tanggal rekam',
            'follow_up_date' => 'tanggal tindak lanjut',
            'resolved_date' => 'tanggal selesai',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isResolved = $this->input('status') === HealthRecordStatus::Resolved->value;

            if ($isResolved && ! $this->input('resolved_date')) {
                $validator->errors()->add('resolved_date', 'Rekam yang ditandai selesai wajib mencantumkan tanggal selesai.');
            }

            if ($this->input('record_type') === HealthRecordType::Diagnosis->value && ! $this->filled('diagnosis')) {
                $validator->errors()->add('diagnosis', 'Jenis rekam "Diagnosis" wajib mengisi kolom diagnosis oleh tenaga medis.');
            }
        });
    }
}
