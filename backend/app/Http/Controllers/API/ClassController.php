<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\DailyCheckin;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * GET /api/v1/classes
     */
    public function index(Request $request): JsonResponse
    {
        $query = ClassRoom::with('teacher');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (bool) $request->is_active);
        }

        $perPage = $request->integer('per_page', 10);
        $classes = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar kelas berhasil diambil.',
            'data'    => [
                'items'      => $classes->map(fn($c) => $this->formatClass($c)),
                'pagination' => [
                    'page'        => $classes->currentPage(),
                    'per_page'    => $classes->perPage(),
                    'total'       => $classes->total(),
                    'total_pages' => $classes->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/teacher/classes
     */
    public function teacherClasses(Request $request): JsonResponse
    {
        $classes = ClassRoom::where('teacher_id', $request->user()->id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar kelas berhasil diambil.',
            'data'    => $classes->map(fn($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'grade_level' => $c->grade_level,
            ]),
        ]);
    }

    /**
     * GET /api/v1/teacher/classes/{classId}/students
     */
    public function classStudents(Request $request, int $classId): JsonResponse
    {
        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        $query = User::where('class_id', $classId)
            ->whereHas('role', fn($q) => $q->where('name', 'siswa'));

        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }

        $perPage  = $request->integer('per_page', 10);
        $students = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar siswa berhasil diambil.',
            'data'    => [
                'items' => $students->map(fn($s) => [
                    'id'                  => $s->id,
                    'full_name'           => $s->full_name,
                    'username'            => $s->username,
                    'latest_checkin_date' => $s->checkins()
                        ->latest('checkin_date')
                        ->value('checkin_date'),
                ]),
                'pagination' => [
                    'page'        => $students->currentPage(),
                    'per_page'    => $students->perPage(),
                    'total'       => $students->total(),
                    'total_pages' => $students->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/teacher/classes/{classId}/checkins
     */
    public function classCheckins(Request $request, int $classId): JsonResponse
    {
        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        $date = $request->date ?? today()->format('Y-m-d');

        $students = User::where('class_id', $classId)
            ->whereHas('role', fn($q) => $q->where('name', 'siswa'))
            ->with(['checkins' => fn($q) => $q->whereDate('checkin_date', $date)
                ->with('items.validation')])
            ->paginate($request->integer('per_page', 10));

        $items = $students->map(function ($student) {
            $checkin = $student->checkins->first();

            return [
                'student_id'                    => $student->id,
                'student_name'                  => $student->full_name,
                'checked_in'                    => (bool) $checkin,
                'daily_checkin_id'              => $checkin?->id,
                'total_habits_done'             => $checkin
                    ? $checkin->items->where('is_done', true)->count() : 0,
                'total_items_validated'         => $checkin
                    ? $checkin->items->filter(fn($i) => $i->validation)->count() : 0,
                'school_pending_validation_count' => $checkin
                    ? $checkin->items->filter(
                        fn($i) => $i->is_done
                            && $i->activity_context === 'sekolah'
                            && !$i->validation
                    )->count() : 0,
                'home_pending_validation_count' => $checkin
                    ? $checkin->items->filter(
                        fn($i) => $i->is_done
                            && $i->activity_context === 'rumah'
                            && !$i->validation
                    )->count() : 0,
            ];
        });

        $totalStudents   = $students->total();
        $checkedInCount  = $items->where('checked_in', true)->count();

        return response()->json([
            'status'  => 'success',
            'message' => 'Monitoring check-in berhasil diambil.',
            'data'    => [
                'class' => [
                    'id'          => $class->id,
                    'name'        => $class->name,
                    'grade_level' => $class->grade_level,
                ],
                'date'    => $date,
                'summary' => [
                    'total_students'           => $totalStudents,
                    'checked_in_count'         => $checkedInCount,
                    'not_checked_in_count'     => $totalStudents - $checkedInCount,
                    'total_done_items'         => $items->sum('total_habits_done'),
                    'total_validated_items'    => $items->sum('total_items_validated'),
                    'parent_validated_items'   => 0, // diisi di feature recap
                    'teacher_validated_items'  => 0, // diisi di feature recap
                    'pending_validation_items' => $items->sum('school_pending_validation_count'),
                ],
                'items'      => $items,
                'pagination' => [
                    'page'        => $students->currentPage(),
                    'per_page'    => $students->perPage(),
                    'total'       => $students->total(),
                    'total_pages' => $students->lastPage(),
                ],
            ],
        ]);
    }

    // ── Helper ────────────────────────────────────────────────

    private function formatClass(ClassRoom $class): array
    {
        return [
            'id'          => $class->id,
            'name'        => $class->name,
            'grade_level' => $class->grade_level,
            'is_active'   => $class->is_active,
            'teacher'     => $class->teacher ? [
                'id'        => $class->teacher->id,
                'full_name' => $class->teacher->full_name,
            ] : null,
        ];
    }
}