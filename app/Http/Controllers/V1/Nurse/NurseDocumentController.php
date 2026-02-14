<?php

namespace App\Http\Controllers\V1\Nurse;

use App\Http\Controllers\Controller;
use App\Models\NurseProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NurseDocumentController extends Controller
{
    /**
     * @OA\Get(
     *   path="/nurse/profile/diploma",
     *   tags={"Nurse Profile"},
     *   summary="Download nurse diploma file",
     *   description="NURSE downloads own diploma. ADMIN can specify ?user_id=XX to download any nurse's diploma. Returns the binary file.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="user_id", in="query", required=false, description="(ADMIN only) Target nurse user ID", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="File download (binary stream)", @OA\MediaType(mediaType="application/octet-stream")),
     *   @OA\Response(response=403, description="Forbidden — not NURSE or ADMIN"),
     *   @OA\Response(response=404, description="No diploma file found")
     * )
     */
    public function downloadDiploma(Request $request): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // Infirmier peut voir son propre diplôme, admin peut voir tous
        $targetUserId = $user->id;

        // Admin peut spécifier ?user_id=XX
        if ($user->role === 'ADMIN' && $request->query('user_id')) {
            $targetUserId = (int) $request->query('user_id');
        } elseif ($user->role !== 'NURSE' && $user->role !== 'ADMIN') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $profile = NurseProfile::query()->where('user_id', $targetUserId)->first();

        if (!$profile || !$profile->diploma_path) {
            return response()->json(['message' => 'No diploma file found.'], 404);
        }

        if (!Storage::disk('local')->exists($profile->diploma_path)) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        return Storage::disk('local')->download(
            $profile->diploma_path,
            'diploma_' . $targetUserId . '.' . pathinfo($profile->diploma_path, PATHINFO_EXTENSION)
        );
    }
}
