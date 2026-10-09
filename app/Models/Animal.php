<?php

namespace App\Models;

use App\Enums\AcquisitionType;
use App\Enums\AnimalStatus;
use App\Enums\BreedingStatus;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use Database\Factories\AnimalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Animal extends Model
{
    /** @use HasFactory<AnimalFactory> */
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'barn_id',
        'breed_id',
        'animal_code',
        'name',
        'status',
        'production_status',
        'breeding_status',
        'sex',
        'date_of_birth',
        'acquisition_date',
        'acquisition_type',
        'acquisition_cost',
        'source_name',
        'dam_id',
        'sire_id',
        'current_barn_id',
        'exit_date',
        'exit_type',
        'exit_value',
        'exit_reference',
        'exit_notes',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnimalStatus::class,
            'production_status' => ProductionStatus::class,
            'breeding_status' => BreedingStatus::class,
            'sex' => Sex::class,
            'acquisition_type' => AcquisitionType::class,
            'date_of_birth' => 'date',
            'acquisition_date' => 'date',
            'exit_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'exit_value' => 'decimal:2',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function barn(): BelongsTo
    {
        return $this->belongsTo(Barn::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(self::class, 'dam_id');
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sire_id');
    }

    public function calves(): HasMany
    {
        return $this->hasMany(self::class, 'dam_id');
    }

    /** @return HasMany<self> */
    public function offspringFromSire(): HasMany
    {
        return $this->hasMany(self::class, 'sire_id');
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function breedingRecords(): HasMany
    {
        return $this->hasMany(BreedingRecord::class);
    }

    public function pregnancyChecks(): HasMany
    {
        return $this->hasMany(PregnancyCheck::class);
    }

    public function birthRecordsAsDam(): HasMany
    {
        return $this->hasMany(BirthRecord::class, 'dam_id');
    }

    public function birthRecordsAsSire(): HasMany
    {
        return $this->hasMany(BirthRecord::class, 'sire_id');
    }

    public function milkProductions(): HasMany
    {
        return $this->hasMany(MilkProduction::class);
    }

    public function feedAllocations(): HasMany
    {
        return $this->hasMany(FeedConsumptionAllocation::class);
    }

    public function openHealthRecords(): HasMany
    {
        return $this->healthRecords()->whereIn('status', ['open', 'monitoring']);
    }

    /** @return HasOne<HealthRecord, $this> */
    public function latestHealthRecord(): HasOne
    {
        return $this->hasOne(HealthRecord::class)->latestOfMany('record_date');
    }

    /** @return HasOne<PregnancyCheck, $this> */
    public function latestPregnancyCheck(): HasOne
    {
        return $this->hasOne(PregnancyCheck::class)->latestOfMany('check_date');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AnimalStatus::Active->value);
    }

    public function scopeLactating(Builder $query): Builder
    {
        return $query->where('production_status', ProductionStatus::Lactating->value);
    }

    public function scopePregnant(Builder $query): Builder
    {
        return $query->whereIn('breeding_status', [
            BreedingStatus::Pregnant->value,
            BreedingStatus::ConfirmedPregnant->value,
        ]);
    }

    public function scopeFemale(Builder $query): Builder
    {
        return $query->where('sex', Sex::Female->value);
    }

    public function scopeWithOpenHealthIssue(Builder $query): Builder
    {
        return $query->whereHas('healthRecords', fn (Builder $q) => $q->whereIn('status', ['open', 'monitoring']));
    }

    /** @param Builder<self> $query */
    public function scopeBornBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('date_of_birth', '>=', $from)->whereDate('date_of_birth', '<=', $to);
    }

    // ------------------------------------------------------------------
    // Presentation helpers
    // ------------------------------------------------------------------

    public function getDisplayNameAttribute(): string
    {
        return trim($this->animal_code.' '.(string) $this->name);
    }

    public function getShortLabelAttribute(): string
    {
        return $this->name ? $this->animal_code.' ('.$this->name.')' : $this->animal_code;
    }

    protected function ageInDays(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (! $this->date_of_birth) {
                return null;
            }

            return $this->date_of_birth->diffInDays(now());
        });
    }

    /**
     * Human readable age, e.g. "3 th 4 bln" (years/months).
     */
    public function getAgeLabelAttribute(): string
    {
        if (! $this->date_of_birth) {
            return '—';
        }

        $reference = $this->exit_date ?? now();
        $years = $this->date_of_birth->diffInYears($reference);
        $months = $this->date_of_birth->diffInMonths($reference) % 12;

        if ($years === 0 && $months === 0) {
            return $this->date_of_birth->diffInDays($reference).' hr';
        }

        if ($years === 0) {
            return $months.' bln';
        }

        return $years.' th '.$months.' bln';
    }

    /**
     * Health facet derived from open records — never inferred from production.
     */
    public function healthStatusLabel(): string
    {
        $open = $this->relationLoaded('openHealthRecords')
            ? $this->openHealthRecords->count()
            : $this->open_health_records_count ?? $this->openHealthRecords()->count();

        return $open > 0 ? 'Under care' : 'Healthy / routine';
    }

    public function isSold(): bool
    {
        return $this->status === AnimalStatus::Sold;
    }

    public function isDeceased(): bool
    {
        return $this->status === AnimalStatus::Deceased;
    }
}
