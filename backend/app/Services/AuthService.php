<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Login
     */
    public function login($request)
    {
        $user = User::with(['role', 'class'])
            ->where('username', $request->identifier)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | USER NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            return response()->json([

                'success' => false,

                'message' => 'User tidak ditemukan',

                'data' => null

            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | PASSWORD INVALID
        |--------------------------------------------------------------------------
        */

        if (!Hash::check($request->password, $user->password)) {

            return response()->json([

                'success' => false,

                'message' => 'Password salah',

                'data' => null

            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | ACCOUNT NOT ACTIVE
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {

            return response()->json([

                'success' => false,

                'message' => 'Akun tidak aktif',

                'data' => null

            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE TOKEN
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken('auth_token')->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' => 'Login successful',

            'data' => [

                'access_token' => $token,

                'token_type' => 'Bearer',

                'expires_in' => 86400,

                'user' => [

                    'id' => $user->id,

                    'full_name' => $user->full_name,

                    'username' => $user->username,

                    'email' => $user->email,

                    'phone' => $user->phone,

                    'role' => [

                        'id' => $user->role?->id,

                        'name' => $user->role?->name,
                    ],

                    'class' => $user->class ? [

                        'id' => $user->class->id,

                        'name' => $user->class->name,

                        'grade_level' => $user->class->grade_level,

                    ] : null
                ]
            ]
        ]);
    }

    /**
     * Current user
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['role', 'class']);

        return response()->json([

            'success' => true,

            'message' => 'Success',

            'data' => [

                'id' => $user->id,

                'full_name' => $user->full_name,

                'username' => $user->username,

                'email' => $user->email,

                'phone' => $user->phone,

                'role' => [

                    'id' => $user->role?->id,

                    'name' => $user->role?->name,
                ],

                'class' => $user->class ? [

                    'id' => $user->class->id,

                    'name' => $user->class->name,

                    'grade_level' => $user->class->grade_level,

                ] : null
            ]
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([

            'success' => true,

            'message' => 'Success',

            'data' => null
        ]);
    }
}