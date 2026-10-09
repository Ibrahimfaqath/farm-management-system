<?php

namespace App\Http\Requests;

use App\Enums\AnimalStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnimalExitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('animal.exit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'exit_type' => ['required', Rule::in([
                AnimalStatus::Sold->value,
                AnimalStatus::Deceased->value,
                AnimalStatus::Transferred->value,
            ])],
            'exit_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:2015-01-01'],
            'exit_value' => ['nullable', 'numeric', 'min:0', 'required_if:exit_type,sold'],
            'exit_reference' => ['nullable', 'string', 'max:60'],
            'exit_notes' => ['nullable', 'string', 'max:2000'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'exit_type' => 'jenis keluar',
            'exit_date' => 'tanggal keluar',
            'exit_value' => 'nilai transaksi',
            'exit_reference' => 'nomor referensi',
            'reason' => 'alasan',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan keluar wajib diisi untuk menjaga jejak riwayat.',
            'exit_value.required_if' => 'Nilai transaksi wajib diisi untuk hewan yang terjual.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $animal = $this->route('animal');

            if ($animal && $animal->status !== AnimalStatus::Active) {
                $validator->errors()->add('exit_type', 'Ternak ini sudah tidak berstatus aktif.');
            }
        });
    }
}
