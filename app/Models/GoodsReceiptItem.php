<?php

namespace App\Models;

use App\Enums\UnitOfMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'feed_item_id',
        'quantity_received',
        'unit',
        'unit_price',
        'line_total',
        'batch_number',
        'expiry_date',
        'quality_grade',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:2',
            'unit' => UnitOfMeasure::class,
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'expiry_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $item->line_total = round((float) $item->quantity_received * (float) $item->unit_price, 2);
        });
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function feedItem(): BelongsTo
    {
        return $this->belongsTo(FeedItem::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function batch(): HasOne
    {
        return $this->hasOne(InventoryBatch::class);
    }
}
