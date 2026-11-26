<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Lượt xem sản phẩm gom theo giờ (reference-parity §3.5-2): một dòng mỗi (mẫu, giờ) — không ghi mỗi lượt một dòng,
 * không lưu ai xem (giảm dữ liệu cá nhân).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_view_stats', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id');
            $table->dateTime('hour');
            $table->unsignedInteger('views')->default(0);
            $table->primary(['product_id', 'hour']);
            $table->index('hour');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_view_stats');
    }
};
