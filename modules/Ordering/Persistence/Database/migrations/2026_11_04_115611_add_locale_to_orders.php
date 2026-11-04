<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Ngôn ngữ khách dùng lúc đặt (ADR-038 §8, snapshot): thông báo giao dịch chọn mẫu theo ngôn ngữ của đơn. Đơn cũ
| (null) dùng ngôn ngữ mặc định của cửa hàng.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('locale', 8)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
