<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CheckinController;
use App\Http\Controllers\API\HabitController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\ValidationController;
# use App\Http\Controllers\API\RecapController;
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

        // Master Data
        Route::get('/roles', fn() => response()->json([
            'status'  => 'success',
            'message' => 'Daftar role berhasil diambil.',
            'data'    => Role::all(['id', 'name']),
        ]));
        Route::get('/habits',  [HabitController::class, 'index']);
        Route::get('/classes', [ClassController::class, 'index']);

        // ── Student ───────────────────────────────────────────
        Route::middleware('role:siswa')->prefix('student')->group(function () {
            Route::get('/checkins/today',  [CheckinController::class, 'today']);
            Route::get('/checkins',        [CheckinController::class, 'index']);
            Route::post('/checkins',       [CheckinController::class, 'store']);
            Route::get('/checkins/{id}',   [CheckinController::class, 'show']);
#            Route::get('/recap',           [RecapController::class,   'personal']);
        });

        // ── Parent ────────────────────────────────────────────
        Route::middleware('role:orang_tua')->prefix('parent')->group(function () {
            Route::get('/children',                                [ValidationController::class, 'children']);
            Route::get('/children/{studentId}/checkins',           [ValidationController::class, 'childCheckins']);
            Route::get('/checkins/{id}',                           [ValidationController::class, 'parentCheckinDetail']);
            Route::post('/checkins/{id}/validate-home',            [ValidationController::class, 'validateHome']);
            Route::post('/checkin-items/{id}/validate',            [ValidationController::class, 'validateHomeItem']);
        });

        // ── Teacher ───────────────────────────────────────────
        Route::middleware('role:guru')->prefix('teacher')->group(function () {
            Route::get('/classes',                                 [ClassController::class,      'teacherClasses']);
            Route::get('/classes/{classId}/students',              [ClassController::class,      'classStudents']);
            Route::get('/classes/{classId}/checkins',              [ClassController::class,      'classCheckins']);
            Route::get('/checkins/{id}',                           [ValidationController::class, 'teacherCheckinDetail']);
            Route::post('/checkins/{id}/validate-school',          [ValidationController::class, 'validateSchool']);
            Route::post('/checkin-items/{id}/validate',            [ValidationController::class, 'validateSchoolItem']);
        });
    });
});