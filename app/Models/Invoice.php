<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'sales_order_id',
        'invoice_date',
        'payment_terms_days',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'payment_terms_days' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Outstanding receivable. Derived, never stored, so it always agrees with
     * the invoice total and the recorded payments.
     */
    public function balance(): float
    {
        if ($this->status === InvoiceStatus::Cancelled) {
            return 0.0;
        }

        return max(0, round((float) $this->total - (float) $this->paid_amount, 2));
    }

    public function isOverdue(): bool
    {
        return $this->balance() > 0 && $this->due_date !== null && $this->due_date->isPast();
    }

    public function daysOverdue(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(now()) : 0;
    }

    public function isSettled(): bool
    {
        return $this->balance() <= 0.005;
    }

    /**
     * Recompute the cached status from the payment total.
     */
    public function syncStatus(): void
    {
        $this->paid_amount = (float) $this->payments()->sum('amount');

        $status = match (true) {
            $this->status === InvoiceStatus::Cancelled => InvoiceStatus::Cancelled,
            $this->paid_amount <= 0 => InvoiceStatus::Unpaid,
            $this->paid_amount + 0.005 >= (float) $this->total => InvoiceStatus::Paid,
            default => InvoiceStatus::Partial,
        };

        if ($status !== $this->status) {
            $this->updateQuietly(['status' => $status]);
        } else {
            $this->paid_amount = (float) $this->payments()->sum('amount');
        }

        $this->refresh();
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled')->whereColumn('paid_amount', '<', 'total');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->outstanding()->whereDate('due_date', '<', now()->toDateString());
    }

    public function overdueAmount(): float
    {
        return $this->isOverdue() ? $this->balance() : 0.0;
    }
}
