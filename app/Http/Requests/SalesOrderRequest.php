<?php

namespace App\Http\Requests;

use App\Enums\UnitOfMeasure;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('sales.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'order_date' => ['required', 'date', 'before_or_equal:today'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(
                    fn ($query) => $query
                        ->where('code', Product::COW_MILK_CODE)
                        ->where('is_active', true),
                ),
            ],
            'items.*.description' => ['required', 'string', 'max:150'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', Rule::enum(UnitOfMeasure::class), Rule::in([UnitOfMeasure::Liter->value])],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'customer_id' => 'pelanggan',
            'order_date' => 'tanggal pesanan',
            'items' => 'barang',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'Pesanan minimal harus memiliki satu barang.',
            'items.*.product_id.required' => 'Pilih produk susu sapi segar.',
            'items.*.product_id.exists' => 'Peternakan ini hanya menjual susu sapi segar.',
            'items.*.quantity.gt' => 'Kuantitas harus lebih dari nol.',
            'items.*.unit.in' => 'Susu sapi dijual dalam satuan liter.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('discount_amount') !== null
                && (float) $this->input('discount_amount') > $this->subtotal()) {
                $validator->errors()->add('discount_amount', 'Diskon tidak boleh melebihi subtotal.');
            }
        });
    }

    private function subtotal(): float
    {
        $total = 0.0;

        foreach ((array) $this->input('items', []) as $item) {
            $total += (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }

        return $total;
    }
}
