<?php

namespace App\Services;

use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CheckinService
{
    public function today(Request $request): JsonResponse
    {
        $student = $request->user();
        $today = now()->toDateString();

        $checkin = DailyCheckin::with([
            'items.habit',
            'items.validations.validator',
        ])
            ->where('student_id', $student->id)
            ->whereDate('checkin_date', $today)
            ->first();

        if (!$checkin) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Check-in hari ini belum dibuat.',
                'data'    => null,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Check-in hari ini berhasil diambil.',
            'data'    => $this->formatCheckin($checkin),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date'   => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $student = $request->user();

        $query = DailyCheckin::with([
            'items.habit',
            'items.validations.validator',
        ])
            ->where('student_id', $student->id)
            ->orderByDesc('checkin_date');

        if (!empty($validated['start_date'])) {
            $query->whereDate(
                'checkin_date',
                '>=',
                $validated['start_date']
            );
        }

        if (!empty($validated['end_date'])) {
            $query->whereDate(
                'checkin_date',
                '<=',
                $validated['end_date']
            );
        }

        $perPage = $validated['per_page'] ?? 10;
        $checkins = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat check-in berhasil diambil.',
            'data'    => [
                'items' => $checkins->getCollection()
                    ->map(
                        fn (DailyCheckin $checkin) =>
                        $this->formatCheckin($checkin)
                    )
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'checkin_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.habit_id' => [
                'required',
                'integer',
                'distinct',
                'exists:habits,id',
            ],

            'items.*.is_done' => [
                'required',
                'boolean',
            ],

            'items.*.notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $student = $request->user();
        $today = now()->toDateString();

        if (
            Gate::forUser($student)
                ->denies('create', DailyCheckin::class)
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses untuk membuat check-in.',
                'data'    => null,
            ], 403);
        }

        $checkinDate = isset($validated['checkin_date'])
            ? Carbon::createFromFormat(
                'Y-m-d',
                $validated['checkin_date']
            )->toDateString()
            : $today;

        if ($checkinDate !== $today) {
            throw ValidationException::withMessages([
                'checkin_date' => [
                    'Untuk MVP, check-in hanya boleh dilakukan untuk tanggal hari ini.',
                ],
            ]);
        }

        $checkin = DB::transaction(function () use (
            $student,
            $today,
            $validated
        ) {
            $checkin = DailyCheckin::query()
                ->where('student_id', $student->id)
                ->whereDate('checkin_date', $today)
                ->first();

            if ($checkin) {
                Gate::forUser($student)
                    ->authorize('update', $checkin);

                $checkin->update([
                    'notes'        => $validated['notes'] ?? null,
                    'submitted_at' => now(),
                ]);
            } else {
                $checkin = DailyCheckin::query()->create([
                    'student_id'   => $student->id,
                    'checkin_date' => $today,
                    'notes'        => $validated['notes'] ?? null,
                    'submitted_at' => now(),
                ]);
            }

            foreach ($validated['items'] as $item) {
                $existingItem = DailyCheckinItem::query()
                    ->where('daily_checkin_id', $checkin->id)
                    ->where('habit_id', $item['habit_id'])
                    ->first();

                $oldIsDone = $existingItem?->is_done;

                $checkinItem = DailyCheckinItem::query()
                    ->updateOrCreate(
                        [
                            'daily_checkin_id' => $checkin->id,
                            'habit_id'         => $item['habit_id'],
                        ],
                        [
                            'is_done' => (bool) $item['is_done'],
                            'notes'   => isset($item['notes'])
                                ? trim($item['notes'])
                                : null,
                        ]
                    );

                $isChanged = $oldIsDone !== null
                    && (bool) $oldIsDone !== (bool) $item['is_done'];

                if ($isChanged || !(bool) $item['is_done']) {
                    $checkinItem->validations()->delete();
                }
            }

            return $checkin->fresh([
                'items.habit',
                'items.validations.validator',
            ]);
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Check-in berhasil disimpan.',
            'data'    => $this->formatCheckin($checkin),
        ]);
    }

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $student = $request->user();

        $checkin = DailyCheckin::with([
            'items.habit',
            'items.validations.validator',
        ])
            ->where('student_id', $student->id)
            ->find($id);

        if (!$checkin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Check-in tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if (
            Gate::forUser($student)
                ->denies('view', $checkin)
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke check-in ini.',
                'data'    => null,
            ], 403);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail check-in berhasil diambil.',
            'data'    => $this->formatCheckin($checkin),
        ]);
    }

    private function formatCheckin(
        DailyCheckin $checkin
    ): array {
        return [
            'id'         => $checkin->id,
            'student_id' => $checkin->student_id,

            'checkin_date' => $checkin->checkin_date
                ? $checkin->checkin_date->format('Y-m-d')
                : null,


            'notes' => $checkin->notes,

            'submitted_at' => $checkin->submitted_at,
            'created_at'   => $checkin->created_at,
            'updated_at'   => $checkin->updated_at,

            'summary' => [
                'total_habits' => $checkin->items->count(),

                'total_done' => $checkin->items
                    ->where('is_done', true)
                    ->count(),

                'total_not_done' => $checkin->items
                    ->where('is_done', false)
                    ->count(),

                'total_validations' => $checkin->items
                    ->sum(
                        fn (DailyCheckinItem $item) =>
                        $item->validations->count()
                    ),

                'parent_validations' => $checkin->items
                    ->sum(
                        fn (DailyCheckinItem $item) =>
                        $item->validations
                            ->where(
                                'validator_role',
                                'orang_tua'
                            )
                            ->count()
                    ),

                'teacher_validations' => $checkin->items
                    ->sum(
                        fn (DailyCheckinItem $item) =>
                        $item->validations
                            ->where(
                                'validator_role',
                                'guru'
                            )
                            ->count()
                    ),
            ],

            'items' => $checkin->items
                ->sortBy(
                    fn (DailyCheckinItem $item) =>
                    $item->habit?->sort_order ?? 999
                )
                ->map(function (DailyCheckinItem $item) {
                    return [
                        'id'       => $item->id,
                        'habit_id' => $item->habit_id,

                        'habit' => $item->habit ? [
                            'id'         => $item->habit->id,
                            'code'       => $item->habit->code,
                            'name'       => $item->habit->name,
                            'sort_order' => $item->habit->sort_order,
                        ] : null,

                        'is_done' => $item->is_done,

                        'notes' => $item->notes,

                        'validations' => $item->validations
                            ->map(function ($validation) {
                                return [
                                    'id' => $validation->id,

                                    'validator_id' =>
                                        $validation->validator_id,

                                    'validator_role' =>
                                        $validation->validator_role,

                                    'validated_at' =>
                                        $validation->validated_at,

                                    'validator' =>
                                        $validation->validator
                                            ? [
                                                'id' =>
                                                    $validation
                                                        ->validator
                                                        ->id,

                                                'full_name' =>
                                                    $validation
                                                        ->validator
                                                        ->full_name,

                                                'username' =>
                                                    $validation
                                                        ->validator
                                                        ->username,
                                            ]
                                            : null,
                                ];
                            })
                            ->values(),
                    ];
                })
                ->values(),
        ];
    }
}