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
        Schema::create('material_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->string('extension', 10);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_documents');
    }
};
