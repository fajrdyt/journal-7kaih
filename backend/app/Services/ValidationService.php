<?php

namespace App\Services;

use App\Models\CheckinItemValidation;
use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use App\Models\StudentParentRelation;
use Illuminate\Support\Facades\DB;

class ValidationService
{
    /**
     * Validasi batch item pada satu checkin.
     * Context: rumah (parent) atau sekolah (teacher).
     */
    public function validateBatch(
        int $checkinId,
        int $validatorId,
        string $validatorRole,
        string $validationSource,
        ?array $itemIds = null
    ): array {
        $checkin = DailyCheckin::with('items.validation')->findOrFail($checkinId);

        // Ambil item eligible
        $query = $checkin->items()
            ->where('is_done', true)
            ->where('activity_context', $validationSource);

        if (!empty($itemIds)) {
            $query->whereIn('id', $itemIds);
        }

        $eligibleItems = $query->get();

        $validatedCount        = 0;
        $alreadyValidatedCount = 0;
        $skippedCount          = 0;
        $validations           = [];

        DB::transaction(function () use (
            $eligibleItems,
            $validatorId,
            $validatorRole,
            $validationSource,
            &$validatedCount,
            &$alreadyValidatedCount,
            &$skippedCount,
            &$validations
        ) {
            foreach ($eligibleItems as $item) {
                if ($item->validation) {
                    $alreadyValidatedCount++;
                    $validations[] = $item->validation->load('validator');
                    continue;
                }

                $validation = CheckinItemValidation::create([
                    'daily_checkin_item_id' => $item->id,
                    'validator_id'          => $validatorId,
                    'validator_role'        => $validatorRole,
                    'validation_source'     => $validationSource,
                    'validated_at'          => now(),
                ]);

                $validatedCount++;
                $validations[] = $validation->load('validator');
            }
        });

        return [
            'daily_checkin_id'       => $checkinId,
            'validated_count'        => $validatedCount,
            'already_validated_count'=> $alreadyValidatedCount,
            'skipped_count'          => $skippedCount,
            'validations'            => $validations,
        ];
    }

    /**
     * Validasi satu item checkin.
     */
    public function validateItem(
        int $itemId,
        int $validatorId,
        string $validatorRole,
        string $validationSource
    ): array {
        $item = DailyCheckinItem::with('validation', 'habit')->findOrFail($itemId);

        // Cek eligible
        if (!$item->is_done) {
            abort(422, 'Item harus is_done = true untuk divalidasi.');
        }

        if ($item->activity_context !== $validationSource) {
            abort(422, "Item harus activity_context = {$validationSource} untuk divalidasi.");
        }

        $alreadyValidated = false;

        if ($item->validation) {
            $alreadyValidated = true;
        } else {
            DB::transaction(function () use ($item, $validatorId, $validatorRole, $validationSource) {
                CheckinItemValidation::create([
                    'daily_checkin_item_id' => $item->id,
                    'validator_id'          => $validatorId,
                    'validator_role'        => $validatorRole,
                    'validation_source'     => $validationSource,
                    'validated_at'          => now(),
                ]);
            });
        }

        return [
            'item'              => $item->fresh(['habit', 'validation.validator']),
            'already_validated' => $alreadyValidated,
        ];
    }

    /**
     * Cek apakah parent punya relasi aktif dengan student pemilik checkin.
     */
    public function assertParentHasChild(int $parentId, int $studentId): void
    {
        $hasRelation = StudentParentRelation::where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();

        if (!$hasRelation) {
            abort(403, 'Akses ditolak. Anda tidak memiliki relasi aktif dengan siswa ini.');
        }
    }

    /**
     * Cek apakah teacher mengampu kelas siswa pemilik checkin.
     */
    public function assertTeacherHasStudent(int $teacherId, int $studentId): void
    {
        $student = \App\Models\User::with('classRoom')->findOrFail($studentId);

        if (!$student->classRoom || $student->classRoom->teacher_id !== $teacherId) {
            abort(403, 'Akses ditolak. Siswa ini bukan dari kelas yang Anda ampu.');
        }
    }
}