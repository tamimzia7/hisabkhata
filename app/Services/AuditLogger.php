<?php

namespace App\Services;

use App\Models\Mess;
use App\Models\MessAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Record an audit trail entry for a mess.
     *
     * @param  array<string, mixed>  $meta
     */
    public function log(
        Mess $mess,
        ?User $actor,
        string $action,
        ?Model $subject = null,
        array $meta = [],
        ?string $ipAddress = null,
    ): MessAuditLog {
        return MessAuditLog::create([
            'mess_id' => $mess->getKey(),
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip_address' => $ipAddress,
        ]);
    }
}
