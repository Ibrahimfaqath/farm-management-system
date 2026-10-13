<?php

namespace App\Http\Requests;

use App\Enums\UnitOfMeasure;
use App\Models\Supplier;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('feed.purchase') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date', 'before_or_equal:today'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.feed_item_id' => ['required', 'exists:feed_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', Rule::enum(UnitOfMeasure::class)],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.batch_hint' => ['nullable', 'string', 'max:60'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'supplier_id' => 'pemasok',
            'order_date' => 'tanggal pesanan',
            'expected_date' => 'tanggal perkiraan tiba',
            'items' => 'item pesanan',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'Pesanan minimal harus memiliki satu item pakan.',
            'items.*.quantity.gt' => 'Kuantitas pesanan harus lebih dari nol.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('discount_amount') !== null
                && (float) $this->input('discount_amount') > (float) $this->subtotal()) {
                $validator->errors()->add('discount_amount', 'Diskon tidak boleh melebihi subtotal pesanan.');
            }

            $seen = [];

            foreach ((array) $this->input('items', []) as $index => $item) {
                $feedItemId = $item['feed_item_id'] ?? null;

                if ($feedItemId !== null && in_array($feedItemId, $seen, true)) {
                    $validator->errors()->add("items.{$index}.feed_item_id", 'Item pakan tidak boleh dobel dalam satu pesanan.');
                }

                if ($feedItemId !== null) {
                    $seen[] = $feedItemId;
                }
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

    public function supplier(): ?Supplier
    {
        return Supplier::query()->find($this->input('supplier_id'));
    }
}
