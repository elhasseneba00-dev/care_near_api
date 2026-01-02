<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->orderByDesc('id');

        if ($request->filled('action')) {
            $query->where('action', (string) $request->query('action'));
        }

        if ($request->filled('actor_user_id')) {
            $query->where('actor_user_id', (int) $request->query('actor_user_id'));
        }

        if ($request->filled('request_id')) {
            // stored in meta->request_id
            $query->where('meta->request_id', (string) $request->query('request_id'));
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', (string) $request->query('entity_type'));
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', (int) $request->query('entity_id'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', (string) $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', (string) $request->query('to'));
        }

        $perPage = (int) ($request->query('per_page', 50));
        $perPage = max(1, min(200, $perPage));

        $items = $query->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
