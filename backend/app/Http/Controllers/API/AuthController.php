<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request)
    {
        return $this->authService->login($request);
    }

    public function me(Request $request)
    {
        return $this->authService->me($request);
    }

    public function logout(Request $request)
    {
        return $this->authService->logout($request);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'role',
            'classRoom',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diambil.',
            'data' => $this->formatProfile($user),
        ]);
    }

    public function updateProfile(
        Request $request
    ): JsonResponse {
        $user = $request->user()->loadMissing('role');

        $validated = $request->validate([
            'full_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'username' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username')
                    ->ignore($user->id),
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'nisn' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^\d{10}$/',
                Rule::unique('users', 'nisn')
                    ->ignore($user->id),
            ],
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'nisn.regex' => 'NISN harus terdiri dari tepat 10 digit angka.',
            'nisn.unique' => 'NISN sudah digunakan oleh siswa lain.',
        ]);

        if ($user->role?->name !== 'siswa') {
            unset($validated['nisn']);
        }

        if (array_key_exists('full_name', $validated)) {
            $validated['full_name'] = filled(
                $validated['full_name']
            )
                ? trim($validated['full_name'])
                : null;

            $validated['name'] = $validated['full_name'];
        }

        if (array_key_exists('username', $validated)) {
            $validated['username'] = filled(
                $validated['username']
            )
                ? trim($validated['username'])
                : null;
        }

        if (array_key_exists('email', $validated)) {
            $validated['email'] = filled(
                $validated['email']
            )
                ? trim($validated['email'])
                : null;
        }

        if (array_key_exists('phone', $validated)) {
            $validated['phone'] = filled(
                $validated['phone']
            )
                ? trim($validated['phone'])
                : null;
        }

        if (array_key_exists('nisn', $validated)) {
            $validated['nisn'] = filled(
                $validated['nisn']
            )
                ? trim($validated['nisn'])
                : null;
        }

        $user->update($validated);

        $user = $user->fresh([
            'role',
            'classRoom',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => $this->formatProfile($user),
        ]);
    }

    public function updatePassword(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        if (
            ! Hash::check(
                $validated['current_password'],
                $user->password
            )
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password lama tidak sesuai.',
                'data' => null,
            ], 422);
        }

        $user->update([
            'password' => $validated['new_password'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diperbarui.',
            'data' => null,
        ]);
    }

    private function formatProfile(User $user): array
    {
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
                'grade_level' => $user->classRoom->grade_level,
            ] : null,
        ];
    }
}