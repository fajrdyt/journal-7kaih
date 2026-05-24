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
     * GET /api/checkins
     * Riwayat checkin milik siswa yang sedang login.
     *
     * Query params:
     *   - from (Y-m-d)
     *   - to   (Y-m-d)
     *   - per_page (default: 10)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to', 'per_page']);
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
     * POST /api/checkins
     * Submit checkin harian siswa.
     */
    public function store(StoreCheckinRequest $request): JsonResponse
    {
        try {
            $checkin = $this->checkinService->store(
                studentId: $request->user()->id,
                data: $request->validated()
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Check-in berhasil disimpan.',
                'data'    => new CheckinResource($checkin),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/checkins/{id}
     * Detail satu checkin milik siswa yang sedang login.
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

    /**
     * GET /api/checkins/today-status
     * Cek apakah siswa sudah checkin hari ini.
     */
    public function todayStatus(Request $request): JsonResponse
    {
        $hasCheckedIn = $this->checkinService->hasCheckedInToday(
            studentId: $request->user()->id
        );

        return response()->json([
            'status' => 'success',
            'data'   => [
                'has_checked_in' => $hasCheckedIn,
                'date'           => today()->format('Y-m-d'),
            ],
        ]);
    }
}