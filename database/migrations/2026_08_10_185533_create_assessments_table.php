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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();

            // Assessment Info
            $table->string('title');
            $table->text('description')->nullable();

            // Classification & Term Linkage
            $table->enum('type', [
                'End of Term',
                'Final Exam',
            ])->default('End of Term');

            $table->unsignedTinyInteger('term')->nullable(); // e.g., 1, 2, or 3

            // Scoring & Weighting
            $table->decimal('weight_percentage', 5, 2); // e.g., 20.00 or 60.00
            $table->decimal('max_score', 8, 2)->default(100.00); // Total raw score attainable

            // Timing & Visibility
            $table->dateTime('due_date')->nullable();

            $table->timestamps();

            // Performance Indexing
            $table->index(['curriculum_id', 'term', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
