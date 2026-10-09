<?php

namespace App\Models;

use Database\Factories\FeedConsumptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedConsumption extends Model
{
    /** @use HasFactory<FeedConsumptionFactory> */
    use HasFactory;

    protected $fillable = [
        'consumption_number',
        'farm_id',
        'barn_id',
        'consumption_date',
        'animal_count',
        'total_quantity',
        'total_amount',
        'average_per_animal',
        'allow_negative_stock',
        'override_reason',
        'override_by',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'consumption_date' => 'date',
            'total_quantity' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'average_per_animal' => 'decimal:4',
            'allow_negative_stock' => 'boolean',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function barn(): BelongsTo
    {
        return $this->belongsTo(Barn::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeedConsumptionItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FeedConsumptionAllocation::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'source_id')
            ->where('source_type', self::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function overrideBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_by');
    }

    public function hasNegativeStockItem(): bool
    {
        return $this->items->contains(fn (FeedConsumptionItem $item) => (float) $item->stock_after < 0);
    }

    /**
     * Per-item view model used by the "what just happened" summary.
     *
     * @return list<array<string, mixed>>
     */
    public function impactSummary(): array
    {
        return $this->items->map(fn (FeedConsumptionItem $item) => [
            'item' => $item->feedItem?->name ?? 'Pakan',
            'unit' => $item->unit->shortLabel(),
            'quantity' => (float) $item->quantity,
            'amount' => (float) $item->line_total,
            'batch' => $item->batch?->batch_code,
            'before' => (float) $item->stock_before,
            'after' => (float) $item->stock_after,
        ])->all();
    }
}
