<?php

namespace App\Services;

use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use App\Models\Habit;
use Illuminate\Support\Facades\DB;

class CheckinService
{
    /**
     * GET /student/checkins/today
     * Ambil habits aktif + data checkin hari ini jika sudah ada.
     */
    public function getTodayForm(int $studentId): array
    {
        $habits = Habit::active()->get();

        $todayCheckin = DailyCheckin::with('items.habit', 'items.validation.validator')
            ->forStudent($studentId)
            ->whereDate('checkin_date', today())
            ->first();

        return [
            'habits'        => $habits,
            'today_checkin' => $todayCheckin,
        ];
    }

    /**
     * POST /student/checkins
     * Upsert checkin harian siswa.
     *
     * Aturan:
     * - checkin_date kosong → pakai hari ini
     * - checkin_date diisi → wajib hari ini (validasi di Request)
     * - Jika checkin sudah ada → update (upsert)
     * - Ubah notes → validasi item TIDAK batal
     * - Ubah is_done / activity_context → validasi item DIBATALKAN
     */
    public function upsert(int $studentId, array $data): DailyCheckin
    {
        $checkinDate = $data['checkin_date'] ?? today()->format('Y-m-d');

        return DB::transaction(function () use ($studentId, $checkinDate, $data) {

            // Cari checkin yang sudah ada atau buat baru
            $checkin = DailyCheckin::firstOrCreate(
                [
                    'student_id'   => $studentId,
                    'checkin_date' => $checkinDate,
                ],
                [
                    'submitted_at' => now(),
                ]
            );

            // Update notes (tidak membatalkan validasi)
            $checkin->update([
                'notes'        => $data['notes'] ?? $checkin->notes,
                'submitted_at' => now(),
            ]);

            // Proses setiap item
            foreach ($data['items'] as $itemData) {
                $existingItem = DailyCheckinItem::where('daily_checkin_id', $checkin->id)
                    ->where('habit_id', $itemData['habit_id'])
                    ->first();

                if ($existingItem) {
                    // Cek apakah is_done atau activity_context berubah
                    $isDoneChanged  = $existingItem->is_done !== (bool) $itemData['is_done'];
                    $contextChanged = $existingItem->activity_context !== ($itemData['activity_context'] ?? null);

                    // Jika berubah → batalkan validasi item ini
                    if ($isDoneChanged || $contextChanged) {
                        $existingItem->validation()?->delete();
                    }

                    $existingItem->update([
                        'is_done'          => $itemData['is_done'],
                        'activity_context' => $itemData['activity_context'] ?? null,
                    ]);
                } else {
                    // Item belum ada → buat baru
                    DailyCheckinItem::create([
                        'daily_checkin_id' => $checkin->id,
                        'habit_id'         => $itemData['habit_id'],
                        'is_done'          => $itemData['is_done'],
                        'activity_context' => $itemData['activity_context'] ?? null,
                    ]);
                }
            }

            return $checkin->fresh(['items.habit', 'items.validation.validator']);
        });
    }

    /**
     * GET /student/checkins
     * Riwayat checkin siswa dengan filter start_date & end_date.
     */
    public function getHistory(int $studentId, array $filters = [])
    {
        $query = DailyCheckin::forStudent($studentId)
            ->with('items.habit', 'items.validation.validator')
            ->orderByDesc('checkin_date');

        if (!empty($filters['start_date'])) {
            $query->whereDate('checkin_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('checkin_date', '<=', $filters['end_date']);
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }

    /**
     * GET /student/checkins/{id}
     * Detail satu checkin milik siswa yang sedang login.
     */
    public function findForStudent(int $checkinId, int $studentId): DailyCheckin
    {
        return DailyCheckin::with('items.habit', 'items.validation.validator')
            ->where('id', $checkinId)
            ->forStudent($studentId)
            ->firstOrFail();
    }
}