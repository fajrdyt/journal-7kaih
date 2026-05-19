<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_parent_relations', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id')
                  ->constrained('users');

            $table->foreignId('parent_id')
                  ->constrained('users');

            $table->string('relation_type');

            $table->boolean('is_active')->default(true);

            $table->unique(['student_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_parent_relations');
    }
};