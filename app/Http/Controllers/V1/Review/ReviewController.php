<?php

namespace App\Http\Controllers\V1\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Review\StoreReviewRequest;
use App\Http\Resources\V1\Review\ReviewResource;
use App\Models\CareRequest;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * @OA\Post(
     *   path="/care-requests/{careRequest}/review",
     *   tags={"Reviews"},
     *   summary="Submit a review for a completed care request",
     *   description="PATIENT only. Only allowed after status is DONE. One review per care request.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="careRequest", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"rating"},
     *       @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
     *       @OA\Property(property="comment", type="string", nullable=true, maxLength=2000, example="Très professionnel et ponctuel.")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Review created"),
     *   @OA\Response(response=403, description="Forbidden — not the patient or not PATIENT role"),
     *   @OA\Response(response=409, description="Review already exists for this request"),
     *   @OA\Response(response=422, description="Request is not DONE or has no nurse")
     * )
     */
    public function store(StoreReviewRequest $request, CareRequest $careRequest): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'PATIENT') {
            return response()->json(['message' => 'Only patients can review'], 403);
        }

        if ($careRequest->patient_user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($careRequest->status !== 'DONE'){
            return response()->json(['message' => 'Review is allowed only after request is DONE'], 422);
        }

        if (!$careRequest->nurse_user_id){
            return response()->json(['message' => 'Cannot review a request without a nurse.'], 422);
        }

        // Ensure only one review per care request
        $existing = Review::query()->where('care_request_id', $careRequest->id)->first();
        if ($existing) {
            return response()->json(['message' => 'Review already exists for this request'], 409);
        }

        $payload = $request->validated();

        $review = Review::query()->create([
            'care_request_id' => $careRequest->id,
            'patient_user_id' => $user->id,
            'nurse_user_id' => $careRequest->nurse_user_id,
            'rating' => $payload['rating'],
            'comment' => $payload['comment'] ?? null,
        ]);

        return response()->json([
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * @OA\Get(
     *   path="/nurses/{nurseUserId}/reviews",
     *   tags={"Reviews"},
     *   summary="List reviews for a nurse (public)",
     *   description="No authentication required. Returns paginated reviews.",
     *   @OA\Parameter(name="nurseUserId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=50, default=20)),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *       @OA\Property(property="meta", type="object",
     *         @OA\Property(property="current_page", type="integer"),
     *         @OA\Property(property="last_page", type="integer"),
     *         @OA\Property(property="per_page", type="integer"),
     *         @OA\Property(property="total", type="integer")
     *       )
     *     )
     *   )
     * )
     */
    public function indexByNurse(Request $request, int $nurseUserId): JsonResponse
    {
        $perPage = (int) ($request->query('per_page') ?? 20);
        $perPage = max(1, min(50, $perPage));

        $items = Review::query()
            ->where('nurse_user_id', $nurseUserId)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => ReviewResource::collection($items)->response()->getData(true)['data'],
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *   path="/nurses/{nurseUserId}/rating",
     *   tags={"Reviews"},
     *   summary="Get aggregated rating for a nurse (public)",
     *   description="No authentication required. Returns average rating and total review count.",
     *   @OA\Parameter(name="nurseUserId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="nurse_user_id", type="integer", example=5),
     *         @OA\Property(property="rating_avg", type="number", format="double", example=4.5),
     *         @OA\Property(property="reviews_count", type="integer", example=12)
     *       )
     *     )
     *   )
     * )
     */
    public function ratingByNurse(Request $request, int $nurseUserId): JsonResponse
    {
        $agg = Review::query()
            ->where('nurse_user_id', $nurseUserId)
            ->selectRaw('COALESCE(AVG(rating),0) as rating_avg, COUNT(*) as reviews_count')
            ->first();

        return response()->json([
            'data' => [
                'nurse_user_id' => $nurseUserId,
                'rating_avg' => round((float) $agg->rating_avg,2),
                'reviews_count' => (int) $agg->reviews_count
            ],
        ]);
    }
}
