<?php

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use Database\Factories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class GoodsReceipt extends Model
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'purchase_order_id',
        'supplier_id',
        'received_date',
        'status',
        'delivery_note_reference',
        'total_amount',
        'quality_notes',
        'notes',
        'received_by',
        'created_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'posted_at' => 'datetime',
            'status' => GoodsReceiptStatus::class,
            'total_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function batches(): HasManyThrough
    {
        return $this->hasManyThrough(InventoryBatch::class, GoodsReceiptItem::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function recalculateTotal(): void
    {
        $this->update(['total_amount' => (float) $this->items()->sum('line_total')]);
    }

    /**
     * Outstanding payable for this receipt: received value minus what has been paid.
     */
    public function outstandingAmount(): float
    {
        if (! $this->status?->affectsInventory()) {
            return 0.0;
        }

        return max(0, (float) $this->total_amount - (float) $this->payments()->sum('amount'));
    }

    public function isFullyPaid(): bool
    {
        return $this->outstandingAmount() <= 0.005;
    }
}
