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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();

            // Academic Numbers
            $table->string('application_number')->unique();
            $table->string('admission_number')->nullable()->unique();
            $table->string('student_number')->nullable()->unique();

            // Personal Details
            $table->string('first_name');
            $table->string('middle_names')->nullable();
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();

            // Demographics & Identifiers
            $table->enum('sex', ['male', 'female']);
            $table->enum('marital_status', ['single', 'married', 'widow', 'divorced']);
            $table->string('nationality')->default('Zambian');
            $table->string('nrc_number')->nullable()->unique();
            $table->string('passport_number')->nullable()->unique();

            // Academic Profile
            $table->enum('intake', ['January', 'May', 'September']);
            $table->enum('study_mode', ['full_time', 'part_time', 'distance_learning', 'online']);
            $table->enum('status', ['Draft', 'Registered', 'Admitted', 'Pending', 'Rejected', 'Graduated', 'Suspended'])->default('Pending');

            // Dates & Progress
            $table->date('application_date');
            $table->date('admission_date')->nullable();
            $table->date('rejection_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->decimal('cgpa', 4, 2)->default(0.00);
            $table->integer('credits_completed')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
