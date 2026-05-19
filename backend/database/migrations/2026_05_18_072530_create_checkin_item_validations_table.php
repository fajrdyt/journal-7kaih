<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkin_item_validations', function (Blueprint $table) {

            $table->id();

            $table->foreignId('daily_checkin_item_id')
                  ->unique()
                  ->constrained('daily_checkin_items')
                  ->cascadeOnDelete();

            $table->foreignId('validator_id')
                  ->constrained('users');

            $table->string('validator_role');

            $table->string('validation_source');

            $table->timestamp('validated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_item_validations');
    }
};