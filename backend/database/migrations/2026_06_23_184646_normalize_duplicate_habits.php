<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $habitMappings = [
            'H1' => 'BANGUN_PAGI',
            'H2' => 'BERIBADAH',
            'H3' => 'MAKAN_SEHAT_BERGIZI',
            'H4' => 'BEROLAHRAGA',
            'H5' => 'GEMAR_BELAJAR',
            'H6' => 'BERMASYARAKAT',
            'H7' => 'TIDUR_CEPAT',
        ];

        DB::transaction(function () use ($habitMappings) {
            foreach ($habitMappings as $oldCode => $newCode) {
                $oldHabit = DB::table('habits')
                    ->where('code', $oldCode)
                    ->first();

                $newHabit = DB::table('habits')
                    ->where('code', $newCode)
                    ->first();

                if (!$oldHabit || !$newHabit) {
                    continue;
                }

                $oldItems = DB::table('daily_checkin_items')
                    ->where('habit_id', $oldHabit->id)
                    ->get();

                foreach ($oldItems as $oldItem) {
                    $newItem = DB::table('daily_checkin_items')
                        ->where('daily_checkin_id', $oldItem->daily_checkin_id)
                        ->where('habit_id', $newHabit->id)
                        ->first();

                    if (!$newItem) {
                        DB::table('daily_checkin_items')
                            ->where('id', $oldItem->id)
                            ->update([
                                'habit_id' => $newHabit->id,
                            ]);

                        continue;
                    }

                    $newNotes = trim((string) ($newItem->notes ?? ''));
                    $oldNotes = trim((string) ($oldItem->notes ?? ''));

                    DB::table('daily_checkin_items')
                        ->where('id', $newItem->id)
                        ->update([
                            'is_done' => (bool) $newItem->is_done
                                || (bool) $oldItem->is_done,
                            'notes' => $newNotes !== ''
                                ? $newNotes
                                : ($oldNotes !== '' ? $oldNotes : null),
                        ]);

                    $oldValidations = DB::table('checkin_item_validations')
                        ->where('daily_checkin_item_id', $oldItem->id)
                        ->get();

                    foreach ($oldValidations as $oldValidation) {
                        $existingValidation = DB::table('checkin_item_validations')
                            ->where('daily_checkin_item_id', $newItem->id)
                            ->where('validator_role', $oldValidation->validator_role)
                            ->first();

                        if ($existingValidation) {
                            DB::table('checkin_item_validations')
                                ->where('id', $oldValidation->id)
                                ->delete();

                            continue;
                        }

                        DB::table('checkin_item_validations')
                            ->where('id', $oldValidation->id)
                            ->update([
                                'daily_checkin_item_id' => $newItem->id,
                            ]);
                    }

                    DB::table('daily_checkin_items')
                        ->where('id', $oldItem->id)
                        ->delete();
                }

                DB::table('habits')
                    ->where('id', $oldHabit->id)
                    ->delete();

                DB::table('habits')
                    ->where('id', $newHabit->id)
                    ->update([
                        'is_active' => true,
                    ]);
            }
        });
    }

    public function down(): void
    {
    }
};