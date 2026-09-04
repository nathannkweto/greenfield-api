<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['General', 'Timetable', 'Assignment', 'Alert'])->default('General');
            $table->enum('target_level', ['College', 'School', 'Program'])->default('College');
            $table->unsignedBigInteger('target_id')->nullable(); // Internal ID reference for School or Program
            $table->string('target_name'); // e.g., "Entire College", "School of Computer Science"
            $table->string('author');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
