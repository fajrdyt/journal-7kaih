<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_checkin_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('daily_checkin_id')
                  ->constrained('daily_checkins')
                  ->cascadeOnDelete();

            $table->foreignId('habit_id')
                  ->constrained('habits');

            $table->boolean('is_done')->default(false);

            $table->text('activity_context')->nullable();

            $table->unique(['daily_checkin_id', 'habit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_checkin_items');
    }
};