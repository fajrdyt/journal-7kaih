<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.',
                'data'    => null,
            ], 401);
        }

        $userRole = $user->role?->name;

        if (!$userRole || !in_array($userRole, $roles, true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin.',
                'data'    => null,
            ], 403);
        }

        return $next($request);
    }
}