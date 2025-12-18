<?php

namespace App\Http\Controllers\V1\Nurse;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Nurse\UpsertNurseProfileRequest;
use App\Http\Resources\V1\Nurse\UpsertNurseProfileResource;
use App\Models\NurseProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NurseProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = NurseProfile::query()->where('user_id', $user->id)->first();
        return response()->json([
            'data' => $profile ? new UpsertNurseProfileResource($profile) : null,
        ]);
    }

    public function upsert(UpsertNurseProfileRequest $request): JsonResponse{
        $user = $request->user();

        if ($user->role !== 'NURSE') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $payload = $request->validated();

        //Do not allow self-verify
        unset($payload['verified']);

        $profile = NurseProfile::query()->updateOrCreate(['user_id' => $user->id], $payload);
        return response()->json([
            'data' => new UpsertNurseProfileResource($profile),
        ]);
    }
}
