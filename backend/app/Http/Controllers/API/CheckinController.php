<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkin\StoreCheckinRequest;
use App\Http\Resources\CheckinResource;
use App\Services\CheckinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function __construct(
        private readonly CheckinService $checkinService
    ) {}

    /**
     * GET /api/v1/student/checkins/today
     * Ambil habits aktif + data checkin hari ini jika sudah ada.
     * Hanya role: student.
     */
    public function today(Request $request): JsonResponse
    {
        $data = $this->checkinService->getTodayForm(
            studentId: $request->user()->id
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Form check-in hari ini berhasil diambil.',
            'data'    => [
                'habits' => $data['habits']->map(fn($habit) => [
                    'id'                       => $habit->id,
                    'code'                     => $habit->code,
                    'name'                     => $habit->name,
                    'default_activity_context' => $habit->default_activity_context,
                    'sort_order'               => $habit->sort_order,
                ]),
                'today_checkin' => $data['today_checkin']
                    ? new CheckinResource($data['today_checkin'])
                    : null,
            ],
        ]);
    }

    /**
     * GET /api/v1/student/checkins
     * Riwayat checkin siswa login.
     * Params: page, per_page, start_date, end_date.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['start_date', 'end_date', 'per_page']);
        $history = $this->checkinService->getHistory(
            studentId: $request->user()->id,
            filters: $filters
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat check-in berhasil diambil.',
            'data'    => CheckinResource::collection($history->items()),
            'meta'    => [
                'current_page' => $history->currentPage(),
                'last_page'    => $history->lastPage(),
                'per_page'     => $history->perPage(),
                'total'        => $history->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/student/checkins
     * Upsert checkin harian siswa.
     */
    public function store(StoreCheckinRequest $request): JsonResponse
    {
        try {
            $checkin = $this->checkinService->upsert(
                studentId: $request->user()->id,
                data: $request->validated()
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Check-in berhasil disimpan.',
                'data'    => new CheckinResource($checkin),
            ], 200);

        } catch (\Exception $e) {
    return response()->json([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ], 500);
}
    }

    /**
     * GET /api/v1/student/checkins/{id}
     * Detail satu checkin milik siswa login.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $checkin = $this->checkinService->findForStudent(
                checkinId: $id,
                studentId: $request->user()->id
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Detail check-in berhasil diambil.',
                'data'    => new CheckinResource($checkin),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data check-in tidak ditemukan.',
            ], 404);
        }
    }
}