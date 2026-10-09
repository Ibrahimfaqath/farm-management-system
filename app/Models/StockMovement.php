<?php

namespace App\Models;

use App\Enums\MovementType;
use App\Enums\StockDirection;
use App\Enums\UnitOfMeasure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Every inventory change is one row here. Current stock is always derived from
 * these rows, never stored on the feed item.
 */
class StockMovement extends Model
{
    protected $fillable = [
        'feed_item_id',
        'inventory_batch_id',
        'type',
        'quantity',
        'unit',
        'movement_date',
        'reference',
        'source_type',
        'source_id',
        'reason',
        'unit_cost',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'unit' => UnitOfMeasure::class,
            'movement_date' => 'date',
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function feedItem(): BelongsTo
    {
        return $this->belongsTo(FeedItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function direction(): StockDirection
    {
        return $this->type->direction();
    }

    /**
     * Signed quantity used when deriving current stock.
     */
    public function signedQuantity(): float
    {
        return round($this->direction()->sign() * (float) $this->quantity, 4);
    }

    /**
     * SQL expression for the signed total, so stock can be aggregated in the database.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSigned(Builder $query, string $alias = 'signed_quantity'): Builder
    {
        $inbound = self::inboundTypeValues();

        $placeholders = implode(', ', array_fill(0, count($inbound), '?'));

        return $query->selectRaw(
            "COALESCE(SUM(CASE WHEN type IN ({$placeholders}) THEN quantity ELSE -quantity END), 0) as {$alias}",
            $inbound
        );
    }

    /** @return list<string> */
    public static function inboundTypeValues(): array
    {
        return array_values(array_map(
            fn (MovementType $type) => $type->value,
            array_filter(MovementType::cases(), fn (MovementType $type) => $type->direction() === StockDirection::In),
        ));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->whereIn('type', array_values(array_diff(
            array_map(fn (MovementType $type) => $type->value, MovementType::cases()),
            self::inboundTypeValues(),
        )));
    }
}
