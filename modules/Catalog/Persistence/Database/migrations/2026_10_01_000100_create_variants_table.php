<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Variant = đơn vị bán & tồn kho nhỏ nhất: một tổ hợp giá trị tuỳ chọn (ADR-045). option_key = id giá trị theo thứ tự trục.
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('option_key', 64)->nullable();
            $table->string('sku', 64)->unique();
            $table->string('barcode', 32)->nullable()->unique();
            $table->string('status', 16)->default('active');     // active | inactive
            $table->unsignedInteger('weight_gram')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'option_key']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
