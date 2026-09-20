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
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'dropped'])->default('active');
            $table->decimal('progress_percentage', 5, 2)->default(0.00);
            $table->timestamp('completed_at')->nullable();
            
            // To track last accessed position for 'Continue Learning'
            $table->foreignId('last_accessed_material_id')->nullable()->constrained('learning_materials')->nullOnDelete();
            $table->foreignId('last_accessed_quiz_id')->nullable()->constrained('quizzes')->nullOnDelete();
            
            $table->timestamps();

            // A student can only enroll in a course once
            $table->unique(['course_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
