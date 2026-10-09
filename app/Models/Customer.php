<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'credit_limit',
        'payment_terms_days',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Outstanding receivable across this customer's invoices.
     *
     * Derived from total minus paid_amount, exactly like Invoice::balance() and
     * FinanceService::accountsReceivable(). There is no stored `balance`
     * column, so this must stay an expression.
     */
    public function outstandingBalance(): float
    {
        $sum = (float) $this->invoices()
            ->where('status', '!=', 'cancelled')
            ->sum(DB::raw('total - paid_amount'));

        return max(0, round($sum, 2));
    }
}
