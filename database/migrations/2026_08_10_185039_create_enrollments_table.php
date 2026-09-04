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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->enum('status', ['In Progress', 'Completed', 'Dropped'])->default('In Progress');
            $table->date('completion_date')->nullable();
            $table->date('drop_date')->nullable();
            $table->string('grade')->nullable();
            $table->decimal('points', 4, 2)->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'curriculum_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
