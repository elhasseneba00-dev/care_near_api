<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Care\CareRequestResource;
use App\Models\CareRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCareRequestController extends Controller
{
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
