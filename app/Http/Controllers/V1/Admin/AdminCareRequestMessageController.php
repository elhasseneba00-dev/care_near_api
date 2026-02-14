<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Chat\MessageResource;
use App\Models\CareRequest;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCareRequestMessageController extends Controller
{
    // openApi documentation:
    /**
     * @OA\Get(
     *     path="/admin/care-requests/{careResquest}/messages",
     *     summary="Get messages for a care request",
     *     tags={"Admin Care Requests"},
     *     @OA\Parameter(
     *         name="careRequest",
     *         in="path",
     *         description="ID of the care request",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of messages to return (default: 200, max: 500)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=500)
     *     ),
     *     @OA\Parameter(
     *         name="after_id",
     *         in="query",
     *         description="Return messages with ID greater than this value (for pagination)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with list of messages",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     * )
    */
    public function index(Request $request, CareRequest $careRequest): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'after_id' => ['nullable', 'integer', 'min:1']
        ]);

        $limit = (int) ($validated['limit'] ?? 200);
        $afterId = $validated['after_id'] ?? null;

        $query = Message::query()
            ->where('care_request_id', $careRequest->id)
            ->orderBy('id', 'asc');

        if ($afterId){
            $query->where('id', '>', (int) $afterId);
        }

        $messages = $query->limit($limit)->get();

        return response()->json([
            'data' => MessageResource::collection($messages),
        ]);
    }
}
