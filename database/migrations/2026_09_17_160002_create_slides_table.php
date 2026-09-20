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
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slidebook_id')->constrained('slidebooks')->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('content');
            $table->text('summary')->nullable();
            $table->unsignedInteger('order')->default(1);
            $table->json('source_reference')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['slidebook_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
