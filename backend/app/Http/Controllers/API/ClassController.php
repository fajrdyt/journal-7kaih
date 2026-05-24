<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    /**
     * GET /api/v1/classes
     * GET /api/v1/admin/classes
     */
    public function index(Request $request): JsonResponse
    {
        $query = ClassRoom::with('teacher')
            ->orderBy('grade_level')
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $request->integer('per_page', 10);
        $classes = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar kelas berhasil diambil.',
            'data'    => [
                'items' => $classes->getCollection()
                    ->map(fn ($class) => $this->formatClass($class))
                    ->values(),
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
     * POST /api/v1/admin/classes
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('classes', 'name')->where(function ($query) use ($request) {
                    return $query->where('grade_level', $request->grade_level);
                }),
            ],
            'grade_level' => ['required', 'string', 'max:20'],
            'teacher_id'  => ['nullable', 'integer', 'exists:users,id'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        if (
            array_key_exists('teacher_id', $validated)
            && $validated['teacher_id'] !== null
            && !$this->isTeacherUser((int) $validated['teacher_id'])
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'teacher_id harus merupakan user dengan role guru.',
                'data'    => null,
            ], 422);
        }

        $validated['is_active'] = $validated['is_active'] ?? true;

        $class = ClassRoom::create($validated);
        $class->load('teacher');

        return response()->json([
            'status'  => 'success',
            'message' => 'Kelas berhasil dibuat.',
            'data'    => $this->formatClass($class),
        ], 201);
    }

    /**
     * GET /api/v1/admin/classes/{id}
     */
    public function show(int $id): JsonResponse
    {
        $class = ClassRoom::with('teacher')->find($id);

        if (!$class) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail kelas berhasil diambil.',
            'data'    => $this->formatClass($class),
        ]);
    }

    /**
     * PUT /api/v1/admin/classes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $class = ClassRoom::with('teacher')->find($id);

        if (!$class) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $validated = $request->validate([
            'name'        => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('classes', 'name')
                    ->ignore($id)
                    ->where(function ($query) use ($request, $class) {
                        return $query->where(
                            'grade_level',
                            $request->grade_level ?? $class->grade_level
                        );
                    }),
            ],
            'grade_level' => ['nullable', 'string', 'max:20'],
            'teacher_id'  => ['nullable', 'integer', 'exists:users,id'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        if (
            array_key_exists('teacher_id', $validated)
            && $validated['teacher_id'] !== null
            && !$this->isTeacherUser((int) $validated['teacher_id'])
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'teacher_id harus merupakan user dengan role guru.',
                'data'    => null,
            ], 422);
        }

        $class->update($validated);
        $class = $class->fresh('teacher');

        return response()->json([
            'status'  => 'success',
            'message' => 'Kelas berhasil diperbarui.',
            'data'    => $this->formatClass($class),
        ]);
    }

    /**
     * DELETE /api/v1/admin/classes/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $class = ClassRoom::find($id);

        if (!$class) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $hasStudents = User::where('class_id', $id)->exists();

        if ($hasStudents) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa.',
                'data'    => null,
            ], 422);
        }

        $class->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Kelas berhasil dihapus.',
            'data'    => null,
        ]);
    }

    /**
     * GET /api/v1/teacher/classes
     */
    public function teacherClasses(Request $request): JsonResponse
    {
        $classes = ClassRoom::where('teacher_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar kelas berhasil diambil.',
            'data'    => $classes->map(fn ($class) => [
                'id'          => $class->id,
                'name'        => $class->name,
                'grade_level' => $class->grade_level,
            ])->values(),
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
            ->whereHas('role', fn ($q) => $q->where('name', 'siswa'))
            ->orderBy('full_name');

        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->integer('per_page', 10);
        $students = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar siswa berhasil diambil.',
            'data'    => [
                'class' => [
                    'id'          => $class->id,
                    'name'        => $class->name,
                    'grade_level' => $class->grade_level,
                ],
                'items' => $students->getCollection()
                    ->map(fn ($student) => [
                        'id'                  => $student->id,
                        'full_name'           => $student->full_name,
                        'username'            => $student->username,
                        'latest_checkin_date' => $student->checkins()
                            ->latest('checkin_date')
                            ->value('checkin_date'),
                    ])
                    ->values(),
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
            ->whereHas('role', fn ($q) => $q->where('name', 'siswa'))
            ->with([
                'checkins' => fn ($q) => $q->whereDate('checkin_date', $date)
                    ->with('items.validation'),
            ])
            ->orderBy('full_name')
            ->paginate($request->integer('per_page', 10));

        $items = $students->getCollection()->map(function ($student) {
            $checkin = $student->checkins->first();

            return [
                'student_id'                      => $student->id,
                'student_name'                    => $student->full_name,
                'checked_in'                      => (bool) $checkin,
                'daily_checkin_id'                => $checkin?->id,
                'total_habits_done'               => $checkin
                    ? $checkin->items->where('is_done', true)->count()
                    : 0,
                'total_items_validated'           => $checkin
                    ? $checkin->items->filter(fn ($item) => $item->validation)->count()
                    : 0,
                'school_pending_validation_count' => $checkin
                    ? $checkin->items->filter(
                        fn ($item) => $item->is_done
                            && $item->activity_context === 'sekolah'
                            && !$item->validation
                    )->count()
                    : 0,
                'home_pending_validation_count'   => $checkin
                    ? $checkin->items->filter(
                        fn ($item) => $item->is_done
                            && $item->activity_context === 'rumah'
                            && !$item->validation
                    )->count()
                    : 0,
            ];
        });

        $totalStudents = $students->total();
        $checkedInCount = $items->where('checked_in', true)->count();

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
                    'total_students'             => $totalStudents,
                    'checked_in_count'           => $checkedInCount,
                    'not_checked_in_count'       => $totalStudents - $checkedInCount,
                    'total_done_items'           => $items->sum('total_habits_done'),
                    'total_validated_items'      => $items->sum('total_items_validated'),
                    'parent_validated_items'     => 0,
                    'teacher_validated_items'    => 0,
                    'pending_validation_items'   => $items->sum('school_pending_validation_count'),
                ],
                'items'      => $items->values(),
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
                'username'  => $class->teacher->username,
                'email'     => $class->teacher->email,
            ] : null,
        ];
    }

    private function isTeacherUser(int $userId): bool
    {
        return User::query()
            ->where('id', $userId)
            ->whereHas('role', function ($query) {
                $query->where('name', 'guru');
            })
            ->exists();
    }
}