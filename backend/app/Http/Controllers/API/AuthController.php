<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request)
    {
        return $this->authService->login($request);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request)
    {
        return $this->authService->me($request);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request)
    {
        return $this->authService->logout($request);
    }

    /**
     * GET /api/v1/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load(['role', 'classRoom']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diambil.',
            'data'    => $this->formatProfile($user),
        ]);
    }

    /**
     * PUT /api/v1/profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => ['nullable', 'string', 'max:255'],
            'username'  => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email'     => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone'     => ['nullable', 'string', 'max:20'],
        ]);

        if (isset($validated['full_name'])) {
            $validated['name'] = $validated['full_name'];
        }

        $user->update($validated);

        $user = $user->fresh(['role', 'classRoom']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $this->formatProfile($user),
        ]);
    }

    /**
     * PUT /api/v1/profile/password
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Password lama tidak sesuai.',
                'data'    => null,
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password berhasil diperbarui.',
            'data'    => null,
        ]);
    }

    private function formatProfile(User $user): array
    {
        return [
            'id'        => $user->id,
            'full_name' => $user->full_name,
            'username'  => $user->username,
            'email'     => $user->email,
            'phone'     => $user->phone,
            'is_active' => $user->is_active,

            'role' => $user->role ? [
                'id'   => $user->role->id,
                'name' => $user->role->name,
            ] : null,

            'class' => $user->classRoom ? [
                'id'          => $user->classRoom->id,
                'name'        => $user->classRoom->name,
                'grade_level' => $user->classRoom->grade_level,
            ] : null,
        ];
    }
}