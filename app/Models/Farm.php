<?php

namespace App\Models;

use Database\Factories\FarmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Farm extends Model
{
    /** @use HasFactory<FarmFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'village',
        'district',
        'regency',
        'province',
        'postal_code',
        'phone',
        'manager_name',
        'area_hectares',
        'established_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'area_hectares' => 'decimal:2',
            'established_date' => 'date',
        ];
    }

    public function barns(): HasMany
    {
        return $this->hasMany(Barn::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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
}
