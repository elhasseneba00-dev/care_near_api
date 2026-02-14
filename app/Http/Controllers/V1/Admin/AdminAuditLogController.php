<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    /**
     * @OA\Get(
     *   path="/admin/audit-logs",
     *   tags={"Admin"},
     *   summary="Get audit logs with filters and pagination",
     *   description="Returns a paginated list of audit logs. Supports filtering by action, actor_user_id, request_id, entity_type, entity_id, and date range (from/to).",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="action", in="query", required=false, @OA\Schema(type="string", example="user.created")),
     *   @OA\Parameter(name="actor_user_id", in="query", required=false, @OA\Schema(type="integer", example=3)),
     *   @OA\Parameter(name="request_id", in="query", required=false, @OA\Schema(type="string", example="abc12345")),
     *   @OA\Parameter(name="entity_type", in="query", required=false, @OA\Schema(type="string", example="User")),
     *   @OA\Parameter(name="entity_id", in="query", required=false, @OA\Schema(type="integer", example=5)),
     *   @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", format="date-time", example="2024-01-01T00:00:00Z")),
     *     @OA\Parameter(name="to", in="query", required=false, @OA\Schema(type="string", format="date-time", example="2024-12-31T23:59:59Z")),
     *   @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", example=50)),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="array", @OA\Items(type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="action", type="string", example="user.created"),
     *         @OA\Property(property="actor_user_id", type="integer", example=3),
     *         @OA\Property(property="entity_type", type="string", example="User"),
     *         @OA\Property(property="entity_id", type="integer", example=5),
     *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-01T12:00:00Z")
     *       )),
     *       @OA\Property(property="meta", type="object",
     *         @OA\Property(property="current_page", type="integer"),
     *         @OA\Property(property="last_page", type="integer"),
     *         @OA\Property(property="per_page", type="integer"),
     *         @OA\Property(property="total", type="integer")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
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
