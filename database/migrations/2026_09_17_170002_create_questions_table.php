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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained('question_banks')->cascadeOnDelete();
            $table->longText('question_text');
            $table->string('type', 30)->default('multiple_choice'); // multiple_choice, true_false
            $table->string('topic', 100)->nullable();
            $table->string('difficulty', 20)->default('medium'); // easy, medium, hard
            $table->text('explanation')->nullable();
            $table->unsignedInteger('points')->default(10);
            $table->unsignedInteger('order')->default(1);
            $table->boolean('needs_review')->default(false);
            $table->string('answer_source', 30)->default('explicit'); // explicit, inferred, manual
            $table->string('status', 20)->default('approved'); // draft, review, approved
            $table->timestamps();

            $table->index(['question_bank_id', 'status']);
            $table->index(['needs_review']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
