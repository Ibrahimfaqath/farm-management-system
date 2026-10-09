<?php

namespace App\Models;

use Database\Factories\BreedFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Breed extends Model
{
    /** @use HasFactory<BreedFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'species',
        'origin',
        'average_milk_liters',
        'average_lactation_days',
        'adult_weight_kg',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'average_milk_liters' => 'decimal:2',
            'adult_weight_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
