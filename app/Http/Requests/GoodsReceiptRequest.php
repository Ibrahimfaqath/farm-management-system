<?php

namespace App\Http\Requests;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('feed.receive') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'received_date' => ['required', 'date', 'before_or_equal:today'],
            'delivery_note_reference' => ['nullable', 'string', 'max:60'],
            'quality_notes' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'post_now' => ['boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.purchase_order_item_id' => ['nullable', 'exists:purchase_order_items,id'],
            'items.*.feed_item_id' => ['required', 'exists:feed_items,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:60'],
            'items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:received_date'],
            'items.*.quality_grade' => ['nullable', 'string', 'max:30'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'purchase_order_id' => 'pesanan pembelian',
            'received_date' => 'tanggal penerimaan',
            'delivery_note_reference' => 'nomor surat jalan',
            'items' => 'barang diterima',
            'quantity_received' => 'jumlah diterima',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'Penerimaan minimal harus memiliki satu item.',
            'items.*.quantity_received.gt' => 'Jumlah diterima harus lebih dari nol.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->input('purchase_order_id') && ! $this->input('supplier_id')) {
                $validator->errors()->add('purchase_order_id', 'Pilih pesanan pembelian atau pemasok.');
            }

            $orderId = $this->integer('purchase_order_id');

            if ($orderId === 0) {
                return;
            }

            $order = PurchaseOrder::query()->with('items')->find($orderId);

            if (! $order || $order->status === PurchaseOrderStatus::Cancelled->value) {
                $validator->errors()->add('purchase_order_id', 'Pesanan pembelian tidak dapat dipilih.');

                return;
            }

            if ($this->input('supplier_id') && (int) $this->input('supplier_id') !== $order->supplier_id) {
                $validator->errors()->add('supplier_id', 'Pemasok tidak sesuai dengan pesanan pembelian.');
            }

            $ordered = $order->items->keyBy('id');
            $alreadyReceived = $order->receivedQuantities();

            foreach ((array) $this->input('items', []) as $index => $item) {
                $poItemId = $item['purchase_order_item_id'] ?? null;

                if (! $poItemId) {
                    continue;
                }

                if (! $ordered->has($poItemId)) {
                    $validator->errors()->add("items.{$index}.purchase_order_item_id", 'Item tidak berasal dari pesanan pembelian ini.');

                    continue;
                }

                $outstanding = max(0, (float) $ordered[$poItemId]->quantity - (float) ($alreadyReceived[$poItemId] ?? 0));

                if ((float) $item['quantity_received'] > $outstanding + 0.0001) {
                    $validator->errors()->add(
                        "items.{$index}.quantity_received",
                        "Jumlah melebihi sisa pesanan ({$outstanding}).",
                    );
                }
            }
        });
    }

    public function shouldPost(): bool
    {
        return $this->boolean('post_now');
    }
}
