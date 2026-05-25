<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ValidationService;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    public function __construct(
        protected ValidationService $validationService
    ) {}

    // ── Parent ────────────────────────────────────────────────

    /**
     * GET /api/v1/parent/children
     */
    public function children(Request $request)
    {
        return $this->validationService->children($request);
    }

    /**
     * GET /api/v1/parent/children/{studentId}/checkins
     */
    public function childCheckins(Request $request, int $studentId)
    {
        return $this->validationService->childCheckins($request, $studentId);
    }

    /**
     * GET /api/v1/parent/checkins/{id}
     */
    public function parentCheckinDetail(Request $request, int $id)
    {
        return $this->validationService->parentCheckinDetail($request, $id);
    }

    /**
     * POST /api/v1/parent/checkins/{id}/validate-home
     */
    public function validateHome(Request $request, int $id)
    {
        return $this->validationService->validateHome($request, $id);
    }

    /**
     * POST /api/v1/parent/checkin-items/{id}/validate
     */
    public function validateHomeItem(Request $request, int $id)
    {
        return $this->validationService->validateHomeItem($request, $id);
    }

    // ── Teacher ───────────────────────────────────────────────

    /**
     * GET /api/v1/teacher/checkins/{id}
     */
    public function teacherCheckinDetail(Request $request, int $id)
    {
        return $this->validationService->teacherCheckinDetail($request, $id);
    }

    /**
     * POST /api/v1/teacher/checkins/{id}/validate-school
     */
    public function validateSchool(Request $request, int $id)
    {
        return $this->validationService->validateSchool($request, $id);
    }

    /**
     * POST /api/v1/teacher/checkin-items/{id}/validate
     */
    public function validateSchoolItem(Request $request, int $id)
    {
        return $this->validationService->validateSchoolItem($request, $id);
    }
}