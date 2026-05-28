<?php

namespace App\Services;

use App\Models\CheckinItemValidation;
use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use App\Models\StudentParentRelation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValidationService
{
    public function children(Request $request)
    {
        $parent = $request->user();

        $children = StudentParentRelation::query()
            ->with(['student.role', 'student.classRoom'])
            ->where('parent_id', $parent->id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($relation) => [
                'relation_id'   => $relation->id,
                'relation_type' => $relation->relation_type,
                'student'       => $relation->student ? [
                    'id'        => $relation->student->id,
                    'full_name' => $relation->student->full_name,
                    'username'  => $relation->student->username,
                    'email'     => $relation->student->email,
                    'class'     => $relation->student->classRoom ? [
                        'id'          => $relation->student->classRoom->id,
                        'name'        => $relation->student->classRoom->name,
                        'grade_level' => $relation->student->classRoom->grade_level,
                    ] : null,
                ] : null,
            ])
            ->values();

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar anak berhasil diambil.',
            'data'    => $children,
        ]);
    }

    public function childCheckins(Request $request, int $studentId)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $parent = $request->user();

        if (!$this->parentCanAccessStudent($parent->id, $studentId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke data siswa ini.',
                'data'    => null,
            ], 403);
        }

        $query = DailyCheckin::query()
            ->with([
                'student.role',
                'student.classRoom',
                'items.habit',
                'items.validations.validator',
            ])
            ->where('student_id', $studentId)
            ->orderByDesc('checkin_date');

        if (!empty($validated['start_date'])) {
            $query->whereDate('checkin_date', '>=', $validated['start_date']);
        }

        if (!empty($validated['end_date'])) {
            $query->whereDate('checkin_date', '<=', $validated['end_date']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $checkins = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat check-in anak berhasil diambil.',
            'data'    => [
                'items' => $checkins->getCollection()
                    ->map(fn ($checkin) => $this->formatCheckin($checkin))
                    ->values(),
                'pagination' => [
                    'page'        => $checkins->currentPage(),
                    'per_page'    => $checkins->perPage(),
                    'total'       => $checkins->total(),
                    'total_pages' => $checkins->lastPage(),
                ],
            ],
        ]);
    }

    public function parentCheckinDetail(Request $request, int $id)
    {
        $parent = $request->user();

        $checkin = DailyCheckin::query()
            ->with([
                'student.role',
                'student.classRoom',
                'items.habit',
                'items.validations.validator',
            ])
            ->find($id);

        if (!$checkin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Check-in tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if (!$this->parentCanAccessStudent($parent->id, $checkin->student_id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke check-in ini.',
                'data'    => null,
            ], 403);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail check-in anak berhasil diambil.',
            'data'    => $this->formatCheckin($checkin),
        ]);
    }

    public function teacherCheckinDetail(Request $request, int $id)
    {
        $teacher = $request->user();

        $checkin = DailyCheckin::query()
            ->with([
                'student.role',
                'student.classRoom',
                'items.habit',
                'items.validations.validator',
            ])
            ->find($id);

        if (!$checkin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Check-in tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if (!$this->teacherCanAccessStudent($teacher->id, $checkin->student_id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke check-in siswa ini.',
                'data'    => null,
            ], 403);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail check-in siswa berhasil diambil.',
            'data'    => $this->formatCheckin($checkin),
        ]);
    }

    public function validateHome(Request $request, int $id)
    {
        $validated = $request->validate([
            'item_ids'   => ['nullable', 'array'],
            'item_ids.*' => ['integer', 'exists:daily_checkin_items,id'],
        ]);

        $parent = $request->user();

        $checkin = DailyCheckin::query()
            ->with(['items'])
            ->find($id);

        if (!$checkin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Check-in tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if (!$this->parentCanAccessStudent($parent->id, $checkin->student_id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses untuk memvalidasi check-in ini.',
                'data'    => null,
            ], 403);
        }

        $itemIds = $validated['item_ids'] ?? null;

        $processed = DB::transaction(function () use ($checkin, $parent, $itemIds) {
            $itemsQuery = $checkin->items()
                ->where('is_done', true);

            if (!empty($itemIds)) {
                $itemsQuery->whereIn('id', $itemIds);
            }

            $items = $itemsQuery->get();

            $validations = $items->map(function ($item) use ($parent) {
                return $this->upsertValidation(
                    itemId: $item->id,
                    validatorId: $parent->id,
                    validatorRole: 'orang_tua',
                    validationSource: 'rumah'
                );
            });

            return [
                'requested_count'  => is_array($itemIds) ? count($itemIds) : null,
                'validated_count'  => $validations->count(),
                'skipped_count'    => is_array($itemIds)
                    ? max(count($itemIds) - $validations->count(), 0)
                    : 0,
            ];
        });

        $checkin = $checkin->fresh([
            'student.role',
            'student.classRoom',
            'items.habit',
            'items.validations.validator',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Check-in berhasil divalidasi oleh orang tua.',
            'data'    => [
                'summary' => $processed,
                'checkin' => $this->formatCheckin($checkin),
            ],
        ]);
    }

    public function validateSchool(Request $request, int $id)
    {
        $validated = $request->validate([
            'item_ids'   => ['nullable', 'array'],
            'item_ids.*' => ['integer', 'exists:daily_checkin_items,id'],
        ]);

        $teacher = $request->user();

        $checkin = DailyCheckin::query()
            ->with(['items'])
            ->find($id);

        if (!$checkin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Check-in tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if (!$this->teacherCanAccessStudent($teacher->id, $checkin->student_id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses untuk memvalidasi check-in siswa ini.',
                'data'    => null,
            ], 403);
        }

        $itemIds = $validated['item_ids'] ?? null;

        $processed = DB::transaction(function () use ($checkin, $teacher, $itemIds) {
            $itemsQuery = $checkin->items()
                ->where('is_done', true);

            if (!empty($itemIds)) {
                $itemsQuery->whereIn('id', $itemIds);
            }

            $items = $itemsQuery->get();

            $validations = $items->map(function ($item) use ($teacher) {
                return $this->upsertValidation(
                    itemId: $item->id,
                    validatorId: $teacher->id,
                    validatorRole: 'guru',
                    validationSource: 'sekolah'
                );
            });

            return [
                'requested_count'  => is_array($itemIds) ? count($itemIds) : null,
                'validated_count'  => $validations->count(),
                'skipped_count'    => is_array($itemIds)
                    ? max(count($itemIds) - $validations->count(), 0)
                    : 0,
            ];
        });

        $checkin = $checkin->fresh([
            'student.role',
            'student.classRoom',
            'items.habit',
            'items.validations.validator',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Check-in berhasil divalidasi oleh guru.',
            'data'    => [
                'summary' => $processed,
                'checkin' => $this->formatCheckin($checkin),
            ],
        ]);
    }


    public function validateHomeItem(Request $request, int $id)
    {
        $parent = $request->user();

        $item = DailyCheckinItem::query()
            ->with(['dailyCheckin.student'])
            ->find($id);

        if (!$item) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Item check-in tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if (!$item->is_done) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Item yang belum dilakukan tidak dapat divalidasi.',
                'data'    => null,
            ], 422);
        }

        $studentId = $item->dailyCheckin?->student_id;

        if (!$studentId || !$this->parentCanAccessStudent($parent->id, $studentId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses untuk memvalidasi item ini.',
                'data'    => null,
            ], 403);
        }

        $validation = $this->upsertValidation(
            itemId: $item->id,
            validatorId: $parent->id,
            validatorRole: 'orang_tua',
            validationSource: 'rumah'
        );

        $validation->load(['validator', 'checkinItem.habit']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Item berhasil divalidasi oleh orang tua.',
            'data'    => $this->formatValidation($validation),
        ]);
    }

    /**
     * POST /api/v1/teacher/checkin-items/{id}/validate
     */
    public function validateSchoolItem(Request $request, int $id)
    {
        $teacher = $request->user();

        $item = DailyCheckinItem::query()
            ->with(['dailyCheckin.student.classRoom'])
            ->find($id);

        if (!$item) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Item check-in tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if (!$item->is_done) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Item yang belum dilakukan tidak dapat divalidasi.',
                'data'    => null,
            ], 422);
        }

        $studentId = $item->dailyCheckin?->student_id;

        if (!$studentId || !$this->teacherCanAccessStudent($teacher->id, $studentId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses untuk memvalidasi item ini.',
                'data'    => null,
            ], 403);
        }

        $validation = $this->upsertValidation(
            itemId: $item->id,
            validatorId: $teacher->id,
            validatorRole: 'guru',
            validationSource: 'sekolah'
        );

        $validation->load(['validator', 'checkinItem.habit']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Item berhasil divalidasi oleh guru.',
            'data'    => $this->formatValidation($validation),
        ]);
    }

    private function parentCanAccessStudent(int $parentId, int $studentId): bool
    {
        return StudentParentRelation::query()
            ->where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();
    }

    private function teacherCanAccessStudent(int $teacherId, int $studentId): bool
    {
        return User::query()
            ->where('id', $studentId)
            ->whereNotNull('class_id')
            ->whereHas('classRoom', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->exists();
    }

    private function upsertValidation(
        int $itemId,
        int $validatorId,
        string $validatorRole,
        string $validationSource
    ): CheckinItemValidation {
        return CheckinItemValidation::query()->updateOrCreate(
            [
                'daily_checkin_item_id' => $itemId,
                'validator_role'        => $validatorRole,
            ],
            [
                'validator_id'       => $validatorId,
                'validation_source'  => $validationSource,
                'validated_at'       => now(),
            ]
        );
    }

    private function formatCheckin(DailyCheckin $checkin): array
    {
        return [
            'id'           => $checkin->id,
            'student_id'   => $checkin->student_id,
            'checkin_date' => $checkin->checkin_date,
            'notes'        => $checkin->notes,
            'student'      => $checkin->student ? [
                'id'        => $checkin->student->id,
                'full_name' => $checkin->student->full_name,
                'username'  => $checkin->student->username,
                'class'     => $checkin->student->classRoom ? [
                    'id'          => $checkin->student->classRoom->id,
                    'name'        => $checkin->student->classRoom->name,
                    'grade_level' => $checkin->student->classRoom->grade_level,
                ] : null,
            ] : null,
            'summary' => [
                'total_items'         => $checkin->items->count(),
                'total_done_items'    => $checkin->items->where('is_done', true)->count(),
                'total_validations'   => $checkin->items->sum(fn ($item) => $item->validations->count()),
                'parent_validations'  => $checkin->items->sum(
                    fn ($item) => $item->validations->where('validator_role', 'orang_tua')->count()
                ),
                'teacher_validations' => $checkin->items->sum(
                    fn ($item) => $item->validations->where('validator_role', 'guru')->count()
                ),
            ],
            'items' => $checkin->items
                ->sortBy(fn ($item) => $item->habit?->sort_order ?? 999)
                ->map(fn ($item) => [
                    'id'       => $item->id,
                    'habit_id' => $item->habit_id,
                    'habit'    => $item->habit ? [
                        'id'         => $item->habit->id,
                        'code'       => $item->habit->code,
                        'name'       => $item->habit->name,
                        'sort_order' => $item->habit->sort_order,
                    ] : null,
                    'is_done' => $item->is_done,
                    'validations' => $item->validations
                        ->map(fn ($validation) => [
                            'id'                => $validation->id,
                            'validator_id'      => $validation->validator_id,
                            'validator_role'    => $validation->validator_role,
                            'validation_source' => $validation->validation_source,
                            'validated_at'      => $validation->validated_at,
                            'validator'         => $validation->validator ? [
                                'id'        => $validation->validator->id,
                                'full_name' => $validation->validator->full_name,
                                'username'  => $validation->validator->username,
                            ] : null,
                        ])
                        ->values(),
                ])
                ->values(),
        ];
    }

    private function formatValidation(CheckinItemValidation $validation): array
    {
        return [
            'id'                    => $validation->id,
            'daily_checkin_item_id' => $validation->daily_checkin_item_id,
            'validator_id'          => $validation->validator_id,
            'validator_role'        => $validation->validator_role,
            'validation_source'     => $validation->validation_source,
            'validated_at'          => $validation->validated_at,
            'validator'             => $validation->validator ? [
                'id'        => $validation->validator->id,
                'full_name' => $validation->validator->full_name,
                'username'  => $validation->validator->username,
            ] : null,
            'item' => $validation->checkinItem ? [
                'id'       => $validation->checkinItem->id,
                'habit_id' => $validation->checkinItem->habit_id,
                'habit'    => $validation->checkinItem->habit ? [
                    'id'   => $validation->checkinItem->habit->id,
                    'name' => $validation->checkinItem->habit->name,
                ] : null,
                'is_done' => $validation->checkinItem->is_done,
            ] : null,
        ];
    }
}