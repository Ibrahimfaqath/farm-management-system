<?php

namespace App\Models;

use Database\Factories\VaccinationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    /** @use HasFactory<VaccinationFactory> */
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'vaccine_name',
        'batch_number',
        'administered_date',
        'next_due_date',
        'dose',
        'route',
        'cost',
        'administered_by',
        'veterinarian_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'administered_date' => 'date',
            'next_due_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinarian_id');
    }

    public function scopeDue(Builder $query, int $withinDays = 30): Builder
    {
        return $query->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', now()->addDays($withinDays)->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->next_due_date !== null && $this->next_due_date->isPast();
    }

    public function daysUntilDue(): ?int
    {
        return $this->next_due_date?->diffInDays(now());
    }
}
