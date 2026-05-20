<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * LOGIN
     */
    public function login($request)
    {
        $user = User::with(['role', 'class'])
            ->where('username', $request->identifier)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
                'data' => null
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah',
                'data' => null
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif',
                'data' => null
            ], 403);
        }

        // 🔥 PENTING: HAPUS TOKEN LAMA (biar ga numpuk & debug lebih gampang)
        $user->tokens()->delete();

        // 🔥 CREATE TOKEN SANCTUM
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]
        ]);
    }

    /**
     * CURRENT USER (ME)
     */
    public function me(Request $request)
    {
        // 🔥 FIX: guard safety
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => null
            ], 401);
        }

        $user = $request->user()->load(['role', 'class']);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $user
        ]);
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        // 🔥 FIX: safety check
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => null
            ], 401);
        }

        // 🔥 HAPUS TOKEN YANG SEDANG DIPAKAI
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout success',
            'data' => null
        ]);
    }
}