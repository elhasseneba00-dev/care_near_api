<?php

namespace App\Http\Controllers\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Nurse\UpsertNurseProfileRequest;
use App\Http\Requests\V1\Patient\UpsertPatientProfileRequest;
use App\Http\Resources\V1\Patient\UpsertPatientProfileResource;
use App\Models\PatientProfile;
use App\Services\Audit;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientProfileController extends Controller
{
    /**
     * @OA\Get(
     *   path="/patient/profile",
     *   tags={"Patient Profile"},
     *   summary="Get current patient profile",
     *   description="Returns the patient profile of the authenticated user including medical documents metadata. Returns null data if no profile exists.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", nullable=true, type="object",
     *         @OA\Property(property="user_id", type="integer", example=3),
     *         @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-05-15"),
     *         @OA\Property(property="gender", type="string", enum={"M","F","OTHER"}, nullable=true, example="M"),
     *         @OA\Property(property="city", type="string", nullable=true, example="Nouakchott"),
     *         @OA\Property(property="address", type="string", nullable=true, example="Ksar"),
     *         @OA\Property(property="lat", type="number", format="double", nullable=true),
     *         @OA\Property(property="lng", type="number", format="double", nullable=true),
     *         @OA\Property(property="medical_notes", type="string", nullable=true, example="Diabétique type 2"),
     *         @OA\Property(property="medical_documents", type="array",
     *           @OA\Items(type="object",
     *             @OA\Property(property="index", type="integer", example=0),
     *             @OA\Property(property="filename", type="string", example="1707123456_abc12345.pdf"),
     *             @OA\Property(property="url", type="string", example="http://localhost/api/v1/patient/profile/documents/0/download")
     *           )
     *         ),
     *         @OA\Property(property="documents_count", type="integer", example=2)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request) : JsonResponse{
        $user = $request->user();

        $profile = PatientProfile::query()->where('user_id', $user->id)->first();
        return response()->json([
            'data' => $profile ? new UpsertPatientProfileResource($profile) : null,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/patient/profile",
     *   tags={"Patient Profile"},
     *   summary="Create or update patient profile (with optional medical documents upload)",
     *   description="PATIENT only. Uses multipart/form-data when uploading files. Maximum 5 files per request, 10 total. Accepted formats: PDF, JPG, PNG (max 5MB each).",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         @OA\Property(property="birth_date", type="string", format="date", example="1990-05-15"),
     *         @OA\Property(property="gender", type="string", enum={"M","F","OTHER"}, example="M"),
     *         @OA\Property(property="city", type="string", maxLength=80, example="Nouakchott"),
     *         @OA\Property(property="address", type="string", maxLength=2000, example="Ksar, rue 42"),
     *         @OA\Property(property="lat", type="number", format="double", example=18.086),
     *         @OA\Property(property="lng", type="number", format="double", example=-15.978),
     *         @OA\Property(property="medical_notes", type="string", maxLength=5000, example="Diabétique type 2, sous insuline"),
     *         @OA\Property(property="medical_files[]", type="array", @OA\Items(type="string", format="binary"), description="Medical documents (PDF/JPG/PNG, max 5MB each, max 5 files)")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Profile created/updated",
     *     @OA\JsonContent(@OA\Property(property="data", type="object"))
     *   ),
     *   @OA\Response(response=403, description="Forbidden — user is not a PATIENT"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    // Upsert (create or update) patient profile
    public function upsert(UpsertPatientProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'PATIENT') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $request->validated();

        // ── Upload des documents médicaux ──
        if ($request->hasFile('medical_files')) {
            $existingProfile = PatientProfile::query()->where('user_id', $user->id)->first();
            $existingDocs = $existingProfile?->medical_documents ?? [];

            $payload['medical_documents'] = FileUploadService::uploadMultiple(
                files: $request->file('medical_files'),
                directory: "medical_documents/{$user->id}",
                existingPaths: $existingDocs,
                maxTotal: 10
            );

            Audit::log($user, 'MEDICAL_DOCUMENTS_UPLOADED', 'PatientProfile', $user->id, [
                'count' => count($request->file('medical_files')),
            ], $request);
        }

        // Retirer medical_files du payload
        unset($payload['medical_files']);

        $profile = PatientProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $payload
        );

        return response()->json([
            'data' => new UpsertPatientProfileResource($profile),
        ]);
    }


    /**
     * @OA\Delete(
     *   path="/patient/profile/documents/{index}",
     *   tags={"Patient Profile"},
     *   summary="Delete a specific medical document",
     *   description="PATIENT only. Deletes the medical document at the given index. Index is 0-based as returned by GET /patient/profile.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="index", in="path", required=true, description="0-based index of the document to delete", @OA\Schema(type="integer", minimum=0)),
     *   @OA\Response(
     *     response=200,
     *     description="Document deleted",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Document deleted."),
     *       @OA\Property(property="data", type="object")
     *     )
     *   ),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=404, description="No documents or invalid index")
     * )
     */
    public function deleteDocument(Request $request, int $index): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'PATIENT') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $profile = PatientProfile::query()->where('user_id', $user->id)->first();

        if (!$profile || empty($profile->medical_documents)) {
            return response()->json(['message' => 'No documents found.'], 404);
        }

        $docs = $profile->medical_documents;

        if (!isset($docs[$index])) {
            return response()->json(['message' => 'Document not found at this index.'], 404);
        }

        // Supprimer le fichier physique
        FileUploadService::delete($docs[$index]);

        // Retirer du tableau
        array_splice($docs, $index, 1);

        $profile->update(['medical_documents' => $docs]);

        Audit::log($user, 'MEDICAL_DOCUMENT_DELETED', 'PatientProfile', $user->id, [
            'deleted_index' => $index,
        ], $request);

        return response()->json([
            'message' => 'Document deleted.',
            'data'    => new UpsertPatientProfileResource($profile->fresh()),
        ]);
    }
}
