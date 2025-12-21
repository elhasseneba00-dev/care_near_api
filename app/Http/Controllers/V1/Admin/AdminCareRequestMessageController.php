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
