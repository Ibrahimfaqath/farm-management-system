<?php

namespace App\Models;

use App\Enums\UnitOfMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /**
     * The farm sells fresh cow's milk; every sales-order line must point here.
     */
    public const COW_MILK_CODE = 'PRD-SUSU';

    protected $fillable = [
        'code',
        'name',
        'unit',
        'default_price',
        'category',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit' => UnitOfMeasure::class,
            'default_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
