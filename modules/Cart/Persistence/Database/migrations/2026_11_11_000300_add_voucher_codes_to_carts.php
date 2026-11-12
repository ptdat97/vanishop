<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Mã giảm giá lưu theo giỏ, dùng chung mọi kênh (ADR-044 §5): Storefront API `PUT /carts/{id}/vouchers`, ô mã của Blade.
| Checkout tính trên các mã này; mã không còn dùng được bị bỏ khi đặt hàng. Thay cho mã giữ trong phiên web.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->json('voucher_codes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->dropColumn('voucher_codes');
        });
    }
};
