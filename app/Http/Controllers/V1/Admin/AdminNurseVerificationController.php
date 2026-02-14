<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NurseProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNurseVerificationController extends Controller
{
    /**
     * @OA\Get(
     *   path="/admin/nurses",
     *   tags={"Admin"},
     *   summary="List nurse profiles (filterable by verified status)",
     *   description="ADMIN only. Returns paginated nurse profiles.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="verified", in="query", required=false, description="Filter by verified status", @OA\Schema(type="boolean")),
     *   @OA\Response(response=200, description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *       @OA\Property(property="meta", type="object",
     *         @OA\Property(property="current_page", type="integer"),
     *         @OA\Property(property="last_page", type="integer"),
     *         @OA\Property(property="per_page", type="integer"),
     *         @OA\Property(property="total", type="integer")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=403, description="Forbidden — not ADMIN")
     * )
     */
    public  function  index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verified' => ['nullable', 'boolean'],
        ]);

        $verified = $validated['verified'] ?? null;

        $query = NurseProfile::query();

        if (!is_null($verified)) {
            $query->where('verified', (bool) $verified);
        }

        $profiles = $query->orderByDesc('updated_at')->paginate(20);

        return response()->json([
            'data' => $profiles->items(),
            'meta' => [
                'current_page' => $profiles->currentPage(),
                'last_page' => $profiles->lastPage(),
                'per_page' => $profiles->perPage(),
                'total' => $profiles->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *   path="/admin/nurses/{nurseUserId}/verify",
     *   tags={"Admin"},
     *   summary="Verify or unverify a nurse",
     *   description="ADMIN only. Sets the verified flag on a nurse profile. Admin should review the uploaded diploma before verifying.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="nurseUserId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"verified"},
     *       @OA\Property(property="verified", type="boolean", example=true)
     *     )
     *   ),
     *   @OA\Response(response=200, description="Verification status updated",
     *     @OA\JsonContent(@OA\Property(property="data", type="object"))
     *   ),
     *   @OA\Response(response=403, description="Forbidden — not ADMIN"),
     *   @OA\Response(response=404, description="Nurse profile not found"),
     *   @OA\Response(response=422, description="User is not a nurse")
     * )
     */
    public function verify(Request $request, int $nurseUserId): JsonResponse
    {
        $validated = $request->validate([
            'verified' => ['required', 'boolean'],
        ]);

        $user = User::query()->findOrFail($nurseUserId);

        if ($user->role !== 'NURSE') {
            return response()->json(['message' => 'User is not a nurse.'], 422);
        }
        $profile = NurseProfile::query()->where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Nurse profile not found.'], 404);
        }

        $profile->update(['verified' => (bool) $validated['verified']]);

        return response()->json([
            'data' => $profile->fresh(),
        ]);
    }
}
