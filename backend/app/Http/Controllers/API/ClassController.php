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
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search'      => ['nullable', 'string', 'max:255'],
            'grade_level' => ['nullable', 'string', 'max:20'],
            'is_active'   => ['nullable', 'boolean'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ClassRoom::with('teacher')
            ->orderBy('grade_level')
            ->orderBy('name');

        if (!empty($validated['search'])) {
            $query->where(function ($query) use ($validated) {
                $query->where('name', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('grade_level', 'like', '%' . $validated['search'] . '%');
            });
        }

        if (!empty($validated['grade_level'])) {
            $query->where('grade_level', $validated['grade_level']);
        }

        if (array_key_exists('is_active', $validated)) {
            $query->where('is_active', $validated['is_active']);
        }

        $perPage = $validated['per_page'] ?? 10;
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('classes', 'name')->where(function ($query) use ($request) {
                    return $query
                        ->where('grade_level', $request->grade_level)
                        ->where('is_active', true);
                }),
            ],
            'grade_level' => ['required', 'string', 'max:20'],
            'teacher_id'  => ['required', 'integer', 'exists:users,id'],
            'is_active'   => ['nullable', 'boolean'],
        ], [
            'name.required'        => 'Nama kelas wajib diisi.',
            'name.unique'          => 'Nama kelas sudah digunakan oleh kelas aktif pada tingkat yang sama.',
            'grade_level.required' => 'Tingkat kelas wajib diisi.',
            'teacher_id.required'  => 'Wali kelas wajib dipilih.',
            'teacher_id.exists'    => 'Wali kelas tidak ditemukan.',
        ]);

        if (!$this->isTeacherUser((int) $validated['teacher_id'])) {
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
                        return $query
                            ->where('grade_level', $request->grade_level ?? $class->grade_level)
                            ->where('is_active', true);
                    }),
            ],
            'grade_level' => ['nullable', 'string', 'max:20'],
            'teacher_id'  => ['nullable', 'integer', 'exists:users,id'],
            'is_active'   => ['nullable', 'boolean'],
        ], [
            'name.unique'       => 'Nama kelas sudah digunakan oleh kelas aktif pada tingkat yang sama.',
            'teacher_id.exists' => 'Wali kelas tidak ditemukan.',
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

        $hasStudents = User::where('class_id', $id)
            ->whereHas('role', fn ($query) => $query->where('name', 'siswa'))
            ->exists();

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

    public function classStudents(Request $request, int $classId): JsonResponse
    {
        $validated = $request->validate([
            'search'   => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        $query = User::where('class_id', $classId)
            ->whereHas('role', fn ($q) => $q->where('name', 'siswa'))
            ->orderBy('full_name');

        if (!empty($validated['search'])) {
            $query->where('full_name', 'like', '%' . $validated['search'] . '%');
        }

        $perPage = $validated['per_page'] ?? 10;
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

    public function classCheckins(Request $request, int $classId): JsonResponse
    {
        $validated = $request->validate([
            'date'     => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        $date = $validated['date'] ?? today()->format('Y-m-d');
        $perPage = $validated['per_page'] ?? 10;

        $students = User::where('class_id', $classId)
            ->whereHas('role', fn ($q) => $q->where('name', 'siswa'))
            ->with([
                'checkins' => fn ($q) => $q->whereDate('checkin_date', $date)
                    ->with([
                        'items.habit',
                        'items.validations.validator',
                    ]),
            ])
            ->orderBy('full_name')
            ->paginate($perPage);

        $items = $students->getCollection()->map(function ($student) {
            $checkin = $student->checkins->first();

            $doneItems = $checkin
                ? $checkin->items->where('is_done', true)
                : collect();

            $parentValidatedCount = $doneItems->sum(
                fn ($item) => $item->validations->where('validator_role', 'orang_tua')->count()
            );

            $teacherValidatedCount = $doneItems->sum(
                fn ($item) => $item->validations->where('validator_role', 'guru')->count()
            );

            $pendingTeacherValidationCount = $doneItems->filter(
                fn ($item) => $item->validations->where('validator_role', 'guru')->count() === 0
            )->count();

            $pendingParentValidationCount = $doneItems->filter(
                fn ($item) => $item->validations->where('validator_role', 'orang_tua')->count() === 0
            )->count();

            return [
                'student_id'                       => $student->id,
                'student_name'                     => $student->full_name,
                'checked_in'                       => (bool) $checkin,
                'daily_checkin_id'                 => $checkin?->id,
                'total_habits_done'                => $doneItems->count(),
                'total_items_validated'            => $parentValidatedCount + $teacherValidatedCount,
                'parent_validated_items'           => $parentValidatedCount,
                'teacher_validated_items'          => $teacherValidatedCount,
                'parent_pending_validation_count'  => $pendingParentValidationCount,
                'teacher_pending_validation_count' => $pendingTeacherValidationCount,
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
                    'parent_validated_items'     => $items->sum('parent_validated_items'),
                    'teacher_validated_items'    => $items->sum('teacher_validated_items'),
                    'pending_validation_items'   => $items->sum('teacher_pending_validation_count'),
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

    private function formatClass(ClassRoom $class): array
    {
        return [
            'id'          => $class->id,
            'name'        => $class->name,
            'grade_level' => $class->grade_level,
            'teacher_id'  => $class->teacher_id,
            'is_active'   => $class->is_active,
            'teacher'     => $class->teacher ? [
                'id'        => $class->teacher->id,
                'full_name' => $class->teacher->full_name,
                'name'      => $class->teacher->name,
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