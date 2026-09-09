<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('amount_zmw', 12, 2);
            $table->decimal('amount_usd', 12, 2)->nullable();
            $table->enum('frequency', ['monthly', 'termly', 'yearly', 'one_time']);

            // Polymorphic relation (allows NULL if fee is global)
            $table->nullableMorphs('feeable');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
