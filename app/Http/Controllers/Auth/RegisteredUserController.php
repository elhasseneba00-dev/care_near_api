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
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
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
