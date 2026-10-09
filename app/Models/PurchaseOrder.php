<?php

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'expected_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'approved_at' => 'datetime',
            'status' => PurchaseOrderStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');

        $this->update([
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal + (float) $this->tax_amount - (float) $this->discount_amount),
        ]);
    }

    /**
     * Total received per purchase order item, based on posted receipts only.
     *
     * @return array<int, float> purchase_order_item_id => quantity
     */
    public function receivedQuantities(): array
    {
        return $this->goodsReceipts()
            ->where('status', GoodsReceiptStatus::Posted->value)
            ->join('goods_receipt_items', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
            ->whereNotNull('goods_receipt_items.purchase_order_item_id')
            ->groupBy('goods_receipt_items.purchase_order_item_id')
            ->selectRaw('goods_receipt_items.purchase_order_item_id, SUM(goods_receipt_items.quantity_received) as received')
            ->pluck('received', 'goods_receipt_items.purchase_order_item_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    public function isFullyReceived(): bool
    {
        $received = $this->receivedQuantities();

        foreach ($this->items as $item) {
            if (($received[$item->id] ?? 0.0) + 0.0001 < (float) $item->quantity) {
                return false;
            }
        }

        return true;
    }

    public function totalReceived(): float
    {
        return array_sum($this->receivedQuantities());
    }

    public function totalOutstanding(): float
    {
        return max(0, (float) $this->items()->sum('quantity') - $this->totalReceived());
    }
}
