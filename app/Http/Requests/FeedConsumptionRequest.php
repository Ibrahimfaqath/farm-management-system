<?php

namespace App\Http\Requests;

use App\Models\Barn;
use App\Models\Farm;
use App\Models\InventoryBatch;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class FeedConsumptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('feed.consume') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'exists:farms,id'],
            'barn_id' => ['nullable', 'exists:barns,id'],
            'consumption_date' => ['required', 'date', 'before_or_equal:today'],
            'animal_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'allow_negative_stock' => ['boolean'],
            'override_reason' => ['nullable', 'required_if:allow_negative_stock,1', 'string', 'min:10', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.feed_item_id' => ['required', 'exists:feed_items,id'],
            'items.*.inventory_batch_id' => ['nullable', 'exists:inventory_batches,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'farm_id' => 'peternakan',
            'barn_id' => 'kandang',
            'consumption_date' => 'tanggal pemakaian',
            'animal_count' => 'jumlah hewan',
            'items' => 'item pemakaian',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'Pemakaian minimal harus memiliki satu item pakan.',
            'items.*.quantity.gt' => 'Kuantitas pemakaian harus lebih dari nol.',
            'override_reason.required_if' => 'Wajib menyertakan alasan bila memakai stok di bawah nol.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $farmId = $this->integer('farm_id');
            $barnId = $this->integer('barn_id');

            if ($barnId && ! Barn::query()->whereKey($barnId)->where('farm_id', $farmId)->exists()) {
                $validator->errors()->add('barn_id', 'Kandang bukan milik peternakan tersebut.');
            }

            $seen = [];

            foreach ((array) $this->input('items', []) as $index => $item) {
                $feedItemId = $item['feed_item_id'] ?? null;

                if ($feedItemId !== null && in_array($feedItemId, $seen, true)) {
                    $validator->errors()->add("items.{$index}.feed_item_id", 'Item pakan tidak boleh dobel.');
                }

                $batchId = $item['inventory_batch_id'] ?? null;

                if ($feedItemId !== null && $batchId) {
                    $batch = InventoryBatch::query()->find($batchId);

                    if (! $batch || (int) $batch->feed_item_id !== (int) $feedItemId) {
                        $validator->errors()->add("items.{$index}.inventory_batch_id", 'Batch tidak sesuai dengan item pakan.');
                    }
                }

                if ($feedItemId !== null) {
                    $seen[] = $feedItemId;
                }
            }
        });
    }

    public function farm(): ?Farm
    {
        return Farm::query()->find($this->input('farm_id'));
    }
}
