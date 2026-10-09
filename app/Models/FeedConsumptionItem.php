<?php

namespace App\Models;

use App\Enums\UnitOfMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedConsumptionItem extends Model
{
    protected $fillable = [
        'feed_consumption_id',
        'feed_item_id',
        'inventory_batch_id',
        'quantity',
        'unit',
        'unit_cost',
        'line_total',
        'stock_before',
        'stock_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit' => UnitOfMeasure::class,
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
            'stock_before' => 'decimal:2',
            'stock_after' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $item->line_total = round((float) $item->quantity * (float) $item->unit_cost, 2);
        });
    }

    public function feedConsumption(): BelongsTo
    {
        return $this->belongsTo(FeedConsumption::class);
    }

    public function feedItem(): BelongsTo
    {
        return $this->belongsTo(FeedItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FeedConsumptionAllocation::class);
    }
}
