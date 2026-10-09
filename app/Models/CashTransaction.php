<?php

namespace App\Models;

use App\Enums\CashCategory;
use App\Enums\CashDirection;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'direction',
        'category',
        'amount',
        'description',
        'method',
        'reference_number',
        'source_type',
        'source_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'direction' => CashDirection::class,
            'category' => CashCategory::class,
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
        ];
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signedAmount(): float
    {
        return round($this->direction->sign() * (float) $this->amount, 2);
    }

    public static function nextNumber(string $prefix = 'TRX'): string
    {
        $latest = static::query()
            ->where('transaction_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('transaction_number');

        $sequence = $latest ? ((int) substr($latest, -6)) + 1 : 1;

        return $prefix.'-'.now()->format('Ym').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /** Running cash balance including this transaction. */
    public static function balanceThrough(int|string $transactionId): float
    {
        $transaction = static::query()->find($transactionId);

        if (! $transaction) {
            return 0.0;
        }

        return (float) static::query()
            ->where('transaction_date', '<=', $transaction->transaction_date)
            ->where('id', '<=', $transaction->getKey())
            ->get(['direction', 'amount'])
            ->sum(fn (self $row) => $row->signedAmount());
    }
}
