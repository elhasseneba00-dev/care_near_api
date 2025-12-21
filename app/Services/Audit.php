<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class Audit
{
    public static function log(?User $actor, string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
