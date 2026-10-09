<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'label',
        'description',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting || $setting->value === null || $setting->value === '') {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function put(string $key, mixed $value, ?string $group = null, ?string $type = null): void
    {
        $payload = [
            'value' => is_array($value) ? json_encode($value) : (string) $value,
            'group' => $group ?? 'general',
            'type' => $type ?? 'string',
            'label' => ucfirst(str_replace('_', ' ', $key)),
        ];

        $setting = static::query()->where('key', $key)->first();

        if ($setting) {
            $setting->update($payload);

            return;
        }

        static::query()->create($payload + ['key' => $key]);
    }

    /** @param Builder<Setting> $query */
    public function scopeInGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group)->orderBy('label');
    }
}
