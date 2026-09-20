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
        Schema::create('ai_processing_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_id')->constrained('ai_processing_logs')->cascadeOnDelete();
            $table->longText('raw_response')->nullable();
            $table->json('parsed_data');
            $table->unsignedInteger('tokens_used')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_processing_results');
    }
};
