<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\DailyCheckin;
use App\Models\Habit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecapService
{
    public function personal(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $student = $request->user();

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate   = $validated['end_date'] ?? now()->toDateString();

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

    public function childRecap(Request $request, int $studentId)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

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

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate   = $validated['end_date'] ?? now()->toDateString();

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

    public function studentRecap(Request $request, int $studentId)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $teacher = $request->user();

        if (!$this->teacherCanAccessStudent($teacher->id, $studentId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke rekap siswa ini.',
                'data'    => null,
            ], 403);
        }

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate   = $validated['end_date'] ?? now()->toDateString();

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

    public function classWeeklyRecap(Request $request, int $classId)
    {
        $validated = $request->validate([
            'week' => ['nullable', 'date'],
        ]);

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

        if (!empty($validated['week'])) {
            $startDate = Carbon::parse($validated['week'])->startOfWeek()->toDateString();
            $endDate   = Carbon::parse($validated['week'])->endOfWeek()->toDateString();
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

    public function classMonthlyRecap(Request $request, int $classId)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

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

        $month = $validated['month'] ?? now()->format('Y-m');

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

    public function studentHabitStatistics(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $student = $request->user();

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate   = $validated['end_date'] ?? now()->toDateString();

        $checkins = DailyCheckin::query()
            ->with(['items.habit'])
            ->where('student_id', $student->id)
            ->whereBetween('checkin_date', [$startDate, $endDate])
            ->orderBy('checkin_date')
            ->get();

        $habits = Habit::active()->get();

        $habitStatistics = $this->buildHabitStatistics($habits, $checkins);

        $dailyProgress = $checkins->map(function ($checkin) {
            $totalItems = $checkin->items->count();
            $doneItems = $checkin->items->where('is_done', true)->count();

            return [
                'date'                  => Carbon::parse($checkin->checkin_date)->format('Y-m-d'),
                'total_items'           => $totalItems,
                'done_items'            => $doneItems,
                'completion_percentage' => $totalItems > 0
                    ? round(($doneItems / $totalItems) * 100, 2)
                    : 0,
            ];
        })->values();

        $totalCheckins = $checkins->count();
        $totalItems = $checkins->sum(fn ($checkin) => $checkin->items->count());
        $totalDoneItems = $checkins->sum(
            fn ($checkin) => $checkin->items->where('is_done', true)->count()
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Statistik kebiasaan siswa berhasil diambil.',
            'data'    => [
                'student' => [
                    'id'        => $student->id,
                    'full_name' => $student->full_name,
                    'username'  => $student->username,
                ],
                'period' => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ],
                'summary' => [
                    'total_checkins'        => $totalCheckins,
                    'total_items'           => $totalItems,
                    'total_done_items'      => $totalDoneItems,
                    'total_not_done_items'  => max($totalItems - $totalDoneItems, 0),
                    'completion_percentage' => $totalItems > 0
                        ? round(($totalDoneItems / $totalItems) * 100, 2)
                        : 0,
                ],
                'habits'         => $habitStatistics,
                'daily_progress' => $dailyProgress,
            ],
        ]);
    }

    public function teacherClassHabitStatistics(Request $request, int $classId)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

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

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate   = $validated['end_date'] ?? now()->toDateString();

        $studentIds = User::query()
            ->where('class_id', $classId)
            ->whereHas('role', fn ($query) => $query->where('name', 'siswa'))
            ->pluck('id');

        $checkins = DailyCheckin::query()
            ->with(['items.habit'])
            ->whereIn('student_id', $studentIds)
            ->whereBetween('checkin_date', [$startDate, $endDate])
            ->orderBy('checkin_date')
            ->get();

        $habits = Habit::active()->get();

        $habitStatistics = $this->buildHabitStatistics($habits, $checkins);

        $dailyProgress = $checkins
            ->groupBy(fn ($checkin) => Carbon::parse($checkin->checkin_date)->format('Y-m-d'))
            ->map(function ($group, $date) {
                $totalItems = $group->sum(fn ($checkin) => $checkin->items->count());
                $doneItems = $group->sum(
                    fn ($checkin) => $checkin->items->where('is_done', true)->count()
                );

                return [
                    'date'                  => $date,
                    'total_checkins'        => $group->count(),
                    'total_items'           => $totalItems,
                    'done_items'            => $doneItems,
                    'completion_percentage' => $totalItems > 0
                        ? round(($doneItems / $totalItems) * 100, 2)
                        : 0,
                ];
            })
            ->values();

        $totalCheckins = $checkins->count();
        $totalItems = $checkins->sum(fn ($checkin) => $checkin->items->count());
        $totalDoneItems = $checkins->sum(
            fn ($checkin) => $checkin->items->where('is_done', true)->count()
        );

        $bestHabit = $habitStatistics
            ->filter(fn ($habit) => $habit['total_items'] > 0)
            ->sortByDesc('percentage')
            ->first();

        $lowestHabit = $habitStatistics
            ->filter(fn ($habit) => $habit['total_items'] > 0)
            ->sortBy('percentage')
            ->first();

        return response()->json([
            'status'  => 'success',
            'message' => 'Statistik kebiasaan kelas berhasil diambil.',
            'data'    => [
                'class' => [
                    'id'          => $class->id,
                    'name'        => $class->name,
                    'grade_level' => $class->grade_level,
                ],
                'period' => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ],
                'summary' => [
                    'total_students'        => $studentIds->count(),
                    'total_checkins'        => $totalCheckins,
                    'total_items'           => $totalItems,
                    'total_done_items'      => $totalDoneItems,
                    'total_not_done_items'  => max($totalItems - $totalDoneItems, 0),
                    'completion_percentage' => $totalItems > 0
                        ? round(($totalDoneItems / $totalItems) * 100, 2)
                        : 0,
                    'best_habit'            => $bestHabit,
                    'lowest_habit'          => $lowestHabit,
                ],
                'habits'         => $habitStatistics,
                'daily_progress' => $dailyProgress,
            ],
        ]);
    }

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

        $activeHabitCount = Habit::active()->count();

        $completedDays = $checkins->filter(function ($checkin) use ($activeHabitCount) {
            $totalItems = $checkin->items->count();
            $doneItems = $checkin->items->where('is_done', true)->count();

            return $activeHabitCount > 0
                && $totalItems >= $activeHabitCount
                && $doneItems >= $activeHabitCount;
        })->count();

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
                        'habit_id'                => $habitId,
                        'habit'                   => $item->habit ? [
                            'id'         => $item->habit->id,
                            'code'       => $item->habit->code,
                            'name'       => $item->habit->name,
                            'sort_order' => $item->habit->sort_order,
                        ] : null,
                        'done_count'              => 0,
                        'not_done_count'          => 0,
                        'parent_validated_count'  => 0,
                        'teacher_validated_count' => 0,
                        'total_validation_count'  => 0,
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
                'completed_days'           => $completedDays,
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
            'daily_checkins' => $checkins->map(function ($checkin) use ($activeHabitCount) {
                $totalItems = $checkin->items->count();
                $doneItems = $checkin->items->where('is_done', true)->count();

                return [
                    'id'           => $checkin->id,
                    'checkin_date' => $checkin->checkin_date,
                    'notes'        => $checkin->notes,
                    'total_items'  => $totalItems,
                    'done_items'   => $doneItems,
                    'is_complete'  => $activeHabitCount > 0
                        && $totalItems >= $activeHabitCount
                        && $doneItems >= $activeHabitCount,
                    'parent_validated_items' => $checkin->items->sum(
                        fn ($item) => $item->validations->where('validator_role', 'orang_tua')->count()
                    ),
                    'teacher_validated_items' => $checkin->items->sum(
                        fn ($item) => $item->validations->where('validator_role', 'guru')->count()
                    ),
                    'total_validated_items' => $checkin->items->sum(
                        fn ($item) => $item->validations->count()
                    ),
                ];
            })->values(),
        ];
    }

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

    private function buildHabitStatistics($habits, $checkins)
    {
        return $habits->map(function ($habit) use ($checkins) {
            $items = $checkins
                ->flatMap(fn ($checkin) => $checkin->items)
                ->where('habit_id', $habit->id);

            $totalItems = $items->count();
            $doneCount = $items->where('is_done', true)->count();
            $notDoneCount = max($totalItems - $doneCount, 0);

            return [
                'habit_id'       => $habit->id,
                'habit_code'     => $habit->code,
                'habit_name'     => $habit->name,
                'sort_order'     => $habit->sort_order,
                'total_items'    => $totalItems,
                'done_count'     => $doneCount,
                'not_done_count' => $notDoneCount,
                'percentage'     => $totalItems > 0
                    ? round(($doneCount / $totalItems) * 100, 2)
                    : 0,
            ];
        })->values();
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