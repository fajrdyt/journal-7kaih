<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\RecapService;
use Illuminate\Http\Request;

class RecapController extends Controller
{
    public function __construct(
        protected RecapService $recapService
    ) {}

    /**
     * GET /api/v1/student/recap
     */
    public function personal(Request $request)
    {
        return $this->recapService->personal($request);
    }

    /**
     * GET /api/v1/parent/children/{studentId}/recap
     */
    public function childRecap(Request $request, int $studentId)
    {
        return $this->recapService->childRecap($request, $studentId);
    }

    /**
     * GET /api/v1/teacher/students/{studentId}/recap
     */
    public function studentRecap(Request $request, int $studentId)
    {
        return $this->recapService->studentRecap($request, $studentId);
    }

    /**
     * GET /api/v1/teacher/classes/{classId}/weekly-recap
     */
    public function classWeeklyRecap(Request $request, int $classId)
    {
        return $this->recapService->classWeeklyRecap($request, $classId);
    }

    /**
     * GET /api/v1/teacher/classes/{classId}/monthly-recap
     */
    public function classMonthlyRecap(Request $request, int $classId)
    {
        return $this->recapService->classMonthlyRecap($request, $classId);
    }
    public function studentHabitStatistics(Request $request)
    {
    return $this->recapService->studentHabitStatistics($request);
    }

    public function teacherClassHabitStatistics(Request $request, int $classId)
    {
    return $this->recapService->teacherClassHabitStatistics($request, $classId);
    }
}