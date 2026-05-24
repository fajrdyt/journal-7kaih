<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CheckinController;
use App\Http\Controllers\API\HabitController;
use App\Http\Controllers\API\ClassController;  
use App\Models\Role;

Route::get('/test', function () {
    return response()->json(['message' => 'API works!']);
});

Route::prefix('v1')->group(function () {

    // ── Auth ──────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me',      [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // ── Protected ─────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Master Data — roles (tidak ada RoleController di root project)
        Route::get('/roles', function () {
            return response()->json([
                'status'  => 'success',
                'message' => 'Daftar role berhasil diambil.',
                'data'    => Role::all(['id', 'name']),
            ]);
        });

        // Master Data — habits & classes
        Route::get('/habits',  [HabitController::class, 'index']);
        Route::get('/classes', [ClassController::class, 'index']);

        // Journal — khusus student
        Route::middleware('role:student')->prefix('checkins')->group(function () {
            Route::get('/',             [CheckinController::class, 'index']);
            Route::get('/today-status', [CheckinController::class, 'todayStatus']);
            Route::post('/',            [CheckinController::class, 'store']);
            Route::get('/{id}',         [CheckinController::class, 'show']);
        });
    });
});