<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\GoodsReceipt;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('finance.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'goods_receipt_id' => ['nullable', 'exists:goods_receipts,id'],
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
            'supplier_id' => 'pemasok',
            'goods_receipt_id' => 'penerimaan barang',
            'payment_date' => 'tanggal pembayaran',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $receiptId = $this->input('goods_receipt_id');

            if (! $receiptId) {
                return;
            }

            $receipt = GoodsReceipt::query()->find($receiptId);

            if (! $receipt) {
                $validator->errors()->add('goods_receipt_id', 'Penerimaan barang tidak ditemukan.');

                return;
            }

            if ((int) $receipt->supplier_id !== (int) $this->input('supplier_id')) {
                $validator->errors()->add('supplier_id', 'Pemasok tidak sesuai dengan penerimaan barang.');
            }

            if ((float) $this->input('amount') > $receipt->outstandingAmount() + 0.005) {
                $validator->errors()->add('amount', 'Pembayaran melebihi sisa utang penerimaan ini.');
            }
        });
    }
}
