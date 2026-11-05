<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    /**
     * Menukar email & password dengan token Sanctum untuk aplikasi mobile.
     * Kirim token pada header `Authorization: Bearer {token}`.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();
        $token = $user->createToken($request->input('device_name') ?: 'mobile');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Mencabut token yang sedang dipakai (perangkat lain tetap login).
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
