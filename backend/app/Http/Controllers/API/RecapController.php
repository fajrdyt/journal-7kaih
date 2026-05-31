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

    public function personal(Request $request)
    {
        return $this->recapService->personal($request);
    }

    public function childRecap(Request $request, int $studentId)
    {
        return $this->recapService->childRecap($request, $studentId);
    }

    public function studentRecap(Request $request, int $studentId)
    {
        return $this->recapService->studentRecap($request, $studentId);
    }

    public function classWeeklyRecap(Request $request, int $classId)
    {
        return $this->recapService->classWeeklyRecap($request, $classId);
    }

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