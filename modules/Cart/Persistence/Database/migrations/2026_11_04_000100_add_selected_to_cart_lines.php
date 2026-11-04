<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Chọn dòng để thanh toán (reference-parity §3.8): checkout chỉ lấy dòng đã chọn, dòng chưa chọn ở lại giỏ sau khi đặt.
 * Mặc định true → giỏ hiện có giữ nguyên hành vi "đặt cả giỏ".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_lines', function (Blueprint $table): void {
            $table->boolean('selected')->default(true)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('cart_lines', function (Blueprint $table): void {
            $table->dropColumn('selected');
        });
    }
};
