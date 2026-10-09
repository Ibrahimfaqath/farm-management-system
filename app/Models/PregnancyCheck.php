<?php

namespace App\Models;

use App\Enums\PregnancyCheckMethod;
use App\Enums\PregnancyResult;
use Database\Factories\PregnancyCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class PregnancyCheck extends Model
{
    /** @use HasFactory<PregnancyCheckFactory> */
    use HasFactory;

    /** Standard gestation length for cattle and buffalo. */
    public const GESTATION_DAYS = 283;

    protected $fillable = [
        'animal_id',
        'breeding_record_id',
        'check_date',
        'method',
        'result',
        'estimated_gestation_days',
        'expected_due_date',
        'fetus_count',
        'notes',
        'veterinarian_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'check_date' => 'date',
            'expected_due_date' => 'date',
            'method' => PregnancyCheckMethod::class,
            'result' => PregnancyResult::class,
            'estimated_gestation_days' => 'integer',
            'fetus_count' => 'integer',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function breedingRecord(): BelongsTo
    {
        return $this->belongsTo(BreedingRecord::class);
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinarian_id');
    }

    public function birthRecord(): HasOne
    {
        return $this->hasOne(BirthRecord::class);
    }

    /**
     * Expected due date derived from the mating date when not supplied.
     */
    public function expectedDueDate(): ?Carbon
    {
        if ($this->expected_due_date) {
            return $this->expected_due_date;
        }

        $breedingDate = $this->breedingRecord?->breeding_date;

        return $breedingDate?->copy()->addDays(self::GESTATION_DAYS);
    }

    public function daysUntilDue(): ?int
    {
        return $this->expectedDueDate()?->diffInDays(now());
    }
}
