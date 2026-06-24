<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(
        LoginRequest $request
    ): JsonResponse {
        $user = User::query()
            ->with([
                'role',
                'classRoom',
            ])
            ->where(function ($query) use ($request) {
                $query
                    ->where(
                        'username',
                        $request->identifier
                    )
                    ->orWhere(
                        'email',
                        $request->identifier
                    );
            })
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        if (
            ! Hash::check(
                $request->password,
                $user->password
            )
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password salah.',
                'data' => null,
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak aktif.',
                'data' => null,
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    public function me(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        $user->loadMissing([
            'role',
            'classRoom',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data user berhasil diambil.',
            'data' => $this->formatUser($user),
        ]);
    }

    public function logout(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        $user->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.',
            'data' => null,
        ]);
    }

    private function formatUser(
        User $user
    ): array {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'nisn' => $user->nisn,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'is_active' => $user->is_active,

            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
            ] : null,

            'class' => $user->classRoom ? [
                'id' => $user->classRoom->id,
                'name' => $user->classRoom->name,
                'grade_level' => $user
                    ->classRoom
                    ->grade_level,
            ] : null,
        ];
    }
}