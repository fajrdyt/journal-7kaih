<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_checkin_items', function (Blueprint $table) {
            $table->text('notes')
                ->nullable()
                ->after('is_done');
        });
    }

    public function down(): void
    {
        Schema::table('daily_checkin_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};