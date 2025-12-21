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

        if ($careRequest->nurse_user_id){
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
