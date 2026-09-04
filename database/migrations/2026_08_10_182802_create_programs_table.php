<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique(); // e.g., BAGRIC, DipCS
            $table->string('title');

            // Academic Level
            $table->enum('level', [
                'Certificate',
                'Diploma',
                'Degree',
                'Post-graduate Diploma'
            ]);

            $table->unsignedInteger('duration_value');
            $table->enum('duration_unit', ['weeks', 'months', 'years'])->default('years');

            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
