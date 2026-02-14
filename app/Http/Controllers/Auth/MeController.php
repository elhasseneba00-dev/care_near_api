<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * @OA\Get(
     *   path="/me",
     *   tags={"Auth"},
     *   summary="Get current authenticated user with profile",
     *   description="Returns the authenticated user data. If NURSE, includes nurseProfile. If PATIENT, includes patientProfile.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="full_name", type="string", example="Ahmed Ould Mohamed"),
     *         @OA\Property(property="phone", type="string", example="22233334444"),
     *         @OA\Property(property="role", type="string", enum={"PATIENT","NURSE","ADMIN"}, example="PATIENT"),
     *         @OA\Property(property="status", type="string", enum={"ACTIVE","SUSPENDED"}, example="ACTIVE"),
     *         @OA\Property(property="nurse_profile", type="object", nullable=true),
     *         @OA\Property(property="patient_profile", type="object", nullable=true)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Eager load the relevant profile based on role
        if ($user->isNurse()) {
            $user->load('nurseProfile');
        } elseif ($user->isPatient()) {
            $user->load('patientProfile');
        }

        return response()->json(['data' => $user]);
    }
}
