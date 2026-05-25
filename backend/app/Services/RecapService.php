<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\DailyCheckin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecapService
{
    /**
     * GET /api/v1/student/recap
     */
    public function personal(Request $request)
    {
        $student = $request->user();

        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', now()->toDateString());

        $data = $this->buildStudentRecap(
            studentId: $student->id,
            startDate: $startDate,
            endDate: $endDate
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap personal berhasil diambil.',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/v1/parent/children/{studentId}/recap
     */
    public function childRecap(Request $request, int $studentId)
    {
        $parent = $request->user();

        $hasAccess = $parent->parentRelations()
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke rekap siswa ini.',
                'data'    => null,
            ], 403);
        }

        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', now()->toDateString());

        $data = $this->buildStudentRecap(
            studentId: $studentId,
            startDate: $startDate,
            endDate: $endDate
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap anak berhasil diambil.',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/v1/teacher/students/{studentId}/recap
     */
    public function studentRecap(Request $request, int $studentId)
    {
        $teacher = $request->user();

        if (!$this->teacherCanAccessStudent($teacher->id, $studentId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke rekap siswa ini.',
                'data'    => null,
            ], 403);
        }

        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', now()->toDateString());

        $data = $this->buildStudentRecap(
            studentId: $studentId,
            startDate: $startDate,
            endDate: $endDate
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap siswa berhasil diambil.',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/v1/teacher/classes/{classId}/weekly-recap
     */
    public function classWeeklyRecap(Request $request, int $classId)
    {
        $teacher = $request->user();

        $class = ClassRoom::query()
            ->where('id', $classId)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$class) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas tidak ditemukan atau bukan kelas Anda.',
                'data'    => null,
            ], 404);
        }

        $week = $request->query('week');

        if ($week) {
            $startDate = Carbon::parse($week)->startOfWeek()->toDateString();
            $endDate   = Carbon::parse($week)->endOfWeek()->toDateString();
        } else {
            $startDate = now()->startOfWeek()->toDateString();
            $endDate   = now()->endOfWeek()->toDateString();
        }

        $data = $this->buildClassRecap(
            classId: $classId,
            startDate: $startDate,
            endDate: $endDate
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap mingguan kelas berhasil diambil.',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/v1/teacher/classes/{classId}/monthly-recap
     */
    public function classMonthlyRecap(Request $request, int $classId)
    {
        $teacher = $request->user();

        $class = ClassRoom::query()
            ->where('id', $classId)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$class) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas tidak ditemukan atau bukan kelas Anda.',
                'data'    => null,
            ], 404);
        }

        $month = $request->query('month', now()->format('Y-m'));

        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endDate   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        $data = $this->buildClassRecap(
            classId: $classId,
            startDate: $startDate,
            endDate: $endDate
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Rekap bulanan kelas berhasil diambil.',
            'data'    => $data,
        ]);
    }

    /**
     * Build rekap personal siswa.
     */
    private function buildStudentRecap(int $studentId, string $startDate, string $endDate): array
    {
        $student = User::query()
            ->with(['role', 'classRoom'])
            ->find($studentId);

        $checkins = DailyCheckin::query()
            ->with([
                'items.habit',
                'items.validations.validator',
            ])
            ->where('student_id', $studentId)
            ->whereBetween('checkin_date', [$startDate, $endDate])
            ->orderBy('checkin_date')
            ->get();

        $totalCheckins = $checkins->count();
        $totalItems = $checkins->sum(fn ($checkin) => $checkin->items->count());
        $totalDoneItems = $checkins->sum(
            fn ($checkin) => $checkin->items->where('is_done', true)->count()
        );

        $parentValidatedItems = $checkins->sum(function ($checkin) {
            return $checkin->items->sum(function ($item) {
                return $item->validations
                    ->where('validator_role', 'orang_tua')
                    ->count();
            });
        });

        $teacherValidatedItems = $checkins->sum(function ($checkin) {
            return $checkin->items->sum(function ($item) {
                return $item->validations
                    ->where('validator_role', 'guru')
                    ->count();
            });
        });

        $totalValidatedItems = $parentValidatedItems + $teacherValidatedItems;

        $pendingValidationItems = $checkins->sum(function ($checkin) {
            return $checkin->items
                ->where('is_done', true)
                ->filter(fn ($item) => $item->validations->count() === 0)
                ->count();
        });

        $habitSummary = [];

        foreach ($checkins as $checkin) {
            foreach ($checkin->items as $item) {
                $habitId = $item->habit_id;

                if (!isset($habitSummary[$habitId])) {
                    $habitSummary[$habitId] = [
                        'habit_id'                  => $habitId,
                        'habit'                     => $item->habit ? [
                            'id'         => $item->habit->id,
                            'code'       => $item->habit->code,
                            'name'       => $item->habit->name,
                            'sort_order' => $item->habit->sort_order,
                        ] : null,
                        'done_count'                => 0,
                        'not_done_count'            => 0,
                        'parent_validated_count'    => 0,
                        'teacher_validated_count'   => 0,
                        'total_validation_count'    => 0,
                    ];
                }

                if ($item->is_done) {
                    $habitSummary[$habitId]['done_count']++;
                } else {
                    $habitSummary[$habitId]['not_done_count']++;
                }

                $parentCount = $item->validations
                    ->where('validator_role', 'orang_tua')
                    ->count();

                $teacherCount = $item->validations
                    ->where('validator_role', 'guru')
                    ->count();

                $habitSummary[$habitId]['parent_validated_count'] += $parentCount;
                $habitSummary[$habitId]['teacher_validated_count'] += $teacherCount;
                $habitSummary[$habitId]['total_validation_count'] += ($parentCount + $teacherCount);
            }
        }

        return [
            'student' => $student ? [
                'id'        => $student->id,
                'full_name' => $student->full_name,
                'username'  => $student->username,
                'class'     => $student->classRoom ? [
                    'id'          => $student->classRoom->id,
                    'name'        => $student->classRoom->name,
                    'grade_level' => $student->classRoom->grade_level,
                ] : null,
            ] : null,
            'period' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'summary' => [
                'total_checkins'           => $totalCheckins,
                'total_items'              => $totalItems,
                'total_done_items'         => $totalDoneItems,
                'total_not_done_items'     => max($totalItems - $totalDoneItems, 0),
                'total_validated_items'    => $totalValidatedItems,
                'parent_validated_items'   => $parentValidatedItems,
                'teacher_validated_items'  => $teacherValidatedItems,
                'pending_validation_items' => $pendingValidationItems,
                'completion_percentage'    => $totalItems > 0
                    ? round(($totalDoneItems / $totalItems) * 100, 2)
                    : 0,
            ],
            'habit_summary' => collect($habitSummary)
                ->sortBy(fn ($item) => $item['habit']['sort_order'] ?? 999)
                ->values(),
            'daily_checkins' => $checkins->map(fn ($checkin) => [
                'id'           => $checkin->id,
                'checkin_date' => $checkin->checkin_date,
                'notes'        => $checkin->notes,
                'total_items'  => $checkin->items->count(),
                'done_items'   => $checkin->items->where('is_done', true)->count(),
                'parent_validated_items' => $checkin->items->sum(
                    fn ($item) => $item->validations->where('validator_role', 'orang_tua')->count()
                ),
                'teacher_validated_items' => $checkin->items->sum(
                    fn ($item) => $item->validations->where('validator_role', 'guru')->count()
                ),
                'total_validated_items' => $checkin->items->sum(
                    fn ($item) => $item->validations->count()
                ),
            ])->values(),
        ];
    }

    /**
     * Build rekap kelas.
     */
    private function buildClassRecap(int $classId, string $startDate, string $endDate): array
    {
        $class = ClassRoom::query()
            ->with('teacher')
            ->find($classId);

        $students = User::query()
            ->where('class_id', $classId)
            ->whereHas('role', fn ($query) => $query->where('name', 'siswa'))
            ->orderBy('full_name')
            ->get();

        $studentItems = $students->map(function ($student) use ($startDate, $endDate) {
            $recap = $this->buildStudentRecap(
                studentId: $student->id,
                startDate: $startDate,
                endDate: $endDate
            );

            return [
                'student' => $recap['student'],
                'summary' => $recap['summary'],
            ];
        });

        return [
            'class' => $class ? [
                'id'          => $class->id,
                'name'        => $class->name,
                'grade_level' => $class->grade_level,
                'teacher'     => $class->teacher ? [
                    'id'        => $class->teacher->id,
                    'full_name' => $class->teacher->full_name,
                    'username'  => $class->teacher->username,
                ] : null,
            ] : null,
            'period' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'summary' => [
                'total_students'           => $students->count(),
                'total_checkins'           => $studentItems->sum(fn ($item) => $item['summary']['total_checkins']),
                'total_done_items'         => $studentItems->sum(fn ($item) => $item['summary']['total_done_items']),
                'total_validated_items'    => $studentItems->sum(fn ($item) => $item['summary']['total_validated_items']),
                'parent_validated_items'   => $studentItems->sum(fn ($item) => $item['summary']['parent_validated_items']),
                'teacher_validated_items'  => $studentItems->sum(fn ($item) => $item['summary']['teacher_validated_items']),
                'pending_validation_items' => $studentItems->sum(fn ($item) => $item['summary']['pending_validation_items']),
            ],
            'students' => $studentItems->values(),
        ];
    }

    private function teacherCanAccessStudent(int $teacherId, int $studentId): bool
    {
        return User::query()
            ->where('id', $studentId)
            ->whereHas('classRoom', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->exists();
    }
}