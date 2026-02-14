<?php

namespace App\Http\Controllers\V1\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientDocumentController extends Controller
{
    /**
     * @OA\Get(
     *   path="/patient/profile/documents/{index}/download",
     *   tags={"Patient Profile"},
     *   summary="Download a specific medical document",
     *   description="PATIENT downloads own document by index. ADMIN can specify ?user_id=XX. Returns binary file.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="index", in="path", required=true, description="0-based document index", @OA\Schema(type="integer", minimum=0)),
     *   @OA\Parameter(name="user_id", in="query", required=false, description="(ADMIN only) Target patient user ID", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="File download (binary stream)", @OA\MediaType(mediaType="application/octet-stream")),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=404, description="Document not found")
     * )
     */
    public function downloadDocument(Request $request, int $index): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $targetUserId = $user->id;

        if ($user->role === 'ADMIN' && $request->query('user_id')) {
            $targetUserId = (int) $request->query('user_id');
        } elseif ($user->role !== 'PATIENT' && $user->role !== 'ADMIN') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $profile = PatientProfile::query()->where('user_id', $targetUserId)->first();

        if (!$profile || empty($profile->medical_files)) {
            return response()->json(['message' => 'No documents found.'], 404);
        }

        $docs = $profile->medical_files;

        if (!isset($docs[$index])) {
            return response()->json(['message' => 'Document not found at this index.'], 404);
        }

        $path = $docs[$index];

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        return Storage::disk('local')->download(
            $path,
            'medical_doc_' . $index . '.' . pathinfo($path, PATHINFO_EXTENSION)
        );
    }
}
