<?php

namespace App\Models;

use App\Enums\BarnType;
use Database\Factories\BarnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Barn extends Model
{
    /** @use HasFactory<BarnFactory> */
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'code',
        'name',
        'type',
        'capacity',
        'area_m2',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => BarnType::class,
            'area_m2' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function feedConsumptions(): HasMany
    {
        return $this->hasMany(FeedConsumption::class);
    }

    public function milkProductions(): HasMany
    {
        return $this->hasMany(MilkProduction::class);
    }

    public function latestMilkProduction(): HasOne
    {
        return $this->hasOne(MilkProduction::class)->latestOfMany('production_date');
    }

    public function activeAnimalCount(): int
    {
        return $this->animals()->where('status', 'active')->count();
    }

    public function occupancyPercentage(): ?float
    {
        if (! $this->capacity) {
            return null;
        }

        return round($this->activeAnimalCount() / $this->capacity * 100, 1);
    }
}
