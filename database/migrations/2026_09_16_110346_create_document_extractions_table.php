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
        Schema::create('document_extractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('material_documents')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->longText('content');
            $table->unsignedInteger('page_count')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('character_count')->default(0);
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('completed'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_extractions');
    }
};
