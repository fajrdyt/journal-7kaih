<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CheckinController;
use App\Http\Controllers\API\HabitController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\RecapController;   // → feature/backend-report
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

        // Master Data — semua role
        Route::get('/roles',   fn() => response()->json([
            'status'  => 'success',
            'message' => 'Daftar role berhasil diambil.',
            'data'    => Role::all(['id', 'name']),
        ]));
        Route::get('/habits',  [HabitController::class,  'index']);
        Route::get('/classes', [ClassController::class,  'index']);

        // ── Student Endpoints ──────────────────────────────────
        Route::middleware('role:student')->prefix('student')->group(function () {

            // GET  /api/v1/student/checkins/today  — Form + data checkin hari ini
            // ⚠ Didaftarkan SEBELUM /{id}
            Route::get('/checkins/today',   [CheckinController::class, 'today']);

            // GET  /api/v1/student/checkins        — Riwayat checkin
            Route::get('/checkins',         [CheckinController::class, 'index']);

            // POST /api/v1/student/checkins        — Upsert checkin harian
            Route::post('/checkins',        [CheckinController::class, 'store']);

            // GET  /api/v1/student/checkins/{id}   — Detail checkin
            Route::get('/checkins/{id}',    [CheckinController::class, 'show']);

            // GET  /api/v1/student/recap           — Rekap personal (→ backend-report)
            Route::get('/recap',            [RecapController::class,   'personal']);
        });
    });
});