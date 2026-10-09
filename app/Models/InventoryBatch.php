<?php

namespace App\Models;

use App\Enums\BatchStatus;
use App\Enums\UnitOfMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    protected $table = 'inventory_batches';

    protected $fillable = [
        'feed_item_id',
        'goods_receipt_item_id',
        'batch_code',
        'unit',
        'received_date',
        'original_quantity',
        'quantity_remaining',
        'expiry_date',
        'storage_location',
        'quality_grade',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'unit' => UnitOfMeasure::class,
            'received_date' => 'date',
            'expiry_date' => 'date',
            'original_quantity' => 'decimal:2',
            'quantity_remaining' => 'decimal:2',
            'status' => BatchStatus::class,
        ];
    }

    public function feedItem(): BelongsTo
    {
        return $this->belongsTo(FeedItem::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status->isUsable() && ! $this->isExpired() && (float) $this->quantity_remaining > 0;
    }
}
