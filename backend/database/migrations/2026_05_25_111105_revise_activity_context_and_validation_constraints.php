<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah constraint validasi agar 1 item bisa divalidasi oleh parent dan guru
        Schema::table('checkin_item_validations', function (Blueprint $table) {
            $table->dropForeign(['daily_checkin_item_id']);
        });

        Schema::table('checkin_item_validations', function (Blueprint $table) {
            $table->dropUnique('checkin_item_validations_daily_checkin_item_id_unique');
        });

        Schema::table('checkin_item_validations', function (Blueprint $table) {
            $table->unique(
                ['daily_checkin_item_id', 'validator_role'],
                'checkin_item_validations_item_role_unique'
            );
        });

        Schema::table('checkin_item_validations', function (Blueprint $table) {
            $table->foreign('daily_checkin_item_id')
                ->references('id')
                ->on('daily_checkin_items')
                ->cascadeOnDelete();
        });

        // 2. Hapus activity_context dari item check-in siswa
        Schema::table('daily_checkin_items', function (Blueprint $table) {
            $table->dropColumn('activity_context');
        });

        // 3. Hapus default_activity_context dari habit
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('default_activity_context');
        });
    }

    public function down(): void
    {
        // Kembalikan default_activity_context
        Schema::table('habits', function (Blueprint $table) {
            $table->string('default_activity_context')->nullable();
        });

        // Kembalikan activity_context
        Schema::table('daily_checkin_items', function (Blueprint $table) {
            $table->string('activity_context')->nullable();
        });

        // Balikkan constraint validasi ke kondisi lama
        Schema::table('checkin_item_validations', function (Blueprint $table) {
            $table->dropForeign(['daily_checkin_item_id']);
        });

        Schema::table('checkin_item_validations', function (Blueprint $table) {
            $table->dropUnique('checkin_item_validations_item_role_unique');
        });

        Schema::table('checkin_item_validations', function (Blueprint $table) {
            $table->unique(
                'daily_checkin_item_id',
                'checkin_item_validations_daily_checkin_item_id_unique'
            );
        });

        Schema::table('checkin_item_validations', function (Blueprint $table) {
            $table->foreign('daily_checkin_item_id')
                ->references('id')
                ->on('daily_checkin_items')
                ->cascadeOnDelete();
        });
    }
};