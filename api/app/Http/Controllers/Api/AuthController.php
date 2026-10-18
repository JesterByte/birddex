<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\GoogleTokenVerifier;
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
            || ! is_string($payload['sub'] ?? null)
            || $payload['sub'] === ''
            || ! filter_var($payload['email'] ?? null, FILTER_VALIDATE_EMAIL)
            || ($payload['email_verified'] ?? false) !== true
        ) {
            return response()->json([
                'message' => 'Invalid or unverified Google account.',
                'code' => 'invalid_google_identity',
            ], 401);
        }

        $payload['email'] = strtolower(trim($payload['email']));

        $user = DB::transaction(function () use ($payload) {
            $user = User::query()->where('google_id', $payload['sub'])->first();

            if ($user) {
                return ['user' => $user, 'conflict' => false];
            }

            $existingEmailUser = User::query()->where('email', $payload['email'])->first();

            if ($existingEmailUser) {
                return ['user' => null, 'conflict' => true];
            }

            $user = User::create([
                'name' => $payload['name'] ?? 'BirdDex User',
                'email' => $payload['email'],
                'google_id' => $payload['sub'],
                'avatar' => $payload['picture'] ?? null,
                'password' => Str::random(64),
                'email_verified_at' => now()
            ]);

            return ['user' => $user, 'conflict' => false];
        });

        if ($user['conflict']) {
            return response()->json([
                'message' => 'An account with this email already exists. '
                    . 'Sign in using its existing method or contact support.',
                'code' => 'account_linking_required',
            ], 409);
        }

        $user = $user['user'];

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
