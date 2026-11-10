<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', Rule::in(['client', 'vendor', 'venue'])],
            'business_name' => ['required_if:role,vendor,venue', 'nullable', 'string', 'max:255'],
            'category' => ['required_if:role,vendor,venue', 'nullable', 'string', 'max:255'],
            'address' => ['required_if:role,vendor,venue', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => $validated['role'] ?? 'client',
        ]);

        if (in_array($user->role, ['vendor', 'venue'], true)) {
            $user->vendorProfile()->create([
                'business_name' => $validated['business_name'],
                'category' => $validated['category'],
                'address' => $validated['address'],
                'description' => $validated['description'] ?? null,
            ]);
        }

        return response()->json([
            'user' => $user->load('vendorProfile'),
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password tidak sesuai.'], 422);
        }

        return response()->json([
            'user' => $user->load('vendorProfile'),
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('vendorProfile'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);

        $request->user()->update($validated);

        return response()->json($request->user()->fresh()->load('vendorProfile'));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json(['message' => 'Password saat ini tidak sesuai.'], 422);
        }

        $request->user()->update(['password' => $validated['password']]);

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }
}
