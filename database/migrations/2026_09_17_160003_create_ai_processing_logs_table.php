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
        Schema::create('ai_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->string('process_type', 50);
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('provider', 50)->default('mock');
            $table->string('model', 50);
            $table->string('prompt_version', 50)->default('v1.0');
            $table->string('input_hash', 64);
            $table->string('status', 20)->default('pending');
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempt_count')->default(1);
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id', 'status']);
            $table->index(['input_hash', 'prompt_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_processing_logs');
    }
};
