<?php

namespace App\Services;

use App\Models\User;
use App\Models\Habit;
use App\Models\ClassRoom;
use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use App\Models\StudentParentRelation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecapService
{
    public function getStudentRecap(
        int $studentId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $student = User::query()->find($studentId);

        if (!$student) {
            throw new NotFoundHttpException('Siswa tidak ditemukan.');
        }

        [$start, $end, $totalDays] = $this->resolvePeriod($startDate, $endDate);

        $habits = Habit::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $checkinIds = DailyCheckin::query()
            ->where('student_id', $studentId)
            ->whereBetween('checkin_date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->pluck('id');

        $totalCheckinDays = $checkinIds->count();

        $itemStats = DailyCheckinItem::query()
            ->select(
                'daily_checkin_items.habit_id',
                DB::raw('COUNT(CASE WHEN daily_checkin_items.is_done = 1 THEN 1 END) as done_days'),
                DB::raw('COUNT(checkin_item_validations.id) as validated_days')
            )
            ->leftJoin(
                'checkin_item_validations',
                'checkin_item_validations.daily_checkin_item_id',
                '=',
                'daily_checkin_items.id'
            )
            ->whereIn('daily_checkin_items.daily_checkin_id', $checkinIds)
            ->groupBy('daily_checkin_items.habit_id')
            ->get()
            ->keyBy('habit_id');

        $totalHabitsDone = 0;
        $totalValidatedItems = 0;

        $habitRecaps = $habits->map(function ($habit) use (
            $itemStats,
            $totalDays,
            &$totalHabitsDone,
            &$totalValidatedItems
        ) {
            $row = $itemStats->get($habit->id);

            $doneDays = (int) ($row->done_days ?? 0);
            $validatedDays = (int) ($row->validated_days ?? 0);

            $totalHabitsDone += $doneDays;
            $totalValidatedItems += $validatedDays;

            return [
                'habit_id'              => $habit->id,
                'habit_name'            => $habit->name,
                'done_days'             => $doneDays,
                'validated_days'        => $validatedDays,
                'completion_percentage' => $totalDays > 0
                    ? round(($doneDays / $totalDays) * 100, 2)
                    : 0,
            ];
        })->values();

        $maxPossibleItems = $totalDays * max($habits->count(), 1);

        return [
            'student' => [
                'id'        => $student->id,
                'full_name' => $student->full_name,
                'class_id'  => $student->class_id,
            ],
            'period' => [
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
                'total_days' => $totalDays,
            ],
            'summary' => [
                'total_checkin_days'       => $totalCheckinDays,
                'total_habits_done'        => $totalHabitsDone,
                'total_validated_items'    => $totalValidatedItems,
                'completion_percentage'    => $maxPossibleItems > 0
                    ? round(($totalHabitsDone / $maxPossibleItems) * 100, 2)
                    : 0,
                'validation_percentage'    => $totalHabitsDone > 0
                    ? round(($totalValidatedItems / $totalHabitsDone) * 100, 2)
                    : 0,
            ],
            'habits' => $habitRecaps,
        ];
    }

    public function getClassWeeklyRecap(
        int $teacherId,
        int $classId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $this->ensureTeacherCanAccessClass($teacherId, $classId);

        [$start, $end] = $this->resolveWeeklyPeriod($startDate, $endDate);

        return $this->getClassRecap(
            classId: $classId,
            startDate: $start->toDateString(),
            endDate: $end->toDateString()
        );
    }

    public function getClassMonthlyRecap(
        int $teacherId,
        int $classId,
        string $month
    ): array {
        $this->ensureTeacherCanAccessClass($teacherId, $classId);

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        return $this->getClassRecap(
            classId: $classId,
            startDate: $start->toDateString(),
            endDate: $end->toDateString()
        );
    }

    public function getClassRecap(
        int $classId,
        string $startDate,
        string $endDate
    ): array {
        $class = ClassRoom::query()
            ->active()
            ->with(['students' => function ($query) {
                $query->orderBy('full_name');
            }])
            ->find($classId);

        if (!$class) {
            throw new NotFoundHttpException('Kelas tidak ditemukan.');
        }

        [$start, $end, $totalDays] = $this->resolvePeriod($startDate, $endDate);

        $students = $class->students;

        $studentRecaps = $students->map(function ($student) use ($start, $end) {
            $recap = $this->getStudentRecap(
                studentId: $student->id,
                startDate: $start->toDateString(),
                endDate: $end->toDateString()
            );

            return [
                'student_id'              => $student->id,
                'student_name'            => $student->full_name,
                'total_checkin_days'      => $recap['summary']['total_checkin_days'],
                'total_habits_done'       => $recap['summary']['total_habits_done'],
                'total_validated_items'   => $recap['summary']['total_validated_items'],
                'completion_percentage'   => $recap['summary']['completion_percentage'],
                'validation_percentage'   => $recap['summary']['validation_percentage'],
            ];
        })->values();

        $totalStudents = $students->count();
        $totalCheckins = $studentRecaps->sum('total_checkin_days');
        $totalHabitsDone = $studentRecaps->sum('total_habits_done');
        $totalValidatedItems = $studentRecaps->sum('total_validated_items');

        return [
            'class' => [
                'id'          => $class->id,
                'name'        => $class->name,
                'grade_level' => $class->grade_level,
                'teacher_id'  => $class->teacher_id,
            ],
            'period' => [
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
                'total_days' => $totalDays,
            ],
            'summary' => [
                'total_students'              => $totalStudents,
                'total_checkins'              => $totalCheckins,
                'total_habits_done'           => $totalHabitsDone,
                'total_validated_items'       => $totalValidatedItems,
                'average_checkin_percentage'  => $totalStudents > 0 && $totalDays > 0
                    ? round(($totalCheckins / ($totalStudents * $totalDays)) * 100, 2)
                    : 0,
                'average_validation_percentage' => $totalHabitsDone > 0
                    ? round(($totalValidatedItems / $totalHabitsDone) * 100, 2)
                    : 0,
            ],
            'students' => $studentRecaps,
        ];
    }

    public function ensureParentCanAccessStudent(
        int $parentId,
        int $studentId
    ): void {
        $allowed = StudentParentRelation::query()
            ->where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();

        if (!$allowed) {
            throw new HttpException(403, 'Orang tua tidak memiliki akses ke siswa ini.');
        }
    }

    public function ensureTeacherCanAccessClass(
        int $teacherId,
        int $classId
    ): void {
        $allowed = ClassRoom::query()
            ->active()
            ->where('id', $classId)
            ->where('teacher_id', $teacherId)
            ->exists();

        if (!$allowed) {
            throw new HttpException(403, 'Guru tidak memiliki akses ke kelas ini.');
        }
    }

    public function ensureTeacherCanAccessStudent(
        int $teacherId,
        int $studentId
    ): void {
        $student = User::query()->find($studentId);

        if (!$student) {
            throw new NotFoundHttpException('Siswa tidak ditemukan.');
        }

        if (!$student->class_id) {
            throw new HttpException(403, 'Siswa belum memiliki kelas.');
        }

        $this->ensureTeacherCanAccessClass($teacherId, $student->class_id);
    }

    private function resolvePeriod(
        ?string $startDate,
        ?string $endDate
    ): array {
        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : now()->startOfMonth();

        $end = $endDate
            ? Carbon::parse($endDate)->startOfDay()
            : now()->endOfMonth()->startOfDay();

        if ($end->lt($start)) {
            throw new HttpException(422, 'end_date tidak boleh lebih kecil dari start_date.');
        }

        return [
            $start,
            $end,
            $start->diffInDays($end) + 1,
        ];
    }

    private function resolveWeeklyPeriod(
        ?string $startDate,
        ?string $endDate
    ): array {
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();

            if ($end->lt($start)) {
                throw new HttpException(422, 'end_date tidak boleh lebih kecil dari start_date.');
            }

            return [$start, $end];
        }

        return [
            now()->startOfWeek()->startOfDay(),
            now()->endOfWeek()->startOfDay(),
        ];
    }
}