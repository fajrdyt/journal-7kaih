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

    public function children(Request $request)
    {
        return $this->validationService->children($request);
    }

    public function childCheckins(Request $request, int $studentId)
    {
        return $this->validationService->childCheckins($request, $studentId);
    }

    public function parentCheckinDetail(Request $request, int $id)
    {
        return $this->validationService->parentCheckinDetail($request, $id);
    }

    public function validateHome(Request $request, int $id)
    {
        return $this->validationService->validateHome($request, $id);
    }

    public function validateHomeItem(Request $request, int $id)
    {
        return $this->validationService->validateHomeItem($request, $id);
    }

    public function teacherCheckinDetail(Request $request, int $id)
    {
        return $this->validationService->teacherCheckinDetail($request, $id);
    }

    public function validateSchool(Request $request, int $id)
    {
        return $this->validationService->validateSchool($request, $id);
    }

    public function validateSchoolItem(Request $request, int $id)
    {
        return $this->validationService->validateSchoolItem($request, $id);
    }
}