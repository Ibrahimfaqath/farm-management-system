<?php

namespace App\Http\Requests;

use App\Enums\FeedCategory;
use App\Enums\UnitOfMeasure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeedItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('feed.purchase') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(FeedCategory::class)],
            'unit' => ['required', Rule::enum(UnitOfMeasure::class)],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'reorder_point' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'price_per_unit' => 'harga satuan',
            'reorder_point' => 'batas pemesanan ulang',
            'shelf_life_days' => 'masa simpan',
        ];
    }
}
