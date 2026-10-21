<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'reference',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function humanAction(): string
    {
        return static::labelFor($this->action);
    }

    /**
     * Turn a stored action key into a label the interface can show.
     *
     * The English label doubles as the translation key, so an action added
     * without a dictionary entry still renders as readable text rather than a
     * blank badge.
     */
    public static function labelFor(string $action): string
    {
        $label = ucfirst(str_replace(['.', '_'], ' ', $action));
        $translated = __($label);

        return $translated === $label ? $label : $translated;
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query->where('subject_type', $subject::class)->where('subject_id', $subject->getKey());
    }
}
