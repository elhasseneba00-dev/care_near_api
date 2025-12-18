<?php

namespace App\Http\Controllers\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Nurse\UpsertNurseProfileRequest;
use App\Http\Resources\V1\Patient\UpsertPatientProfileResource;
use App\Models\PatientProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientProfileController extends Controller
{
    public function show(Request $request) : JsonResponse{
        $user = $request->user();

        $profile = PatientProfile::query()->where('user_id', $user->id)->first();
        return response()->json([
            'data' => $profile ? new UpsertPatientProfileResource($profile) : null,
        ]);
    }

    // Upsert (create or update) patient profile
    public function upsert(UpsertNurseProfileRequest $request) : JsonResponse{
        $user = $request->user();
         //MVP: allow only patient role to edit patient profile
        if ($user->role !== 'PATIENT') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $profile = PatientProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return response()->json([
            'data' => new UpsertPatientProfileResource($profile),
        ]);
    }
}
