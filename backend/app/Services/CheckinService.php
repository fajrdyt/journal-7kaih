<?php

namespace App\Services;

use App\Models\DailyCheckin;
use App\Models\Habit;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class CheckinService
{
    /**
     * Submit checkin harian siswa.
     *
     * Flow:
     * 1. Cek apakah siswa sudah checkin hari ini (unique constraint)
     * 2. Ambil semua 7 habit aktif
     * 3. Buat DailyCheckin + 7 DailyCheckinItem dalam satu transaksi
     *
     * @param  int    $studentId
     * @param  array  $data  { checkin_date, notes, items: [{habit_id, is_done, activity_context}] }
     * @return DailyCheckin
     *
     * @throws \Exception jika sudah checkin hari ini
     */
    public function store(int $studentId, array $data): DailyCheckin
    {
        // Cek duplikat checkin di hari yang sama
        $alreadyCheckin = DailyCheckin::where('student_id', $studentId)
            ->whereDate('checkin_date', $data['checkin_date'])
            ->exists();

        if ($alreadyCheckin) {
            throw new \Exception('Kamu sudah melakukan check-in untuk hari ini.', 409);
        }

        // Ambil semua habit aktif untuk validasi
        $activeHabits = Habit::active()->pluck('id');

        // Validasi: semua habit_id yang dikirim harus ada di daftar habit aktif
        $submittedHabitIds = collect($data['items'])->pluck('habit_id');
        $invalidHabits = $submittedHabitIds->diff($activeHabits);

        if ($invalidHabits->isNotEmpty()) {
            throw new \Exception('Terdapat habit tidak valid: ' . $invalidHabits->implode(', '), 422);
        }

        return DB::transaction(function () use ($studentId, $data) {
            // Buat header checkin
            $checkin = DailyCheckin::create([
                'student_id'   => $studentId,
                'checkin_date' => $data['checkin_date'],
                'notes'        => $data['notes'] ?? null,
                'submitted_at' => now(),
            ]);

            // Buat semua item checkin sekaligus
            $items = collect($data['items'])->map(fn($item) => [
                'daily_checkin_id' => $checkin->id,
                'habit_id'         => $item['habit_id'],
                'is_done'          => $item['is_done'],
                'activity_context' => $item['activity_context'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ])->toArray();

            $checkin->items()->insert($items);

            // Load relasi untuk response
            return $checkin->load('items.habit');
        });
    }

    /**
     * Ambil riwayat checkin milik siswa.
     *
     * @param  int    $studentId
     * @param  array  $filters  { from?, to?, per_page? }
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getHistory(int $studentId, array $filters = [])
    {
        $query = DailyCheckin::forStudent($studentId)
            ->with('items.habit')
            ->orderByDesc('checkin_date');

        if (!empty($filters['from']) && !empty($filters['to'])) {
            $query->betweenDates($filters['from'], $filters['to']);
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }

    /**
     * Ambil detail satu checkin.
     * Memastikan checkin milik student yang sedang login.
     *
     * @param  int  $checkinId
     * @param  int  $studentId
     * @return DailyCheckin
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findForStudent(int $checkinId, int $studentId): DailyCheckin
    {
        return DailyCheckin::with('items.habit')
            ->where('id', $studentId)
            ->forStudent($studentId)
            ->firstOrFail();
    }

    /**
     * Cek apakah siswa sudah checkin hari ini.
     */
    public function hasCheckedInToday(int $studentId): bool
    {
        return DailyCheckin::where('student_id', $studentId)
            ->whereDate('checkin_date', today())
            ->exists();
    }
}