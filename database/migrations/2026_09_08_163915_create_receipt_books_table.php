<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_books', function (Blueprint $table) {
            $table->id();
            $table->string('book_number')->unique();
            $table->integer('start_number');
            $table->integer('end_number');
            $table->integer('current_number')->nullable();
            $table->foreignId('assigned_to_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['ACTIVE', 'COMPLETED', 'LOST', 'CANCELLED'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_books');
    }
};
