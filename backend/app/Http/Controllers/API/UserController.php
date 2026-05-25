<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CheckinItemValidation;
use App\Models\ClassRoom;
use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use App\Models\Role;
use App\Models\StudentParentRelation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    /**
     * GET /api/v1/admin/users
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search'    => ['nullable', 'string', 'max:255'],
            'role_id'   => ['nullable', 'integer', 'exists:roles,id'],
            'class_id'  => ['nullable', 'integer', 'exists:classes,id'],
            'is_active' => ['nullable', 'boolean'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()
            ->with(['role', 'classRoom'])
            ->orderBy('full_name');

        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['role_id'])) {
            $query->where('role_id', $validated['role_id']);
        }

        if (!empty($validated['class_id'])) {
            $query->where('class_id', $validated['class_id']);
        }

        if (array_key_exists('is_active', $validated)) {
            $query->where('is_active', $validated['is_active']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $users = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar user berhasil diambil.',
            'data'    => [
                'items' => $users->getCollection()
                    ->map(fn ($user) => $this->formatUser($user))
                    ->values(),
                'pagination' => [
                    'page'        => $users->currentPage(),
                    'per_page'    => $users->perPage(),
                    'total'       => $users->total(),
                    'total_pages' => $users->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/dashboard-summary
     */
    public function dashboardSummary()
    {
        $today = now()->toDateString();

        $studentRoleId = Role::where('name', 'siswa')->value('id');
        $teacherRoleId = Role::where('name', 'guru')->value('id');
        $parentRoleId  = Role::where('name', 'orang_tua')->value('id');
        $adminRoleId   = Role::where('name', 'admin')->value('id');

        $totalStudents = User::where('role_id', $studentRoleId)->count();
        $totalTeachers = User::where('role_id', $teacherRoleId)->count();
        $totalParents  = User::where('role_id', $parentRoleId)->count();
        $totalAdmins   = User::where('role_id', $adminRoleId)->count();

        $activeStudents = User::where('role_id', $studentRoleId)
            ->where('is_active', true)
            ->count();

        $totalClasses = ClassRoom::count();
        $activeClasses = ClassRoom::where('is_active', true)->count();

        $todayCheckins = DailyCheckin::whereDate('checkin_date', $today)->count();

        $studentsCheckedInToday = DailyCheckin::whereDate('checkin_date', $today)
            ->distinct('student_id')
            ->count('student_id');

        $studentsNotCheckedInToday = max($activeStudents - $studentsCheckedInToday, 0);

        $todayCheckinIds = DailyCheckin::whereDate('checkin_date', $today)
            ->pluck('id');

        $todayItemsQuery = DailyCheckinItem::whereIn('daily_checkin_id', $todayCheckinIds);

        $todayTotalItems = (clone $todayItemsQuery)->count();

        $todayDoneItems = (clone $todayItemsQuery)
            ->where('is_done', true)
            ->count();

        $todayParentValidations = CheckinItemValidation::whereHas('checkinItem.dailyCheckin', function ($query) use ($today) {
            $query->whereDate('checkin_date', $today);
        })
            ->where('validator_role', 'orang_tua')
            ->count();

        $todayTeacherValidations = CheckinItemValidation::whereHas('checkinItem.dailyCheckin', function ($query) use ($today) {
            $query->whereDate('checkin_date', $today);
        })
            ->where('validator_role', 'guru')
            ->count();

        $todayTotalValidations = $todayParentValidations + $todayTeacherValidations;

        // Setelah revisi validasi, 1 item yang is_done=true bisa divalidasi oleh 2 role:
        // orang_tua dan guru.
        $pendingValidationItems = max(($todayDoneItems * 2) - $todayTotalValidations, 0);

        $recentCheckins = DailyCheckin::with(['student.classRoom'])
            ->latest('checkin_date')
            ->limit(5)
            ->get()
            ->map(fn ($checkin) => [
                'id'           => $checkin->id,
                'student_id'   => $checkin->student_id,
                'student_name' => $checkin->student?->full_name,
                'class'        => $checkin->student?->classRoom ? [
                    'id'   => $checkin->student->classRoom->id,
                    'name' => $checkin->student->classRoom->name,
                ] : null,
                'checkin_date' => $checkin->checkin_date,
                'created_at'   => $checkin->created_at ?? null,
            ])
            ->values();

        return response()->json([
            'status'  => 'success',
            'message' => 'Ringkasan dashboard admin berhasil diambil.',
            'data'    => [
                'users' => [
                    'total_students'  => $totalStudents,
                    'total_teachers'  => $totalTeachers,
                    'total_parents'   => $totalParents,
                    'total_admins'    => $totalAdmins,
                    'active_students' => $activeStudents,
                ],
                'classes' => [
                    'total_classes'  => $totalClasses,
                    'active_classes' => $activeClasses,
                ],
                'today_activity' => [
                    'date'                          => $today,
                    'today_checkins'                => $todayCheckins,
                    'students_checked_in_today'     => $studentsCheckedInToday,
                    'students_not_checked_in_today' => $studentsNotCheckedInToday,
                    'today_total_items'             => $todayTotalItems,
                    'today_done_items'              => $todayDoneItems,
                    'today_parent_validations'      => $todayParentValidations,
                    'today_teacher_validations'     => $todayTeacherValidations,
                    'today_total_validations'       => $todayTotalValidations,
                    'pending_validation_items'      => $pendingValidationItems,
                ],
                'recent_checkins' => $recentCheckins,
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/users
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id'   => ['required', 'integer', 'exists:roles,id'],
            'class_id'  => ['nullable', 'integer', 'exists:classes,id'],
            'name'      => ['nullable', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'password'  => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['name'] = $validated['name'] ?? $validated['full_name'];
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $user = User::query()->create($validated);
        $user->load(['role', 'classRoom']);

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil dibuat.',
            'data'    => $this->formatUser($user),
        ], 201);
    }

    /**
     * GET /api/v1/admin/users/{id}
     */
    public function show(int $id)
    {
        $user = User::query()
            ->with(['role', 'classRoom'])
            ->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail user berhasil diambil.',
            'data'    => $this->formatUser($user),
        ]);
    }

    /**
     * PUT /api/v1/admin/users/{id}
     */
    public function update(Request $request, int $id)
    {
        $user = User::query()
            ->with(['role', 'classRoom'])
            ->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $validated = $request->validate([
            'role_id'   => ['nullable', 'integer', 'exists:roles,id'],
            'class_id'  => ['nullable', 'integer', 'exists:classes,id'],
            'name'      => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'username'  => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($id),
            ],
            'email'     => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'phone'     => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['full_name']) && !isset($validated['name'])) {
            $validated['name'] = $validated['full_name'];
        }

        $user->update($validated);
        $user = $user->fresh(['role', 'classRoom']);

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil diperbarui.',
            'data'    => $this->formatUser($user),
        ]);
    }

    /**
     * DELETE /api/v1/admin/users/{id}
     */
    public function destroy(Request $request, int $id)
    {
        if ($request->user()->id === $id) {
            throw new HttpException(422, 'Admin tidak dapat menghapus akun sendiri.');
        }

        $user = User::query()->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil dihapus.',
            'data'    => null,
        ]);
    }

    /**
     * POST /api/v1/admin/users/{id}/reset-password
     */
    public function resetPassword(Request $request, int $id)
    {
        $user = User::query()
            ->with(['role', 'classRoom'])
            ->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $user->tokens()->delete();
        $user = $user->fresh(['role', 'classRoom']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password user berhasil direset.',
            'data'    => $this->formatUser($user),
        ]);
    }

    /**
     * GET /api/v1/admin/student-parent-relations
     */
    public function studentParentRelations(Request $request)
    {
        $validated = $request->validate([
            'student_id'    => ['nullable', 'integer', 'exists:users,id'],
            'parent_id'     => ['nullable', 'integer', 'exists:users,id'],
            'relation_type' => ['nullable', 'string', 'max:50'],
            'is_active'     => ['nullable', 'boolean'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = StudentParentRelation::query()
            ->with([
                'student.role',
                'student.classRoom',
                'parent.role',
            ])
            ->orderByDesc('id');

        if (!empty($validated['student_id'])) {
            $query->where('student_id', $validated['student_id']);
        }

        if (!empty($validated['parent_id'])) {
            $query->where('parent_id', $validated['parent_id']);
        }

        if (!empty($validated['relation_type'])) {
            $query->where('relation_type', $validated['relation_type']);
        }

        if (array_key_exists('is_active', $validated)) {
            $query->where('is_active', $validated['is_active']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $relations = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar relasi siswa dan orang tua berhasil diambil.',
            'data'    => [
                'items' => $relations->getCollection()
                    ->map(fn ($relation) => $this->formatStudentParentRelation($relation))
                    ->values(),
                'pagination' => [
                    'page'        => $relations->currentPage(),
                    'per_page'    => $relations->perPage(),
                    'total'       => $relations->total(),
                    'total_pages' => $relations->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/student-parent-relations
     */
    public function storeStudentParentRelation(Request $request)
    {
        $validated = $request->validate([
            'student_id'    => ['required', 'integer', 'exists:users,id'],
            'parent_id'     => ['required', 'integer', 'exists:users,id'],
            'relation_type' => ['required', 'string', 'max:50'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        if (!$this->isUserWithRole($validated['student_id'], 'siswa')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'student_id harus merupakan user dengan role siswa.',
                'data'    => null,
            ], 422);
        }

        if (!$this->isUserWithRole($validated['parent_id'], 'orang_tua')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'parent_id harus merupakan user dengan role orang_tua.',
                'data'    => null,
            ], 422);
        }

        if ($validated['student_id'] === $validated['parent_id']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'student_id dan parent_id tidak boleh sama.',
                'data'    => null,
            ], 422);
        }

        $exists = StudentParentRelation::query()
            ->where('student_id', $validated['student_id'])
            ->where('parent_id', $validated['parent_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Relasi siswa dan orang tua sudah ada.',
                'data'    => null,
            ], 422);
        }

        $validated['is_active'] = $validated['is_active'] ?? true;

        $relation = StudentParentRelation::query()->create($validated);

        $relation->load([
            'student.role',
            'student.classRoom',
            'parent.role',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Relasi siswa dan orang tua berhasil dibuat.',
            'data'    => $this->formatStudentParentRelation($relation),
        ], 201);
    }

    /**
     * GET /api/v1/admin/student-parent-relations/{id}
     */
    public function showStudentParentRelation(int $id)
    {
        $relation = StudentParentRelation::query()
            ->with([
                'student.role',
                'student.classRoom',
                'parent.role',
            ])
            ->find($id);

        if (!$relation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Relasi siswa dan orang tua tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail relasi siswa dan orang tua berhasil diambil.',
            'data'    => $this->formatStudentParentRelation($relation),
        ]);
    }

    /**
     * PUT /api/v1/admin/student-parent-relations/{id}
     */
    public function updateStudentParentRelation(Request $request, int $id)
    {
        $relation = StudentParentRelation::query()
            ->with([
                'student.role',
                'student.classRoom',
                'parent.role',
            ])
            ->find($id);

        if (!$relation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Relasi siswa dan orang tua tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $validated = $request->validate([
            'student_id'    => ['nullable', 'integer', 'exists:users,id'],
            'parent_id'     => ['nullable', 'integer', 'exists:users,id'],
            'relation_type' => ['nullable', 'string', 'max:50'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $studentId = $validated['student_id'] ?? $relation->student_id;
        $parentId = $validated['parent_id'] ?? $relation->parent_id;

        if (!$this->isUserWithRole($studentId, 'siswa')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'student_id harus merupakan user dengan role siswa.',
                'data'    => null,
            ], 422);
        }

        if (!$this->isUserWithRole($parentId, 'orang_tua')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'parent_id harus merupakan user dengan role orang_tua.',
                'data'    => null,
            ], 422);
        }

        if ($studentId === $parentId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'student_id dan parent_id tidak boleh sama.',
                'data'    => null,
            ], 422);
        }

        $duplicate = StudentParentRelation::query()
            ->where('student_id', $studentId)
            ->where('parent_id', $parentId)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Relasi siswa dan orang tua sudah ada.',
                'data'    => null,
            ], 422);
        }

        $relation->update($validated);

        $relation = $relation->fresh([
            'student.role',
            'student.classRoom',
            'parent.role',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Relasi siswa dan orang tua berhasil diperbarui.',
            'data'    => $this->formatStudentParentRelation($relation),
        ]);
    }

    /**
     * DELETE /api/v1/admin/student-parent-relations/{id}
     */
    public function deleteStudentParentRelation(int $id)
    {
        $relation = StudentParentRelation::query()->find($id);

        if (!$relation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Relasi siswa dan orang tua tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $relation->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Relasi siswa dan orang tua berhasil dihapus.',
            'data'    => null,
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'        => $user->id,
            'full_name' => $user->full_name,
            'username'  => $user->username,
            'email'     => $user->email,
            'phone'     => $user->phone,
            'is_active' => $user->is_active,

            'role' => $user->role ? [
                'id'   => $user->role->id,
                'name' => $user->role->name,
            ] : null,

            'class' => $user->classRoom ? [
                'id'          => $user->classRoom->id,
                'name'        => $user->classRoom->name,
                'grade_level' => $user->classRoom->grade_level,
            ] : null,
        ];
    }

    private function formatStudentParentRelation(StudentParentRelation $relation): array
    {
        return [
            'id'            => $relation->id,
            'relation_type' => $relation->relation_type,
            'is_active'     => $relation->is_active,

            'student' => $relation->student ? [
                'id'        => $relation->student->id,
                'full_name' => $relation->student->full_name,
                'username'  => $relation->student->username,
                'email'     => $relation->student->email,
                'role'      => $relation->student->role ? [
                    'id'   => $relation->student->role->id,
                    'name' => $relation->student->role->name,
                ] : null,
                'class' => $relation->student->classRoom ? [
                    'id'          => $relation->student->classRoom->id,
                    'name'        => $relation->student->classRoom->name,
                    'grade_level' => $relation->student->classRoom->grade_level,
                ] : null,
            ] : null,

            'parent' => $relation->parent ? [
                'id'        => $relation->parent->id,
                'full_name' => $relation->parent->full_name,
                'username'  => $relation->parent->username,
                'email'     => $relation->parent->email,
                'role'      => $relation->parent->role ? [
                    'id'   => $relation->parent->role->id,
                    'name' => $relation->parent->role->name,
                ] : null,
            ] : null,
        ];
    }

    private function isUserWithRole(int $userId, string $roleName): bool
    {
        return User::query()
            ->where('id', $userId)
            ->whereHas('role', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })
            ->exists();
    }
}