<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CheckinController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\HabitController;
use App\Http\Controllers\API\RecapController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ValidationController;
use App\Models\Role;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/roles', fn () => response()->json([
            'status'  => 'success',
            'message' => 'Daftar role berhasil diambil.',
            'data'    => Role::all(['id', 'name']),
        ]));

        Route::get('/habits', [HabitController::class, 'index']);
        Route::get('/classes', [ClassController::class, 'index']);

        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/profile/password', [AuthController::class, 'updatePassword']);

        Route::post('/events/track', function (Request $request, AnalyticsService $analyticsService) {
            $validated = $request->validate([
                'event_name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::in([
                        'dashboard_open',
                        'profile_open',
                        'report_open',
                        'checkin_form_open',
                        'recap_open',
                        'habit_statistics_open',
                    ]),
                ],
                'properties'   => ['nullable', 'array', 'max:20'],
                'properties.*' => ['nullable'],
            ]);

            $event = $analyticsService->trackEvent(
                userId: $request->user()->id,
                eventName: $validated['event_name'],
                properties: $validated['properties'] ?? null
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Event berhasil dicatat.',
                'data'    => [
                    'id'         => $event->id,
                    'user_id'    => $event->user_id,
                    'event_name' => $event->event_name,
                    'properties' => $event->properties,
                    'created_at' => $event->created_at,
                ],
            ], 201);
        })->middleware('throttle:30,1');

        Route::middleware('role:admin')->prefix('admin')->group(function () {

            Route::get('/dashboard-summary', [UserController::class, 'dashboardSummary']);

            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
            Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);

            Route::get('/classes', [ClassController::class, 'index']);
            Route::post('/classes', [ClassController::class, 'store']);
            Route::get('/classes/{id}', [ClassController::class, 'show']);
            Route::put('/classes/{id}', [ClassController::class, 'update']);
            Route::delete('/classes/{id}', [ClassController::class, 'destroy']);

            Route::get('/student-parent-relations', [UserController::class, 'studentParentRelations']);
            Route::post('/student-parent-relations', [UserController::class, 'storeStudentParentRelation']);
            Route::get('/student-parent-relations/{id}', [UserController::class, 'showStudentParentRelation']);
            Route::put('/student-parent-relations/{id}', [UserController::class, 'updateStudentParentRelation']);
            Route::delete('/student-parent-relations/{id}', [UserController::class, 'deleteStudentParentRelation']);
        });

        Route::middleware('role:siswa')->prefix('student')->group(function () {
            Route::get('/checkins/today', [CheckinController::class, 'today']);
            Route::get('/checkins', [CheckinController::class, 'index']);
            Route::post('/checkins', [CheckinController::class, 'store']);
            Route::get('/checkins/{id}', [CheckinController::class, 'show']);

            Route::get('/recap', [RecapController::class, 'personal']);
            Route::get('/habit-statistics', [RecapController::class, 'studentHabitStatistics']);
        });

        Route::middleware('role:orang_tua')->prefix('parent')->group(function () {
            Route::get('/children', [ValidationController::class, 'children']);
            Route::get('/children/{studentId}/checkins', [ValidationController::class, 'childCheckins']);

            Route::get('/children/{studentId}/recap', [RecapController::class, 'childRecap']);

            Route::get('/checkins/{id}', [ValidationController::class, 'parentCheckinDetail']);
            Route::post('/checkins/{id}/validate-home', [ValidationController::class, 'validateHome']);
            Route::post('/checkin-items/{id}/validate', [ValidationController::class, 'validateHomeItem']);
        });

        Route::middleware('role:guru')->prefix('teacher')->group(function () {
            Route::get('/classes', [ClassController::class, 'teacherClasses']);
            Route::get('/classes/{classId}/students', [ClassController::class, 'classStudents']);
            Route::get('/classes/{classId}/checkins', [ClassController::class, 'classCheckins']);

            Route::get('/classes/{classId}/weekly-recap', [RecapController::class, 'classWeeklyRecap']);
            Route::get('/classes/{classId}/monthly-recap', [RecapController::class, 'classMonthlyRecap']);
            Route::get('/classes/{classId}/habit-statistics', [RecapController::class, 'teacherClassHabitStatistics']);
            Route::get('/students/{studentId}/recap', [RecapController::class, 'studentRecap']);

            Route::get('/checkins/{id}', [ValidationController::class, 'teacherCheckinDetail']);
            Route::post('/checkins/{id}/validate-school', [ValidationController::class, 'validateSchool']);
            Route::post('/checkin-items/{id}/validate', [ValidationController::class, 'validateSchoolItem']);
        });
    });
});