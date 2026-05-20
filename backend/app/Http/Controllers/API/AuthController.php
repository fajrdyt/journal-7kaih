<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Login user
     */
    public function login(LoginRequest $request)
    {
        return $this->authService->login($request);
    }

    /**
     * Current authenticated user
     */
    public function me(Request $request)
    {
        return $this->authService->me($request);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        return $this->authService->logout($request);
    }
}