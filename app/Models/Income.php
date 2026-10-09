<?php

namespace App\Models;

use App\Enums\IncomeCategory;
use App\Enums\PaymentMethod;
use Database\Factories\IncomeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Money received outside customer invoicing, for example manure sales or
 * livestock disposal. Customer receipts are represented by payments, so this
 * table must not double-count sales revenue.
 */
class Income extends Model
{
    /** @use HasFactory<IncomeFactory> */
    use HasFactory;

    protected $fillable = [
        'income_number',
        'income_date',
        'category',
        'amount',
        'description',
        'source_name',
        'reference',
        'payment_method',
        'cash_transaction_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'income_date' => 'date',
            'category' => IncomeCategory::class,
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function cashTransaction(): HasOne
    {
        return $this->hasOne(CashTransaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('income_date', '>=', $from)->whereDate('income_date', '<=', $to);
    }
}
