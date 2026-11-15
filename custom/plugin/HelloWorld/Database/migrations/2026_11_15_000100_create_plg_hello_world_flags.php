<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Dữ liệu riêng của plugin (ADR-027): "Đánh dấu Hello" trên các màn vận hành Admin (EP v3 X1) — tham chiếu cho cột, bộ lọc,
 * thao tác hàng loạt, công cụ tải file. `subject` = tài nguyên Admin (`shipment`, `return`, `payment`, `promotion`,
 * `variant` — tồn kho, bảng giá và SkuPicker dùng chung id SKU). Không FK (nhiều bảng Core).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_hello_world_flags', function (Blueprint $table): void {
            $table->string('subject', 32);
            $table->unsignedBigInteger('subject_id');
            $table->timestamp('created_at')->nullable();
            $table->primary(['subject', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_hello_world_flags');
    }
};
