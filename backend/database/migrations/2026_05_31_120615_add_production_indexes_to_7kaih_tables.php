<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role_id', 'is_active'], 'users_role_active_index');
            $table->index(['class_id', 'is_active'], 'users_class_active_index');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->index('is_active', 'classes_is_active_index');
        });

        Schema::table('habits', function (Blueprint $table) {
            $table->index('is_active', 'habits_is_active_index');
        });

        Schema::table('student_parent_relations', function (Blueprint $table) {
            $table->index(['parent_id', 'is_active'], 'relations_parent_active_index');
            $table->index(['student_id', 'is_active'], 'relations_student_active_index');
        });

        Schema::table('daily_checkins', function (Blueprint $table) {
            $table->index('checkin_date', 'daily_checkins_date_index');
        });

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'analytics_user_created_index');
            $table->index(['event_name', 'created_at'], 'analytics_event_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex('analytics_user_created_index');
            $table->dropIndex('analytics_event_created_index');
        });

        Schema::table('daily_checkins', function (Blueprint $table) {
            $table->dropIndex('daily_checkins_date_index');
        });

        Schema::table('student_parent_relations', function (Blueprint $table) {
            $table->dropIndex('relations_parent_active_index');
            $table->dropIndex('relations_student_active_index');
        });

        Schema::table('habits', function (Blueprint $table) {
            $table->dropIndex('habits_is_active_index');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex('classes_is_active_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_active_index');
            $table->dropIndex('users_class_active_index');
        });
    }
};