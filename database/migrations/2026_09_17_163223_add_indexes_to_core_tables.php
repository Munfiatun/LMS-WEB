<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->index('status');
            $table->index('slug');
        });

        Schema::table('learning_materials', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['slug']);
        });

        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
