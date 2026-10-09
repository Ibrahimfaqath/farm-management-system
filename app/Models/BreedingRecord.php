<?php

namespace App\Models;

use App\Enums\BreedingRecordType;
use Database\Factories\BreedingRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BreedingRecord extends Model
{
    /** @use HasFactory<BreedingRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'record_type',
        'breeding_date',
        'bull_id',
        'semen_code',
        'semen_batch',
        'inseminator',
        'heat_signs',
        'expected_next_heat_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'breeding_date' => 'date',
            'expected_next_heat_date' => 'date',
            'record_type' => BreedingRecordType::class,
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function bull(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'bull_id');
    }

    public function pregnancyChecks(): HasMany
    {
        return $this->hasMany(PregnancyCheck::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sireLabel(): string
    {
        return $this->bull?->animal_code ?? $this->semen_code ?? 'Tidak dicatat';
    }
}
