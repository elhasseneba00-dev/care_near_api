<?php

namespace App\Http\Controllers\V1\Nurse;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Nurse\UpsertNurseProfileRequest;
use App\Http\Resources\V1\Nurse\UpsertNurseProfileResource;
use App\Models\NurseProfile;
use App\Services\Audit;
use App\Services\FileUploadService;
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

    public function upsert(UpsertNurseProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'NURSE') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $request->validated();

        // Interdire l'auto-vérification
        unset($payload['verified']);

        // ── Upload du diplôme ──
        if ($request->hasFile('diploma_file')) {
            $existingProfile = NurseProfile::query()->where('user_id', $user->id)->first();

            $payload['diploma_path'] = FileUploadService::uploadSingle(
                file: $request->file('diploma_file'),
                directory: "diplomas/{$user->id}",
                oldPath: $existingProfile?->diploma_path
            );

            // Quand un nouveau diplôme est uploadé, remettre verified à false
            // (l'admin devra re-vérifier)
            $payload['verified'] = false;

            Audit::log($user, 'DIPLOMA_UPLOADED', 'NurseProfile', $user->id, [
                'path' => $payload['diploma_path'],
            ], $request);
        }

        // Retirer diploma_file du payload (ce n'est pas un champ de la table)
        unset($payload['diploma_file']);

        $profile = NurseProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $payload
        );

        return response()->json([
            'data' => new UpsertNurseProfileResource($profile),
        ]);
    }
}
