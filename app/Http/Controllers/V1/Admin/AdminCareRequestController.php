<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Care\CareRequestResource;
use App\Models\CareRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCareRequestController extends Controller
{
    // openApi documentation
    /**
     * @OA\Get(
     *     path="/api/v1/admin/care-requests",
     *     summary="List care requests with filters and pagination",
     *     tags={"Admin Care Requests"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by care request status (e.g., pending, accepted, completed)",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=20)
     *     ),
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="Filter by city",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=80)
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Filter care requests created from this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Filter care requests created up to this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *      @OA\Parameter(
     *          name="patient_user_id",
     *          in="query",
     *          description="Filter by patient user ID",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="nurse_user_id",
     *          in="query",
     *          description="Filter by nurse user ID",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Number of items per page for pagination (default: 20, max: 50)",
     *          required=false,
     *          @OA\Schema(type="integer", minimum=1, maximum=50)
    ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with paginated care requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:80'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],

            'patient_user_id' => ['nullable', 'integer'],
            'nurse_user_id' => ['nullable', 'integer'],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:50']
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);

        $query = CareRequest::query()->orderByDesc('id');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['city'])){
            $query->where('city', $validated['city']);
        }

        if (!empty($validated['patient_user_id'])){
            $query->where('patient_user_id', (int) $validated['patient_user_id']);
        }

        if (!empty($validated['nurse_user_id'])){
            $query->where('nurse_user_id', (int) $validated['nurse_user_id']);
        }

        if (!empty($validated['from'])){
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (!empty($validated['to'])){
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $items = $query->paginate($perPage);

        return response()->json([
            'data' => CareRequestResource::collection($items)->response()->getData(true)['data'],
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ]
        ]);
    }
}
