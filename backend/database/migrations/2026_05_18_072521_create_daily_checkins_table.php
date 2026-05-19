<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_checkins', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id')
                  ->constrained('users');

            $table->date('checkin_date');

            $table->text('notes')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->unique(['student_id', 'checkin_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_checkins');
    }
};