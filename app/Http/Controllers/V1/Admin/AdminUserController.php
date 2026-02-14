<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    // openApi documentation:
    /**
     * @OA\Get(
     *     path="/admin/users",
     *     summary="Get list of users with optional filters",
     *     tags={"Admin Users"},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by user role (e.g. 'PATIENT', 'NURSE', 'ADMIN')",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",         description="Filter by user status (e.g. 'ACTIVE', 'SUSPENDED')",       required=false,         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query to match against full name, phone, or email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",         description="Number of users to return per page (default: 20, max: 50)",        required=false,         @OA\Schema(type="integer", minimum=1, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with paginated list of users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *             )
     *         )
     *     ),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $validate = $request->validate([
            'role' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = $validate['per_page'] ?? 20;

        $query = User::query()->orderByDesc('id');

        if (!empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['q'])) {
            $q = $validated['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'ilike', "%{$q}%")
                    ->orWhere('phone', 'ilike', "%{$q}%")
                    ->orWhere('email', 'ilike', "%{$q}%");
            });
        }

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

    public function suspend(Request $request, int $userId): JsonResponse{
        $user = User::query()->findOrFail($userId);

        // Optional: prevent suspending self
        if ($request->user()->id === $user->id){
            return response()->json(['message' => 'Cannot suspend yourself.'], 422);
        }

        $user->update(['status' => 'SUSPENDED']);

        // Optional: revoke tokens
        $user->tokens()->delete();

        return response()->json([
            'data' => $user->fresh()
        ]);
    }

    public function unsuspend(int $userId): JsonResponse
    {
        $user = User::query()->findOrFail($userId);

        $user->update(['status' => 'ACTIVE']);

        return response()->json([
            'data' => $user->fresh(),
        ]);
    }
}
