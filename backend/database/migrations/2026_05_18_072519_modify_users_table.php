<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->foreignId('role_id')
                  ->nullable()
                  ->constrained('roles');

            $table->foreignId('class_id')
                  ->nullable()
                  ->constrained('classes');

            $table->string('full_name');

            $table->string('phone')->nullable();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'role_id',
                'class_id',
                'full_name',
                'phone',
                'is_active',
                'deleted_at'
            ]);
        });
    }
};