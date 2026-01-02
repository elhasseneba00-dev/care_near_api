<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class Audit
{
    public static function log(?User $actor, string $action, ?string $entityType = null, ?int $entityId = null, array $meta = [], ?Request $request = null): void
    {
        $context = [];
        if ($request) {
            $context = [ 'ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'request_id' => $request->header('X-Request-Id') ];
        }

        $merdgedMeta = array_filter(array_merge($meta, $context), fn($value) => $value !== null && $value !== '');

        AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $merdgedMeta ?: null,
            'created_at' => now(),
        ]);
    }
}
