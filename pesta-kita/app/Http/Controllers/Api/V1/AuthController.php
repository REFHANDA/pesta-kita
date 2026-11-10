<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($validated);

        return $this->issueToken($user, 'Registered', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return $this->issueToken($user, 'Logged in', 200);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    private function issueToken(User $user, string $message, int $status): JsonResponse
    {
        $plainTextToken = Str::random(80);

        $user->apiTokens()->create([
            'name' => 'api',
            'token_hash' => hash('sha256', $plainTextToken),
        ]);

        return response()->json([
            'message' => $message.'.',
            'data' => [
                'user' => $user,
                'token' => $plainTextToken,
                'token_type' => 'Bearer',
            ],
        ], $status);
    }
}
