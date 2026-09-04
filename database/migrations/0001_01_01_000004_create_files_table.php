<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Storage table
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Polymorphic pivot table
        Schema::create('fileables', function (Blueprint $table) {
            $table->foreignUuid('file_id')->constrained('files')->cascadeOnDelete();
            $table->uuidMorphs('fileable'); // fileable_type + fileable_id
            $table->string('collection')->default('attachment'); // 'gallery', 'submission', 'avatar', 'brochure'
            $table->unsignedInteger('sort_order')->default(0);   // for galleries / re-ordering
            $table->timestamps();

            $table->primary(['file_id', 'fileable_id', 'fileable_type', 'collection']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fileables');
        Schema::dropIfExists('files');
    }
};
