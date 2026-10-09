<?php

namespace App\Http\Requests;

use App\Enums\IncomeCategory;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('finance.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'income_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', Rule::enum(IncomeCategory::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:255'],
            'source_name' => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:60'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'category.in' => 'Pendapatan penjualan susu dicatat lewat faktur dan pembayaran, bukan di halaman ini.',
        ];
    }
}
