<?php

namespace App\Models;

use Database\Factories\TreatmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Treatment extends Model
{
    /** @use HasFactory<TreatmentFactory> */
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'health_record_id',
        'medication_name',
        'dosage',
        'route',
        'frequency',
        'treatment_date',
        'end_date',
        'withdrawal_period_days',
        'cost',
        'veterinarian_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'treatment_date' => 'date',
            'end_date' => 'date',
            'cost' => 'decimal:2',
            'withdrawal_period_days' => 'integer',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function healthRecord(): BelongsTo
    {
        return $this->belongsTo(HealthRecord::class);
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinarian_id');
    }

    /**
     * Last date the milk from this animal must be withheld, if any.
     */
    public function withdrawalEndsAt(): ?Carbon
    {
        $base = $this->end_date ?? $this->treatment_date;

        if ($base === null || ! $this->withdrawal_period_days) {
            return null;
        }

        return $base->copy()->addDays((int) $this->withdrawal_period_days);
    }

    public function isWithdrawalActive(): bool
    {
        $end = $this->withdrawalEndsAt();

        return $end !== null && $end->isFuture();
    }
}
