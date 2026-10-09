<?php

namespace App\Models;

use App\Enums\HealthRecordStatus;
use App\Enums\HealthRecordType;
use App\Enums\HealthSeverity;
use Database\Factories\HealthRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthRecord extends Model
{
    /** @use HasFactory<HealthRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'record_date',
        'record_type',
        'severity',
        'symptoms',
        'diagnosis',
        'notes',
        'status',
        'follow_up_date',
        'resolved_date',
        'cost',
        'veterinarian_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'follow_up_date' => 'date',
            'resolved_date' => 'date',
            'record_type' => HealthRecordType::class,
            'severity' => HealthSeverity::class,
            'status' => HealthRecordStatus::class,
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

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [HealthRecordStatus::Open->value, HealthRecordStatus::Monitoring->value]);
    }

    public function scopeOpenIssues(Builder $query): Builder
    {
        return $query->open()->whereNotNull('diagnosis');
    }
}
