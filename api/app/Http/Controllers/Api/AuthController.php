<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\GoogleTokenVerifier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function google(Request $request, GoogleTokenVerifier $googleTokenVerifier): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token' => ['required', 'string', 'max:10000']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid request.',
                'errors' => $validator->errors()
            ], 422);
        }

        $payload = $googleTokenVerifier->verify($request->input('id_token'));

        if (
            ! is_array($payload)
            || empty($payload['sub'])
            || empty($payload['email'])
            || !filter_var(
                $payload['email_verified'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            )
        ) {
            return response()->json([
                'message' => 'Invalid or unverified Google account.'
            ], 401);
        }

        $user = DB::transaction(function () use ($payload) {
            $user = User::query()->where('google_id', $payload['sub'])->first();

            if ($user) {
                return $user;
            }

            $user = User::query()->where('email', $payload['email'])->first();

            if ($user) {
                $user->forceFill([
                    'google_id' => $payload['sub'],
                    'email_verified_at' => $user->email_verified_at ?? now()
                ])->save();

                return $user;
            }

            return User::create([
                'name' => $payload['name'] ?? 'BirdDex User',
                'email' => $payload['email'],
                'google_id' => $payload['sub'],
                'avatar' => $payload['picture'] ?? null,
                'password' => Hash::make(Str::random(24)),
                'email_verified_at' => now()
            ]);
        });

        $token = $user->createToken(
            'birddex-web',
            ['*'],
            now()->addDays(30)
        );

        return response()->json([
            'message' => 'Authenticated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar
            ],
            'token' => $token->plainTextToken
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }
}
