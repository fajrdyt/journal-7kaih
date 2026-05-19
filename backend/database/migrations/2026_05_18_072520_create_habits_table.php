<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {

            $table->id();

            $table->string('code')->unique();

            $table->string('name');

            $table->text('default_activity_context')->nullable();

            $table->integer('sort_order')->default(0);

            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};