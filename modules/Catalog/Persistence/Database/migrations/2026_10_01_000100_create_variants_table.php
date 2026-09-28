<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Variant = đơn vị bán & tồn kho nhỏ nhất (màu × size). brand_id lặp lại từ style để lọc phạm vi và index.
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('style_id')->constrained()->restrictOnDelete();
            $table->foreignId('style_color_id')->constrained()->restrictOnDelete();
            $table->foreignId('size_id')->constrained()->restrictOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('barcode', 32)->nullable()->unique();
            $table->string('status', 16)->default('active');     // active | inactive
            $table->unsignedInteger('weight_gram')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['style_color_id', 'size_id']);
            $table->index(['style_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
