<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Lightweight audit trail: who did what, when, why and to which record.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $properties
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?string $reference = null,
        ?array $properties = null,
    ): ActivityLog {
        $user = Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->getKey(),
            'user_name' => $user?->name,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description ?? $action,
            'reference' => $reference,
            'properties' => $properties,
            'ip_address' => Request::ip(),
        ]);
    }
}
