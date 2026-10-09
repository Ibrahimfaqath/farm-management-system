<?php

namespace App\Models;

use App\Enums\BirthType;
use Database\Factories\BirthRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BirthRecord extends Model
{
    /** @use HasFactory<BirthRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'dam_id',
        'sire_id',
        'animal_id',
        'pregnancy_check_id',
        'birth_date',
        'birth_time',
        'birth_type',
        'birth_weight_kg',
        'colostrum_intake',
        'calf_sex',
        'assistance_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'birth_time' => 'datetime:H:i',
            'birth_type' => BirthType::class,
            'birth_weight_kg' => 'decimal:2',
        ];
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'dam_id');
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'sire_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function pregnancyCheck(): BelongsTo
    {
        return $this->belongsTo(PregnancyCheck::class);
    }
}
