<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * @OA\Post(
     *   path="/register",
     *   tags={"Auth"},
     *   summary="Register a new user (PATIENT or NURSE)",
     *   description="Creates a new account and returns a Sanctum Bearer token. No authentication required.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"full_name","phone","password","password_confirmation","role"},
     *       @OA\Property(property="full_name", type="string", maxLength=150, example="Ahmed Ould Mohamed"),
     *       @OA\Property(property="phone", type="string", maxLength=30, example="22233334444"),
     *       @OA\Property(property="email", type="string", format="email", nullable=true, example="ahmed@example.com"),
     *       @OA\Property(property="password", type="string", minLength=8, example="password123"),
     *       @OA\Property(property="password_confirmation", type="string", example="password123"),
     *       @OA\Property(property="role", type="string", enum={"PATIENT","NURSE"}, example="PATIENT")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="User created successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="token_type", type="string", example="Bearer"),
     *       @OA\Property(property="access_token", type="string", example="1|abc123xyz..."),
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="user", type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="full_name", type="string", example="Ahmed Ould Mohamed"),
     *           @OA\Property(property="phone", type="string", example="22233334444"),
     *           @OA\Property(property="role", type="string", example="PATIENT"),
     *           @OA\Property(property="status", type="string", example="ACTIVE")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validation error (phone taken, passwords don't match, etc.)")
     * )
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'], // already normalized in request
            'email' => $data['email'] ?? null,
            'role' => $data['role'],
            'status' => 'ACTIVE',
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'data' => [
                'user' => $user,
            ],
        ], 201);
    }
}
