<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('disk', 32);
            $table->string('path', 512);
            $table->string('original_name');
            $table->string('mime_type', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('checksum', 64);
            $table->timestamps();
            $table->unique(['brand_id', 'checksum']);
        });

        // Gắn media vào đối tượng (category, style color, banner…) với vai trò và thứ tự.
        Schema::create('mediables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained()->restrictOnDelete();
            $table->string('mediable_type', 64);
            $table->unsignedBigInteger('mediable_id');
            $table->string('role', 32);
            $table->unsignedInteger('position')->default(0);
            $table->string('alt')->nullable();
            $table->timestamps();
            $table->index(['mediable_type', 'mediable_id', 'role', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
        Schema::dropIfExists('media');
    }
};
