<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tầng giá trên đơn (roadmap Phase 8, 0.3.37): bảng giá cho giá bán của dòng (snapshot) — tách "giảm giá bán" theo nguồn
 * (sale/member/campaign) và báo cáo campaign theo bảng giá. Đơn cũ: null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->string('price_list_code', 64)->nullable()->after('compare_at_amount')->index();
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropIndex(['price_list_code']);
            $table->dropColumn('price_list_code');
        });
    }
};
