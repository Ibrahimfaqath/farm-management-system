<?php

namespace App\Models;

use App\Enums\MilkSession;
use Database\Factories\MilkProductionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilkProduction extends Model
{
    /** @use HasFactory<MilkProductionFactory> */
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'barn_id',
        'production_date',
        'session',
        'liters',
        'fat_percent',
        'protein_percent',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'session' => MilkSession::class,
            'liters' => 'decimal:2',
            'fat_percent' => 'decimal:2',
            'protein_percent' => 'decimal:2',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function barn(): BelongsTo
    {
        return $this->belongsTo(Barn::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('production_date', '>=', $from)->whereDate('production_date', '<=', $to);
    }

    public function scopeForFarm(Builder $query, ?int $farmId): Builder
    {
        return $farmId ? $query->where('farm_id', $farmId) : $query;
    }

    public function subjectLabel(): string
    {
        return $this->animal?->animal_code ?? $this->barn?->name ?? 'Kelompok';
    }
}
