<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'farm_id',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasAbility(string $ability): bool
    {
        return $this->role->can($ability);
    }

    public function canAccessModule(string $module): bool
    {
        return $this->role->canAccessModule($module);
    }

    public function canManageFarm(Farm $farm): bool
    {
        // Roles without a farm assignment (owner, finance) see every farm.
        if ($this->farm_id === null) {
            return true;
        }

        return $this->farm_id === $farm->getKey();
    }

    /** @param Builder<User> $query */
    public function scopeForFarm(Builder $query, ?int $farmId): Builder
    {
        return $farmId ? $query->where('farm_id', $farmId) : $query;
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role->label();
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : mb_strtoupper(mb_substr($this->email, 0, 1));
    }
}
