<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('payment.record') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'invoice_id' => 'faktur',
            'customer_id' => 'pelanggan',
            'payment_date' => 'tanggal pembayaran',
            'reference_number' => 'nomor referensi',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $invoiceId = $this->input('invoice_id');

            if (! $invoiceId) {
                return;
            }

            $invoice = Invoice::query()->find($invoiceId);

            if (! $invoice) {
                $validator->errors()->add('invoice_id', 'Faktur tidak ditemukan.');

                return;
            }

            if ((int) $invoice->customer_id !== (int) $this->input('customer_id')) {
                $validator->errors()->add('customer_id', 'Pelanggan tidak sesuai dengan faktur.');
            }

            if ($invoice->isSettled()) {
                $validator->errors()->add('amount', 'Faktur ini sudah lunas.');
            }
        });
    }
}
