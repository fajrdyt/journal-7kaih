<?php

namespace App\Services;

use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use App\Models\Habit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckinService
{
    /**
     * Ambil check-in hari ini milik siswa login.
     */
    public function today(Request $request)
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

    /**
     * Riwayat check-in siswa login.
     */
    public function index(Request $request)
    {
        $student = $request->user();

        $query = DailyCheckin::with([
                'items.habit',
                'items.validations.validator',
            ])
            ->where('student_id', $student->id)
            ->orderByDesc('checkin_date');

        if ($request->filled('start_date')) {
            $query->whereDate('checkin_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('checkin_date', '<=', $request->end_date);
        }

        $perPage = $request->integer('per_page', 10);
        $checkins = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat check-in berhasil diambil.',
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

    /**
     * Buat/update check-in hari ini.
     *
     * Revisi context:
     * - activity_context sudah tidak diterima dari siswa
     * - siswa cukup mengirim habit_id dan is_done
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'notes'             => ['nullable', 'string', 'max:1000'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.habit_id'  => ['required', 'integer', 'exists:habits,id'],
            'items.*.is_done'   => ['required', 'boolean'],
        ]);

        $student = $request->user();
        $today = now()->toDateString();

        $checkin = DB::transaction(function () use ($student, $today, $validated) {
            $checkin = DailyCheckin::query()->updateOrCreate(
                [
                    'student_id'   => $student->id,
                    'checkin_date' => $today,
                ],
                [
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            foreach ($validated['items'] as $item) {
                DailyCheckinItem::query()->updateOrCreate(
                    [
                        'daily_checkin_id' => $checkin->id,
                        'habit_id'         => $item['habit_id'],
                    ],
                    [
                        'is_done' => $item['is_done'],
                    ]
                );
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

    /**
     * Detail check-in siswa login.
     */
    public function show(Request $request, int $id)
    {
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

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail check-in berhasil diambil.',
            'data'    => $this->formatCheckin($checkin),
        ]);
    }

    /**
     * Format response check-in.
     */
    private function formatCheckin(DailyCheckin $checkin): array
    {
        return [
            'id'           => $checkin->id,
            'student_id'   => $checkin->student_id,
            'checkin_date' => $checkin->checkin_date,
            'notes'        => $checkin->notes,
            'created_at'   => $checkin->created_at,
            'updated_at'   => $checkin->updated_at,

            'summary' => [
                'total_habits'       => $checkin->items->count(),
                'total_done'         => $checkin->items->where('is_done', true)->count(),
                'total_not_done'     => $checkin->items->where('is_done', false)->count(),
                'total_validations'  => $checkin->items->sum(fn ($item) => $item->validations->count()),
                'parent_validations' => $checkin->items->sum(
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
}