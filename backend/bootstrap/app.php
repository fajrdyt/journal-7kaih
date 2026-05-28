<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->alias([
            'role'            => \App\Http\Middleware\RoleMiddleware::class,
            'parent.relation' => \App\Http\Middleware\EnsureParentRelation::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {

        $isApiRequest = function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        };

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.',
                'data'    => null,
            ], 401);
        });

        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin.',
                'data'    => null,
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Data atau endpoint tidak ditemukan.',
                'data'    => null,
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            $statusCode = $e->getStatusCode();

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage() ?: 'Terjadi kesalahan pada request.',
                'data'    => null,
            ], $statusCode);
        });

        $exceptions->render(function (\Throwable $e, Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            $response = [
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada server.',
                'data'    => null,
            ];

            if (config('app.debug')) {
                $response['debug'] = [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ];
            }

            return response()->json($response, 500);
        });
    })->create();