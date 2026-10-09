<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operational attribution of a consumption line to individual animals.
 * These rows never move stock — the parent consumption item does that once.
 */
class FeedConsumptionAllocation extends Model
{
    protected $fillable = [
        'feed_consumption_id',
        'feed_consumption_item_id',
        'animal_id',
        'quantity',
        'note',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2'];
    }

    public function feedConsumption(): BelongsTo
    {
        return $this->belongsTo(FeedConsumption::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(FeedConsumptionItem::class, 'feed_consumption_item_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
