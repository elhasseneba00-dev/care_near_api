<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
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
