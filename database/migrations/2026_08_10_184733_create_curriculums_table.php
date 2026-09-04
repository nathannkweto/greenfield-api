<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculums', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('year'); // e.g., 1, 2, 3, 4
            $table->timestamps();

            $table->unique(['program_id', 'course_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculums');
    }
};
