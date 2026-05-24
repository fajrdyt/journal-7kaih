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
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        $data = $this->recapService->getStudentRecap(
            studentId: $request->user()->id,
            startDate: $request->query('start_date'),
            endDate: $request->query('end_date')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap personal siswa berhasil diambil.',
            'data'    => $data,
        ]);
    }

    public function childRecap(Request $request, int $studentId)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        $this->recapService->ensureParentCanAccessStudent(
            parentId: $request->user()->id,
            studentId: $studentId
        );

        $data = $this->recapService->getStudentRecap(
            studentId: $studentId,
            startDate: $request->query('start_date'),
            endDate: $request->query('end_date')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap anak berhasil diambil.',
            'data'    => $data,
        ]);
    }

    public function classWeeklyRecap(Request $request, int $classId)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        $data = $this->recapService->getClassWeeklyRecap(
            teacherId: $request->user()->id,
            classId: $classId,
            startDate: $request->query('start_date'),
            endDate: $request->query('end_date')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap mingguan kelas berhasil diambil.',
            'data'    => $data,
        ]);
    }

    public function classMonthlyRecap(Request $request, int $classId)
    {
        $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $data = $this->recapService->getClassMonthlyRecap(
            teacherId: $request->user()->id,
            classId: $classId,
            month: $request->query('month')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap bulanan kelas berhasil diambil.',
            'data'    => $data,
        ]);
    }

    public function studentRecap(Request $request, int $studentId)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        $this->recapService->ensureTeacherCanAccessStudent(
            teacherId: $request->user()->id,
            studentId: $studentId
        );

        $data = $this->recapService->getStudentRecap(
            studentId: $studentId,
            startDate: $request->query('start_date'),
            endDate: $request->query('end_date')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap siswa berhasil diambil.',
            'data'    => $data,
        ]);
    }
}