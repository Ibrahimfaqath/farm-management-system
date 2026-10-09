<?php

namespace App\Models;

use App\Enums\FeedCategory;
use App\Enums\UnitOfMeasure;
use Database\Factories\FeedItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedItem extends Model
{
    /** @use HasFactory<FeedItemFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'price_per_unit',
        'reorder_point',
        'reorder_quantity',
        'storage_location',
        'shelf_life_days',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => FeedCategory::class,
            'unit' => UnitOfMeasure::class,
            'price_per_unit' => 'decimal:2',
            'reorder_point' => 'decimal:2',
            'reorder_quantity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function consumptionItems(): HasMany
    {
        return $this->hasMany(FeedConsumptionItem::class);
    }

    /**
     * Current stock derived from posted inventory movements.
     */
    public function currentStock(): float
    {
        return round((float) $this->stockMovements()->signed()->value('signed_quantity'), 4);
    }

    public function isBelowReorderPoint(): bool
    {
        return (float) $this->reorder_point > 0 && $this->currentStock() <= (float) $this->reorder_point;
    }

    public function isOutOfStock(): bool
    {
        return $this->currentStock() <= 0;
    }

    public function averageUnitCost(): float
    {
        return (float) $this->inventoryBatches()
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->avg('unit_cost');
    }
}
